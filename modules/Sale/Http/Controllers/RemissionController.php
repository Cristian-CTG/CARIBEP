<?php

namespace Modules\Sale\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Item;
use App\Models\Tenant\Person;
use App\Models\Tenant\Establishment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\CoreFacturalo\Requests\Inputs\Common\PersonInput;
use App\CoreFacturalo\Requests\Inputs\Common\EstablishmentInput;
use App\CoreFacturalo\Helpers\Storage\StorageDocument;
use App\CoreFacturalo\Template;
use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Exception;
use Modules\Sale\Models\Remission;
use Modules\Sale\Http\Resources\RemissionCollection;
use Modules\Sale\Http\Resources\RemissionResource;
use Modules\Sale\Http\Requests\RemissionRequest;
use Modules\Factcolombia1\Models\Tenant\{
    Currency,
    Tax,
    PaymentMethod,
    PaymentForm,
    Company
};
use App\Http\Controllers\Tenant\{
    PersonController,
    ItemController,
};
use Modules\Factcolombia1\Helpers\DocumentHelper;
use App\Models\Tenant\ItemWarehouse;
use App\Models\Tenant\InventoryKardex;
use Carbon\Carbon;


class RemissionController extends Controller
{

    use StorageDocument;

    protected $remission;

    public function index()
    {
        return view('sale::co-remissions.index');
    }

    public function create($id = null)
    {
        return view('sale::co-remissions.form', compact('id'));
    }

    public function columns()
    {
        return [
            'date_of_issue' => 'Fecha de emisión',
            'number' => 'Número',
            'prefix' => 'Prefijo',
            'customer' => 'Cliente'
        ];
    }

    public function records(Request $request)
    {

        $records = Remission::where($request->column, 'like', "%{$request->value}%")
                                ->latest();

        return new RemissionCollection($records->paginate(config('tenant.items_per_page')));
    }


    public function tables()
    {
        $customers = $this->table('customers');
        $payment_methods = PaymentMethod::all();
        $payment_forms = PaymentForm::all();
        $currencies = Currency::all();
        $taxes = $this->table('taxes');

        return compact('customers', 'payment_methods', 'payment_forms', 'currencies', 'taxes');
    }


    public function item_tables()
    {
        $items = $this->table('items');
        $taxes = $this->table('taxes');

        return compact('items', 'taxes');
    }


    public function table($table)
    {

        if ($table === 'customers') {
            $persons = app(PersonController::class)->searchCustomers(new Request());
            return $persons['customers'];
        }

        if ($table === 'taxes') {
            return Tax::all()->transform(function($row) {
                return $row->getSearchRowResource();
            });
        }

        if ($table === 'items') {
            $items = app(ItemController::class)->searchItems(new Request());
            return $items['items'];
        }

    }


    public function record($id)
    {
        $record = new RemissionResource(Remission::findOrFail($id));

        return $record;
    }


    public function store(RemissionRequest $request) {

        DB::connection('tenant')->transaction(function () use ($request) {

            $data = $this->mergeData($request);

            $this->remission =  Remission::updateOrCreate( ['id' => $request->input('id')], $data);

            $this->remission->items()->delete();

            foreach ($data['items'] as $row) {
                $this->remission->items()->create($row);
            }

            $this->setFilename();
            $this->createPdf();

        });

        return [
            'success' => true,
            'data' => [
                'id' => $this->remission->id,
            ],
        ];
    }


    public function mergeData($inputs)
    {

        $establishment_id = auth()->user()->establishment_id;
        $items = DocumentHelper::getDataItemFromInputs($inputs);
        // dd($items);

        $values = [
            'user_id' => auth()->id(),
            'external_id' => ($inputs->id) ? $inputs->external_id : Str::uuid()->toString(),
            'customer' => Person::with('typePerson', 'typeRegime', 'identity_document_type', 'country', 'department', 'city')->findOrFail($inputs['customer_id']),
            'establishment' => EstablishmentInput::set($establishment_id),
            'establishment_id' => $establishment_id,
            'state_type_id' => '01',
            'number' => $this->getNumber($inputs->prefix),
            'items' => $items,
            'seller_id' => $inputs['seller_id'] ?? null,
        ];

        $inputs->merge($values);

        return $inputs->all();
    }

    /**
     * Obtener ultimo numero correlativo
     *
     * @return int
     */
    private function getNumber($prefix)
    {
        $remission = Remission::where('prefix', $prefix)->select('number')->latest()->first();

        return ($remission) ? (int) $remission->number + 1 : 1;
    }


    private function setFilename()
    {
        $name = [$this->remission->prefix,$this->remission->number,date('Ymd')];
        $this->remission->filename = join('-', $name);
        $this->remission->save();
    }


    public function toPrint($external_id, $format)
    {
        $remission = Remission::where('external_id', $external_id)->first();
        if (!$remission) throw new Exception("El código {$external_id} es inválido, no se encontro el registro relacionado");

        $this->createPdf($remission, $format, $remission->filename);
        $temp = tempnam(sys_get_temp_dir(), 'remission');

        file_put_contents($temp, $this->getStorage($remission->filename, 'remission'));

        return response()->file($temp);
    }



    public function createPdf($remission = null, $format_pdf = null, $filename = null)
    {
        ini_set("pcre.backtrack_limit", "5000000");
        $template = new Template();
        
        $document = ($remission != null) ? $remission : $this->remission;
        $company = Company::first();
        $filename = ($filename != null) ? $filename : $this->remission->filename;
        $format_pdf = ($format_pdf != null) ? $format_pdf : 'a4';
        $base_template = config('tenant.pdf_template');

        // Agregar cuentas bancarias al documento
        $bank_accounts = \App\Models\Tenant\BankAccount::where('status', 1)->get();
        $document->bank_accounts = $bank_accounts;

        // Configuración inicial del PDF según formato
        $pdf_config = [];
        if ($format_pdf === 'ticket') {
            $pdf_config = [
                'mode' => 'utf-8',
                'format' => [80, 297],
                'margin_top' => 0,
                'margin_right' => 2,
                'margin_bottom' => 0,
                'margin_left' => 2
            ];
        }

        // Crear instancia de PDF con la configuración correspondiente
        $pdf = new Mpdf($pdf_config);

        $html = $template->pdf($base_template, "remission", $company, $document, $format_pdf);

        // Configurar fuentes solo para formato A4
        if ($format_pdf === 'a4' && config('tenant.pdf_name_regular')) {
            $defaultConfig = (new ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];
            $defaultFontConfig = (new FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];

            $pdf = new Mpdf([
                'fontDir' => array_merge($fontDirs, [
                    app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.
                                                DIRECTORY_SEPARATOR.'pdf'.
                                                DIRECTORY_SEPARATOR.$base_template.
                                                DIRECTORY_SEPARATOR.'font')
                ]),
                'fontdata' => $fontData + [
                    'custom_bold' => [
                        'R' => config('tenant.pdf_name_bold').'.ttf',
                    ],
                    'custom_regular' => [
                        'R' => config('tenant.pdf_name_regular').'.ttf',
                    ],
                ]
            ]);
        }

        $path_css = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.
                                             DIRECTORY_SEPARATOR.'pdf'.
                                             DIRECTORY_SEPARATOR.$base_template.
                                             DIRECTORY_SEPARATOR.'co_custom_styles.css');

        $stylesheet = file_get_contents($path_css);

        $pdf->WriteHTML($stylesheet, HTMLParserMode::HEADER_CSS);
        $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        // Agregar footer solo en formato A4
        if ($format_pdf === 'a4' && config('tenant.pdf_template_footer')) {
            $html_footer = $template->pdfFooter($base_template);
            $pdf->SetHTMLFooter($html_footer);
        }

        $this->uploadFile($filename, $pdf->output('', 'S'), 'remission');
    }


    public function uploadFile($filename, $file_content, $file_type)
    {
        $this->uploadStorage($filename, $file_content, $file_type);
    }


    public function download($external_id, $format = 'a4')
    {
        $remission = Remission::where('external_id', $external_id)->first();

        if (!$remission) throw new Exception("El código {$external_id} es inválido, no se encontro el documento relacionado");

        return $this->downloadStorage($remission->filename, 'remission');
    }

    public function voided($id)
    {
        $remission = Remission::with('items')->findOrFail($id);

        if ($remission->state_type_id == '11') {
            return [
                'success' => false,
                'message' => 'La remisión ya está anulada.'
            ];
        }

        DB::connection('tenant')->transaction(function () use ($remission) {
            // Cambiar estado a anulado
            $remission->state_type_id = '11'; // 11 = Anulado
            $remission->save();

            foreach ($remission->items as $item) {
                $warehouse_id = $item->warehouse_id;
                if (empty($warehouse_id)) {
                    $mainWarehouse = ItemWarehouse::where('item_id', $item->item_id)->orderBy('id')->first();
                    if ($mainWarehouse) {
                        $warehouse_id = $mainWarehouse->warehouse_id;
                    }
                }
                if ($warehouse_id) {
                    // Actualizar stock en el almacén correspondiente
                    $itemWarehouse = ItemWarehouse::where('item_id', $item->item_id)
                        ->where('warehouse_id', $warehouse_id)
                        ->first();

                    if ($itemWarehouse) {
                        $itemWarehouse->stock += $item->quantity;
                        $itemWarehouse->save();
                    }

                    // Registrar entrada en inventory_kardex
                    InventoryKardex::create([
                        'date_of_issue' => now(),
                        'item_id' => $item->item_id,
                        'inventory_kardexable_id' => $remission->id,
                        'inventory_kardexable_type' => Remission::class,
                        'warehouse_id' => $warehouse_id,
                        'quantity' => $item->quantity,
                    ]);
                } else {
                    // Si no se encuentra ningún almacén, registrar en el log
                    \Log::warning("No se encontró almacén para el item {$item->item_id} en la remisión {$remission->id}. No se actualizó el stock ni el kardex.");
                }
            }
        });

        return [
            'success' => true,
            'message' => 'Remisión anulada correctamente.'
        ];
    }

}
