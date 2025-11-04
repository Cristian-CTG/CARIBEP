<?php

namespace Modules\Accounting\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalPrefix;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\ThirdParty;
use App\Models\Tenant\BankAccount;

// Modelos de origen para terceros
use App\Models\Tenant\Person;                 // customers / suppliers
use Modules\Payroll\Models\Worker;            // employee
use App\Models\Tenant\Seller;                 // seller

class JournalEntriesImport implements ToCollection, WithHeadingRow
{
    protected $userId;
    protected $created = 0;
    protected $updated = 0;
    protected $skipped = 0;
    protected $rowErrors = array();

    protected $headerMap = [
        'prefijo' => 'prefix',
        'numero' => 'number',
        'fecha' => 'date',
        'descripcion' => 'description',
        'codigo_cuenta' => 'account_code',
        'debito' => 'debit',
        'credito' => 'credit',
        'documento_tercero' => 'third_party_document',
        'nombre_tercero' => 'third_party_name',
        'tipo_tercero' => 'third_party_type',
        'metodo_pago' => 'payment_method',
        'nombre_banco' => 'bank_name',
    ];

    // cachés para reducir viajes a DB
    protected $cachePrefixes = array();        // prefix => JournalPrefix
    protected $cacheAccounts = array();        // account_code => ChartOfAccount
    protected $cacheBanks = array();           // bank_name => BankAccount
    protected $cacheThirdByDoc = array();      // type|document => ThirdParty

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        $clean = array();
        $lineNumber = 1;

        foreach ($rows as $row) {
            $lineNumber++;

            $r = $row->toArray();

            // Mapea cabeceras en español a inglés
            foreach ($this->headerMap as $es => $en) {
                if (isset($r[$es])) {
                    $r[$en] = $r[$es];
                    unset($r[$es]);
                }
            }

            foreach ($r as $k => $v) {
                if (is_string($v)) $r[$k] = trim($v);
            }

            // normaliza tipo de tercero (acepta 'Clientes', 'cliente', etc.)
            if (isset($r['third_party_type'])) {
                $r['third_party_type'] = $this->normalizeThirdPartyType($r['third_party_type']);
            }

            // validación mínima
            $required = array('prefix','number','date','description','account_code');
            $missing = array();
            foreach ($required as $col) {
                if (!isset($r[$col]) || $r[$col] === '' || $r[$col] === null) $missing[] = $col;
            }
            if (count($missing) > 0) {
                $this->pushError($lineNumber, 'Faltan columnas obligatorias: '.implode(', ', $missing));
                $this->skipped++;
                continue;
            }

            // débito/crédito
            $debit  = isset($r['debit'])  ? (float)$r['debit']  : 0.0;
            $credit = isset($r['credit']) ? (float)$r['credit'] : 0.0;
            if (!(($debit > 0) xor ($credit > 0))) {
                $this->pushError($lineNumber, 'Cada fila debe tener solo débito o solo crédito mayor a 0.');
                $this->skipped++;
                continue;
            }

            // código PUC simple
            if (!preg_match('/^[0-9]{2,12}$/', (string)$r['account_code'])) {
                $this->pushError($lineNumber, 'account_code inválido (solo dígitos, 2-12).');
                $this->skipped++;
                continue;
            }

            // si viene documento de tercero, asegura name/type por defecto
            if (isset($r['third_party_document']) && $r['third_party_document'] !== '') {
                if (!isset($r['third_party_name']) || $r['third_party_name'] === '') {
                    $r['third_party_name'] = $r['third_party_document'];
                }
                if (!isset($r['third_party_type']) || $r['third_party_type'] === '') {
                    $r['third_party_type'] = 'customers';
                }
            }

            $r['_line'] = $lineNumber;
            $clean[] = $r;
        }

        // Agrupa por (prefix, number)
        $grouped = array();
        foreach ($clean as $r) {
            $key = $r['prefix'].'|'.$r['number'];
            if (!isset($grouped[$key])) $grouped[$key] = array();
            $grouped[$key][] = $r;
        }

        foreach ($grouped as $key => $lines) {
            DB::transaction(function () use ($lines) {
                // Prefix
                $prefixStr = $lines[0]['prefix'];
                $prefix = $this->getPrefix($prefixStr);
                if (!$prefix) {
                    foreach ($lines as $r) {
                        $this->pushError($r['_line'], 'Prefijo inexistente: '.$prefixStr);
                        $this->skipped++;
                    }
                    return;
                }
                //Terceros Existentes
                foreach ($lines as $r) {
                    if (isset($r['third_party_document']) && $r['third_party_document'] !== '') {
                        $thirdPartyId = $this->resolveThirdParty(
                            isset($r['third_party_type']) ? $r['third_party_type'] : 'customers',
                            $r['third_party_document'],
                            isset($r['third_party_name']) ? $r['third_party_name'] : null
                        );
                        if ($thirdPartyId === false || $thirdPartyId === null) {
                            foreach ($lines as $rr) {
                                $this->pushError($rr['_line'], 'No se encontró el tercero requerido, el asiento no se registró.');
                                $this->skipped++;
                            }
                            return; // tercerno no encontrado, no registra nada de ese asiento
                        }
                        // continua si se encontró el tercero o campo vacio
                    }
                }
                // Validar bancos
                foreach ($lines as $r) {
                    if (isset($r['bank_name']) && $r['bank_name'] !== '') {
                        $bank = $this->getBankByDescription($r['bank_name']);
                        if (!$bank) {
                            foreach ($lines as $rr) {
                                $this->pushError($rr['_line'], 'No se encontró el banco: '.$r['bank_name'].'. El asiento no se registró.');
                                $this->skipped++;
                            }
                            return;
                        }
                    }
                }

                // Balance asiento
                $sumD = 0.0; $sumC = 0.0;
                foreach ($lines as $r) {
                    $sumD += isset($r['debit'])  ? (float)$r['debit']  : 0.0;
                    $sumC += isset($r['credit']) ? (float)$r['credit'] : 0.0;
                }
                if (abs($sumD - $sumC) > 0.01) {
                    foreach ($lines as $r) {
                        $this->pushError($r['_line'], 'Asiento desbalanceado (D '.$sumD.' ≠ C '.$sumC.').');
                        $this->skipped++;
                    }
                    return;
                }

                // Si el asiento ya existe (unique journal_prefix_id + number), se omite.
                $number = $lines[0]['number'];
                $already = JournalEntry::where('journal_prefix_id', $prefix->id)
                                       ->where('number', $number)
                                       ->first();
                if ($already) {
                    foreach ($lines as $r) {
                        $this->pushError(
                            $r['_line'],
                            'Asiento ya existente para ['.$prefixStr.'-'.$number.']. Importación solo crea, no actualiza.'
                        );
                        $this->skipped++;
                    }
                    return; // omite creación
                }

                $entry = new JournalEntry();
                $entry->journal_prefix_id = $prefix->id;
                $entry->number            = $number;
                $entry->date              = $lines[0]['date'];
                $entry->description       = $lines[0]['description'];
                if (!$entry->status) $entry->status = 'posted';
                $entry->save();

                // Inserta detalles
                foreach ($lines as $r) {
                    // Cuenta contable
                    $account = $this->getChartAccount($r['account_code']);
                    if (!$account) {
                        $this->pushError($r['_line'], 'Cuenta PUC no encontrada: '.$r['account_code']);
                        throw new \RuntimeException('Abort asiento por cuenta inexistente: '.$r['account_code']);
                    }

                    // Resolver tercero (opcional)
                    $thirdPartyId = null;
                    if (isset($r['third_party_document']) && $r['third_party_document'] !== '') {
                        $thirdPartyId = $this->resolveThirdParty(
                            isset($r['third_party_type']) ? $r['third_party_type'] : 'customers',
                            $r['third_party_document'],
                            isset($r['third_party_name']) ? $r['third_party_name'] : null
                        );
                        if ($thirdPartyId === false || $thirdPartyId === null) {
                            // Marca error y omite TODO el asiento
                            foreach ($lines as $rr) {
                                $this->pushError($rr['_line'], 'No se encontró el tercero requerido, el asiento no se registró.');
                                $this->skipped++;
                            }
                            return; // Sale de la transacción, no registra nada
                        }
                    }

                    // Banco (opcional) por descripción exacta
                    $bankAccountId = null;
                    if (isset($r['bank_name']) && $r['bank_name'] !== '') {
                        $bank = $this->getBankByDescription($r['bank_name']);
                        if ($bank) $bankAccountId = $bank->id;
                    }

                    $debit  = isset($r['debit'])  ? number_format((float)$r['debit'], 2, '.', '')  : '0.00';
                    $credit = isset($r['credit']) ? number_format((float)$r['credit'], 2, '.', '') : '0.00';

                    $entry->details()->create(array(
                        'chart_of_account_id' => $account->id,
                        'third_party_id'      => $thirdPartyId,
                        'payment_method_name' => isset($r['payment_method']) ? $r['payment_method'] : null,
                        'bank_account_id'     => $bankAccountId,
                        'debit'               => $debit,
                        'credit'              => $credit,
                    ));
                }

                $this->created++;
            });
        }
        // Log de errores por fila
        if (!empty($this->rowErrors)) {
                foreach ($this->rowErrors as $err) {
                    \Log::error('[Import Asientos] Error en fila', [
                        'row' => $err['row'],
                        'message' => $err['message']
                    ]);
                }
            }
        // Log resumen del proceso
        \Log::info('[Import Asientos] Resumen', [
            'created_entries' => $this->created,
            'skipped_rows'    => $this->skipped,
            'errores'         => count($this->rowErrors)
        ]);
    }

    /** ------------------------
     *  Helpers y resolutores
     *  ------------------------ */

    protected function getPrefix($prefixStr)
    {
        if (isset($this->cachePrefixes[$prefixStr])) return $this->cachePrefixes[$prefixStr];
        $m = JournalPrefix::where('prefix', $prefixStr)->first();
        $this->cachePrefixes[$prefixStr] = $m;
        return $m;
    }

    protected function getChartAccount($code)
    {
        if (isset($this->cacheAccounts[$code])) return $this->cacheAccounts[$code];
        $m = ChartOfAccount::where('code', $code)->first();
        $this->cacheAccounts[$code] = $m;
        return $m;
    }

    protected function getBankByDescription($desc)
    {
        if (isset($this->cacheBanks[$desc])) return $this->cacheBanks[$desc];
        $m = BankAccount::where('description', $desc)->first();
        $this->cacheBanks[$desc] = $m;
        return $m;
    }

    /**
     * Resuelve/crea el tercero en third_parties con referencia al origen.
     * type: customers|suppliers|employee|seller
     */
    protected function resolveThirdParty($type, $document, $name)
    {
        $key = strtolower($type).'|'.$document;

        if (isset($this->cacheThirdByDoc[$key])) {
            return $this->cacheThirdByDoc[$key]->id;
        }

        // 1) Buscar en third_parties
        $tp = ThirdParty::where('type', strtolower($type))
                        ->where('document', $document)
                        ->first();
        if ($tp) {
            $this->cacheThirdByDoc[$key] = $tp;
            return $tp->id;
        }

        // 2) Buscar en tabla de origen según tipo
        $originId = null;
        $documentType = null;
        $address = null;
        $phone = null;
        $email = null;

        if ($type === 'customers' || $type === 'suppliers') {
            $origin = Person::where('number', $document)
                            ->where('type', $type)
                            ->first();
            if (!$origin && $name) {
                $origin = Person::where('name', $name)
                                ->where('type', $type)
                                ->first();
            }
            if ($origin) {
                $originId = $origin->id;
                $documentType = $origin->identity_document_type_id ?? null;
                $address = $origin->address ?? null;
                $phone = $origin->telephone ?? null;
                $email = $origin->email ?? null;
            }
        } elseif ($type === 'employee') {
            // Worker: identification_number, full name compuesto
            $origin = Worker::where('identification_number', $document)->first();
            if (!$origin && $name) {
                // Construye el nombre completo como se guarda en terceros
                $fullName = trim($name);
                // Busca por nombre completo usando LIKE para mayor flexibilidad
                $origin = Worker::whereRaw("CONCAT(second_surname, ' ', surname, ' ', first_name) LIKE ?", ["%{$fullName}%"])->first();
            }
            if ($origin) {
                $originId = $origin->id;
                $documentType = $origin->payroll_type_document_identification_id ?? null;
                $address = $origin->address ?? null;
                $phone = $origin->cellphone ?? null;
                $email = $origin->email ?? null;
            }
        } elseif ($type === 'seller') {
            // Seller: document_number, full_name
            $origin = Seller::where('document_number', $document)->first();
            if (!$origin && $name) {
                $origin = Seller::where('full_name', $name)->first();
            }
            if ($origin) {
                $originId = $origin->id;
                $documentType = $origin->type_document_identification_id ?? null;
                $address = $origin->address ?? null;
                $phone = $origin->phone ?? null;
                $email = $origin->email ?? null;
            }
        }

        // Si no se encontró el origen, manda error y no crea el tercero
        if (!$originId) {
            $this->pushError(null, "No se encontró el origen para el tercero tipo '{$type}' con documento '{$document}' y nombre '{$name}'.");
            return null;
        }

        // 3) Crear ThirdParty con o sin origin_id (si no hay origen)
        $tp = ThirdParty::create(array(
            'name'          => $name ? $name : $document,
            'type'          => strtolower($type),
            'document'      => $document,
            'document_type' => $documentType,
            'address'       => $address,
            'phone'         => $phone,
            'email'         => $email,
            'origin_id'     => $originId,
        ));

        $this->cacheThirdByDoc[$key] = $tp;
        return $tp->id;
    }

    /**
     * Normaliza variantes a los 4 tipos esperados
     */
    protected function normalizeThirdPartyType($raw)
    {
        $s = strtolower(trim($raw));
        // comunes en español
        if (in_array($s, array('cliente','clientes','customer'))) return 'customers';
        if (in_array($s, array('proveedor','proveedores','supplier'))) return 'suppliers';
        if (in_array($s, array('empleado','empleados','worker','employee'))) return 'employee';
        if (in_array($s, array('vendedor','vendedores','seller'))) return 'seller';
        // si ya viene correcto, lo respeta
        if (in_array($s, array('customers','suppliers','employee','seller'))) return $s;
        // por defecto, clientes
        return 'customers';
    }

    protected function pushError($row, $msg)
    {
        $this->rowErrors[] = array('row' => $row, 'message' => $msg);
    }

    public function getCreatedEntries() { 
        return $this->created; 
    }
    public function getUpdatedEntries() { 
        return $this->updated; 
    }
    public function getSkippedRows()  { 
        return $this->skipped; 
    }
    public function getRowErrors()    { 
        return $this->rowErrors; 
    }
}
