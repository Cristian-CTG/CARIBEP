<?php

namespace Modules\Finance\Traits;

use App\Models\Tenant\Cash;
use App\Models\Tenant\BankAccount;
use App\Models\Tenant\Company;
use Carbon\Carbon;
use Modules\Accounting\Models\JournalPrefix;
use Modules\Expense\Models\ExpensePayment;
use App\Models\Tenant\{
    DocumentPayment,
    SaleNotePayment,
    PurchasePayment,
    DocumentPosPayment
};
use Modules\Purchase\Models\SupportDocumentPayment;
use Modules\Sale\Models\QuotationPayment;
use Modules\Sale\Models\ContractPayment;
use Modules\Finance\Models\IncomePayment;
use Modules\Factcolombia1\Models\Tenant\{
    Currency,
};
use Modules\Sale\Models\RemissionPayment;
use Modules\Accounting\Helpers\AccountingEntryHelper;
use Modules\Accounting\Models\AccountingChartAccountConfiguration;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Factcolombia1\Models\Tenant\TypeDocument;
use App\Models\Tenant\Person;
use Modules\Accounting\Models\ThirdParty;
use Modules\Factcolombia1\Models\Tenant\TypeIdentityDocument;
use App\Models\Tenant\Catalogs\DocumentType;

trait FinanceTrait
{

    public function getPaymentDestinations(){

        $bank_accounts = self::getBankAccounts();
        $cash = $this->getCash();

        // dd($cash);

        return collect($bank_accounts)->push($cash);

    }


    private static function getBankAccounts(){

        return BankAccount::get()->transform(function($row) {
            return [
                'id' => $row->id,
                'cash_id' => null,
                'description' => "{$row->bank->description} - {$row->currency_type_id} - {$row->description}",
            ];
        });

    }


    public function getCash(){

        $cash =  Cash::where([['user_id',auth()->user()->id],['state',true]])->first();

        if($cash){

            return [
                'id' => 'cash',
                'cash_id' => $cash->id,
                'description' => ($cash->reference_number) ? "Caja - {$cash->reference_number}" : "Caja",
            ];

        }else{

            $cash_create = Cash::create([
                                    'user_id' => auth()->user()->id,
                                    'date_opening' => date('Y-m-d'),
                                    'time_opening' => date('H:i:s'),
                                    'date_closed' => null,
                                    'time_closed' => null,
                                    'beginning_balance' => 0,
                                    'final_balance' => 0,
                                    'income' => 0,
                                    'state' => true,
                                    'reference_number' => null
                                ]);

            return [
                'id' => 'cash',
                'cash_id' => $cash_create->id,
                'description' => "Caja"
            ];

        }

    }

    public function createGlobalPayment($model, $row, $is_credit = false)
    {
        $destination = $this->getDestinationRecord($row);
        $company = Company::active();

        $document = null;
        if ($model instanceof PurchasePayment) {
            $document = $model->purchase;
        } elseif ($model instanceof DocumentPayment) {
            $document = $model->document;
        } 
        elseif ($model instanceof SupportDocumentPayment) {
            $document = $model->support_document;
        }
        elseif ($model instanceof ExpensePayment) {
            $document = $model->expense;
        }

        if ($model instanceof PurchasePayment) {
            // if ($is_credit) {
                $this->generateJournalEntry($model, $document, $destination);
            // }
        } elseif ($model instanceof ExpensePayment) {
            $this->generateJournalEntry($model, $document, $destination);
        } else {
            // Para otros módulos, sigue la lógica anterior (por ejemplo, payment_form_id)
            if ($document && isset($document->payment_form_id) && $document->payment_form_id == 2) {
                $this->generateJournalEntry($model, $document, $destination);
            }
        }

        $model->global_payment()->create([
            'soap_type_id' => $company->soap_type_id,
            'destination_id' => $destination['destination_id'],
            'destination_type' => $destination['destination_type'],
        ]);
    }

    public function generateJournalEntry($model, $document, $destination)
    {
        $config = AccountingChartAccountConfiguration::first();

        // Cuentas fijas
        $accountCash = ChartOfAccount::where('code', '110505')->first(); // Caja
        $accountBank = ChartOfAccount::where('code', '111005')->first(); // Banco
        $accountReceivable = ChartOfAccount::where('code', $config->customer_receivable_account)->first();
        $accountPayable = ChartOfAccount::where('code', $config->supplier_payable_account)->first();

        // Tipo de destino
        $destinationType = $destination['destination_type'];
        $destinationId = $destination['destination_id'];

        // movimiento de destino (caja/banco)
        $accountDestinationID = null;
        $bank_account_id = null;
        if ($destinationType === Cash::class) {
            $accountDestinationID = $accountCash->id;
            $bank_account_id = null;
        } elseif ($destinationType === BankAccount::class) {
            $bank = BankAccount::find($destinationId);
            $accountDestinationID = $bank && $bank->chart_of_account_id
                ? $bank->chart_of_account_id
                : $accountBank->id;
            $bank_account_id = $bank ? $bank->id : null;
        }
        // Obtener el cliente como tercer implicado
        $thirdPartyId = null;
        if ($document) {
            // Si tiene customer_id, es cliente
            if (isset($document->customer_id) && $document->customer_id) {
                $person = Person::find($document->customer_id);
                $documentType = null;
                if ($person->identity_document_type_id) {
                    $typeDoc = TypeIdentityDocument::find($person->identity_document_type_id);
                    $documentType = $typeDoc ? $typeDoc->code : null;
                }
                if ($person) {
                    $thirdParty = ThirdParty::updateOrCreate(
                        ['document' => $person->number, 'type' => $person->type],
                        ['name' => $person->name, 'email' => $person->email, 'address' => $person->address, 'phone' => $person->telephone, 'document_type' => $documentType, 'origin_id' => $person->id]
                    );
                    $thirdPartyId = $thirdParty->id;
                }
            }
            // Si tiene supplier_id, es proveedor
            elseif (isset($document->supplier_id) && $document->supplier_id) {
                $person = Person::find($document->supplier_id);
                $documentType = null;
                if ($person->identity_document_type_id) {
                    $typeDoc = TypeIdentityDocument::find($person->identity_document_type_id);
                    $documentType = $typeDoc ? $typeDoc->code : null;
                }
                if ($person) {
                    $thirdParty = ThirdParty::updateOrCreate(
                        ['document' => $person->number, 'type' => $person->type],
                        ['name' => $person->name, 'email' => $person->email, 'address' => $person->address, 'phone' => $person->telephone, 'document_type' => $documentType, 'origin_id' => $person->id]
                    );
                    $thirdPartyId = $thirdParty->id;
                }
            }
        }

        // Detectar tipo de modelo y configurar el asiento
        $typeConfig = $this->getPaymentTypeConfig($model, $accountReceivable, $accountPayable);

        // Obtener el método de pago si existe
        $payment_method_name = null;
        if (method_exists($model, 'getPaymentMethodNameAttribute')) {
            $payment_method_name = $model->payment_method_name;
        }

        // Armar movimientos
        $movements = [
            [
                'account_id' => $accountDestinationID,
                'debit' => $typeConfig['debit'],
                'credit' => $typeConfig['credit'],
                'affects_balance' => true,
                'third_party_id' => $thirdPartyId, 
                'payment_method_name' => $payment_method_name, // <-- aquí
                'bank_account_id' => $bank_account_id, 
            ],
            [
                'account_id' => $typeConfig['counter_account_id'],
                'debit' => $typeConfig['counter_debit'],
                'credit' => $typeConfig['counter_credit'],
                'affects_balance' => false,
                'third_party_id' => $thirdPartyId, 
                // 'payment_method_name' => $payment_method_name, // <-- aquí
            ],
        ];

        $description = 'Pago de ';
        if ($model instanceof PurchasePayment) {
            // Para compras, usa la relación document_type
            $document_type = DocumentType::find($document->document_type_id);
            $description .= $document_type ? $document_type->description : '';
            $description .= ' #' . ($document->series ?? '') . '-' . ($document->number ?? '');
        } elseif ($model instanceof ExpensePayment) {
            // Para gastos, usa Gasto #
            $description .= 'Gasto #' . ($document->number ?? '');
        } else {
            // Para otros módulos, usa TypeDocument
            $documentType = TypeDocument::find($document->type_document_id);
            $description .= $documentType ? $documentType->name : '';
            $description .= ' #' . ($document->prefix ?? '') . '-' . ($document->number ?? '');
        }

        // Identificador dinámico (document_id o purchase_id, o el que toque)
        $referenceField = $typeConfig['reference_field'];

        AccountingEntryHelper::registerEntry([
            'prefix_id' => $typeConfig['prefix_id'],
            'description' => $description,
            $referenceField => $document->id,
            'movements' => $movements,
        ]);
    }

    private function getPaymentTypeConfig($model, $accountReceivable, $accountPayable)
    {
        if ($model instanceof DocumentPayment) {
            return [
                'prefix_id' => 3,
                'debit' => $model->payment,
                'credit' => 0,
                'counter_account_id' => $accountReceivable->id,
                'counter_debit' => 0,
                'counter_credit' => $model->payment,
                'reference_field' => 'document_id',
            ];
        }

        if ($model instanceof PurchasePayment) {
            $isCreditNote = false;
            if (isset($model->purchase) && $model->purchase && isset($model->purchase->document_type_id)) {
                $isCreditNote = $model->purchase->document_type_id == '07';
            }
            if ($isCreditNote) {

                return [
                    'prefix_id' => 4,
                    'debit' => $model->payment,
                    'credit' => 0,
                    'counter_account_id' => $accountPayable->id,
                    'counter_debit' => 0,
                    'counter_credit' => $model->payment,
                    'reference_field' => 'purchase_id',
                ];
            } else {

                return [
                    'prefix_id' => 4,
                    'debit' => 0,
                    'credit' => $model->payment,
                    'counter_account_id' => $accountPayable->id,
                    'counter_debit' => $model->payment,
                    'counter_credit' => 0,
                    'reference_field' => 'purchase_id',
                ];
            }
        }

        if ($model instanceof SupportDocumentPayment) {
            return [
                'prefix_id' => 4,
                'debit' => 0,
                'credit' => $model->payment,
                'counter_account_id' => $accountPayable->id,
                'counter_debit' => $model->payment,
                'counter_credit' => 0,
                'reference_field' => 'support_document_id',
            ];
        }

        if ($model instanceof ExpensePayment) {
            $prefix_id = JournalPrefix::where('prefix', 'GD')->first();
            return [
                'prefix_id' => 4,
                'debit' => 0,
                'credit' => $model->payment,
                'counter_account_id' => $accountPayable->id,
                'counter_debit' => $model->payment,
                'counter_credit' => 0,
                'reference_field' => 'expense_id',
            ];
        }

        // Aquí puedes agregar nuevos tipos, por ejemplo:
        /*
        if ($model instanceof CreditNotePayment) {
            return [
                ...
            ];
        }
        */

        throw new \Exception('Tipo de modelo de pago no soportado: ' . get_class($model));
    }

    public function getDestinationRecord($row){

        if($row['payment_destination_id'] === 'cash'){

            $destination_id = $this->getCash()['cash_id'];
            $destination_type = Cash::class;

        }else{

            $destination_id = $row['payment_destination_id'];
            $destination_type = BankAccount::class;

        }

        return [
            'destination_id' => $destination_id,
            'destination_type' => $destination_type,
        ];
    }


    public function deleteAllPayments($payments){

        foreach ($payments as $payment) {
            $payment->delete();
        }

    }

    public function getCollectionPaymentTypes(){

        return [
            ['id'=> DocumentPayment::class, 'description' => 'FACTURA ELECTRÓNICA'],
            ['id'=> DocumentPosPayment::class, 'description' => 'DOCUMENTO POS'],
            // ['id'=> SaleNotePayment::class, 'description' => 'NOTAS DE VENTA'],
            ['id'=> PurchasePayment::class, 'description' => 'COMPRAS'],
            ['id'=> ExpensePayment::class, 'description' => 'GASTOS'],
            ['id'=> QuotationPayment::class, 'description' => 'COTIZACIÓN'],
            // ['id'=> ContractPayment::class, 'description' => 'CONTRATO'],
            ['id'=> IncomePayment::class, 'description' => 'INGRESO'],
            ['id'=> RemissionPayment::class, 'description' => 'REMISIÓN'],
            ['id'=> SupportDocumentPayment::class, 'description' => 'DOCUMENTO DE SOPORTE'],
        ];
    }

    public function getCollectionDestinationTypes(){

        return [
            ['id'=> Cash::class, 'description' => 'Caja'],
            ['id'=> BankAccount::class, 'description' => 'CUENTA BANCARIA'],
        ];
    }

    public function getDatesOfPeriod($request){

        $period = $request['period'];
        $date_start = $request['date_start'];
        $date_end = $request['date_end'];
        $month_start = $request['month_start'];
        $month_end = $request['month_end'];

        $d_start = null;
        $d_end = null;

        switch ($period) {
            case 'month':
                $d_start = Carbon::parse($month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($month_start.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'between_months':
                $d_start = Carbon::parse($month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($month_end.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'date':
                $d_start = $date_start;
                $d_end = $date_start;
                break;
            case 'between_dates':
                $d_start = $date_start;
                $d_end = $date_end;
                break;
        }

        return [
            'd_start' => $d_start,
            'd_end' => $d_end
        ];
    }


    public function getBalanceByCash($cash){

        $document_payment = $this->getSumPayment($cash, DocumentPayment::class);
        $expense_payment = $this->getSumPayment($cash, ExpensePayment::class);
        // $sale_note_payment = $this->getSumPayment($cash, SaleNotePayment::class);
        $purchase_payment = $this->getSumPayment($cash, PurchasePayment::class);
        $support_document_payment = $this->getSumPayment($cash, SupportDocumentPayment::class);
        $quotation_payment = $this->getSumPayment($cash, QuotationPayment::class);
        $contract_payment = $this->getSumPayment($cash, ContractPayment::class);
        $income_payment = $this->getSumPayment($cash, IncomePayment::class);
        $remission_payment = $this->getSumPayment($cash, RemissionPayment::class);
        $document_pos_payment = $this->getSumPayment($cash, DocumentPosPayment::class);

        $entry = $document_payment + $quotation_payment + $contract_payment + $income_payment + $remission_payment + $document_pos_payment;
        $egress = $expense_payment + $purchase_payment + $support_document_payment;

        $balance = $entry - $egress;

        return [

            'id' => 'cash',
            'description' => "Caja",
            'expense_payment' => number_format($expense_payment,2, ".", ""),
            // 'sale_note_payment' => number_format($sale_note_payment,2, ".", ""),
            'quotation_payment' => number_format($quotation_payment,2, ".", ""),
            'contract_payment' => number_format($contract_payment,2, ".", ""),
            'income_payment' => number_format($income_payment,2, ".", ""),
            'document_payment' => number_format($document_payment,2, ".", ""),
            'purchase_payment' => number_format($purchase_payment,2, ".", ""),
            'support_document_payment' => number_format($support_document_payment,2, ".", ""),
            'remission_payment' => number_format($remission_payment,2, ".", ""),
            'document_pos_payment' => number_format($document_pos_payment,2, ".", ""),
            'balance' => number_format($balance,2, ".", "")

        ];

    }



    public function getBalanceByBankAcounts($bank_accounts){

        $records = $bank_accounts->map(function($row){

            $document_payment = $this->getSumPayment($row->global_destination, DocumentPayment::class);
            $expense_payment = $this->getSumPayment($row->global_destination, ExpensePayment::class);
            // $sale_note_payment = $this->getSumPayment($row->global_destination, SaleNotePayment::class);
            $purchase_payment = $this->getSumPayment($row->global_destination, PurchasePayment::class);
            $support_document_payment = $this->getSumPayment($row->global_destination, SupportDocumentPayment::class);
            $quotation_payment = $this->getSumPayment($row->global_destination, QuotationPayment::class);
            $contract_payment = $this->getSumPayment($row->global_destination, ContractPayment::class);
            $income_payment = $this->getSumPayment($row->global_destination, IncomePayment::class);
            $remission_payment = $this->getSumPayment($row->global_destination, RemissionPayment::class);
            $document_pos_payment = $this->getSumPayment($row->global_destination, DocumentPosPayment::class);

            $entry = $document_payment + $quotation_payment + $contract_payment + $income_payment + $remission_payment + $document_pos_payment;
            $egress = $expense_payment + $purchase_payment + $support_document_payment;
            $balance = $entry - $egress;

            return [

                'id' => $row->id,
                'description' => "{$row->bank->description} - {$row->currency_type_id} - {$row->description}",
                'expense_payment' => number_format($expense_payment,2, ".", ""),
                // 'sale_note_payment' => number_format($sale_note_payment,2, ".", ""),
                'quotation_payment' => number_format($quotation_payment,2, ".", ""),
                'contract_payment' => number_format($contract_payment,2, ".", ""),
                'document_payment' => number_format($document_payment,2, ".", ""),
                'purchase_payment' => number_format($purchase_payment,2, ".", ""),
                'support_document_payment' => number_format($support_document_payment,2, ".", ""),
                'income_payment' => number_format($income_payment,2, ".", ""),
                'remission_payment' => number_format($remission_payment,2, ".", ""),
                'document_pos_payment' => number_format($document_pos_payment,2, ".", ""),
                'balance' => number_format($balance,2, ".", "")

            ];

        });

        return $records;

    }

    public function getSumPayment($record, $model)
    {
        return $record->where('payment_type', $model)->sum(function($row){
            return $this->calculateTotalCurrencyType($row->payment->associated_record_payment, $row->payment->payment);
        });
    }


    public function calculateTotalCurrencyType($record, $payment)
    {
        return $payment;
        // return ($record->currency_type_id === 'USD') ? $payment * $record->exchange_rate_sale : $payment;
    }


    public function getRecordsByPaymentMethodTypes($payment_method_types)
    {
        return $payment_method_types->map(function($row){
            $document_payment = $this->getSumByPMT($row->document_payments);
            // $sale_note_payment = $this->getSumByPMT($row->sale_note_payments);
            $purchase_payment = $this->getSumByPMT($row->purchase_payments);
            $support_document_payment = $this->getSumByPMT($row->support_document_payments);
            $quotation_payment = $this->getSumByPMT($row->quotation_payments);
            $contract_payment = $this->getSumByPMT($row->contract_payments);
            $income_payment = $this->getSumByPMT($row->income_payments);
            $remission_payment = $this->getSumByPMT($row->remission_payments);
            $document_pos_payment = $this->getSumByPMT($row->document_pos_payments);

            // Convertir strings a números para el cálculo
            $doc = $document_payment ?: 0;
            $rem = $remission_payment ?: 0;
            $pos = $document_pos_payment ?: 0;
            $inc = $income_payment ?: 0;
            $pur = $purchase_payment ?: 0;
            $sup = $support_document_payment ?: 0;

            $total = $doc + $rem + $pos + $inc + $pur + $quotation_payment + $contract_payment + $sup;

            return [

                'id' => $row->id,
                'description' => $row->description,
                'expense_payment' => '-',
                'document_payment' => number_format($doc, 2, ".", ""),
                'purchase_payment' => number_format($pur, 2, ".", ""),
                'support_document_payment' => number_format($sup, 2, ".", ""),
                'quotation_payment' => number_format($quotation_payment, 2, ".", ""),
                'contract_payment' => number_format($contract_payment, 2, ".", ""),
                'income_payment' => number_format($inc, 2, ".", ""),
                'remission_payment' => number_format($rem, 2, ".", ""),
                'document_pos_payment' => number_format($pos, 2, ".", ""),
                'total_income' => number_format($doc + $rem + $pos + $inc, 2, ".", ""),
                'total_expense' => number_format($pur + $sup, 2, ".", "")
            ] + ['_total_payments' => $total];
        })
        ->filter(function($row){
            return $row['_total_payments'] > 0;
        })
        ->map(function($row){
            unset($row['_total_payments']);
            return $row;
        })
        ->values();
    }

    public function getRecordsByPaymentMethods($payment_methods)
    {
        return $payment_methods->map(function($pm){
            $document_payment = $this->getSumByPMT($pm->document_payments);
            $purchase_payment = $this->getSumByPMT($pm->purchase_payments);
            $support_document_payment = $this->getSumByPMT($pm->support_document_payments);
            $quotation_payment = $this->getSumByPMT($pm->quotation_payments);
            $contract_payment = $this->getSumByPMT($pm->contract_payments);
            $income_payment = $this->getSumByPMT($pm->income_payments);
            $remission_payment = $this->getSumByPMT($pm->remission_payments);
            $document_pos_payment = $this->getSumByPMT($pm->document_pos_payments);

            $doc = $document_payment ?: 0;
            $rem = $remission_payment ?: 0;
            $pos = $document_pos_payment ?: 0;
            $inc = $income_payment ?: 0;
            $pur = $purchase_payment ?: 0;
            $sup = $support_document_payment ?: 0;

            $total = $doc + $rem + $pos + $inc + $pur + $quotation_payment + $contract_payment + $sup;

            return [
                'id' => $pm->id,
                'description' => $pm->name,
                'expense_payment' => '-',
                'document_payment' => number_format($doc, 2, ".", ""),
                'purchase_payment' => number_format($pur, 2, ".", ""),
                'support_document_payment' => number_format($sup, 2, ".", ""),
                'quotation_payment' => number_format($quotation_payment, 2, ".", ""),
                'contract_payment' => number_format($contract_payment, 2, ".", ""),
                'income_payment' => number_format($inc, 2, ".", ""),
                'remission_payment' => number_format($rem, 2, ".", ""),
                'document_pos_payment' => number_format($pos, 2, ".", ""),
                'total_income' => number_format($doc + $rem + $pos + $inc, 2, ".", ""),
                'total_expense' => number_format($pur + $sup, 2, ".", "")
            ] + ['_total_payments' => $total];
        })
        ->filter(function($row){
            return $row['_total_payments'] > 0;
        })
        ->map(function($row){
            unset($row['_total_payments']);
            return $row;
        })
        ->values();
    }

    public function getRecordsByExpenseMethodTypes($expense_method_types)
    {

        $records = $expense_method_types->map(function($row){

            // dd($row->expense_payments);
            $expense_payment = $this->getSumByPMT($row->expense_payments);
            $exp = $expense_payment ?: 0;

            return [

                'id' => $row->id,
                'description' => $row->description,
                'expense_payment' => number_format($exp, 2, ".", ""),
                'document_pos_payment' => '-',
                // 'sale_note_payment' => '-',
                'document_payment' => '-',
                'quotation_payment' => '-',
                'contract_payment' => '-',
                'income_payment' => '-',
                'purchase_payment' => '-',
                'support_document_payment' => '-',
                'remission_payment' => '-',
                'total_income' => '0.00',
                'total_expense' => number_format($exp, 2, ".", "")
            ];

        });

        return $records;
    }

    public function getSumByPMT($records)
    {
        return $records->sum(function($row){
            return $this->calculateTotalCurrencyType($row->associated_record_payment, $row->payment);
        });
    }

    public function getTotalsPaymentMethodType($records_by_pmt, $records_by_emt)
    {

        $t_documents = 0;
        // $t_sale_notes = 0;
        $t_quotations = 0;
        $t_contracts = 0;
        $t_purchases = 0;
        $t_support_documents = 0;
        $t_expenses = 0;
        $t_income = 0;
        $t_remissions = 0;
        $t_document_pos = 0;

        foreach ($records_by_pmt as $value) {
            $t_documents     += is_numeric($value['document_payment'])     ? $value['document_payment']     : 0;
            $t_quotations    += is_numeric($value['quotation_payment'])    ? $value['quotation_payment']    : 0;
            $t_contracts     += is_numeric($value['contract_payment'])     ? $value['contract_payment']     : 0;
            $t_purchases     += is_numeric($value['purchase_payment'])     ? $value['purchase_payment']     : 0;
            $t_support_documents += is_numeric($value['support_document_payment']) ? $value['support_document_payment'] : 0;
            $t_income        += is_numeric($value['income_payment'])       ? $value['income_payment']       : 0;
            $t_remissions    += is_numeric($value['remission_payment'])    ? $value['remission_payment']    : 0;
            $t_document_pos  += is_numeric($value['document_pos_payment']) ? $value['document_pos_payment'] : 0;
        }

        foreach ($records_by_emt as $value) {
            $t_expenses += is_numeric($value['expense_payment']) ? $value['expense_payment'] : 0;
        }

        return [
            't_documents' => number_format($t_documents,2, ".", ""),
            // 't_sale_notes' => number_format($t_sale_notes,2, ".", ""),
            't_quotations' => number_format($t_quotations,2, ".", ""),
            't_contracts' => number_format($t_contracts,2, ".", ""),
            't_purchases' => number_format($t_purchases,2, ".", ""),
            't_support_documents' => number_format($t_support_documents,2, ".", ""),
            't_expenses' => number_format($t_expenses,2, ".", ""),
            't_income' => number_format($t_income,2, ".", ""),
            't_remissions' => number_format($t_remissions,2, ".", ""),
            't_document_pos' => number_format($t_document_pos,2, ".", ""),
            't_total_income' => number_format($t_documents + $t_remissions + $t_document_pos + $t_income, 2, ".", ""),
            't_total_expense' => number_format($t_expenses + $t_purchases + $t_support_documents, 2, ".", "")
        ];

    }

    public function getCurrencies()
    {
        return Currency::get(['id', 'name']);
    }

}
