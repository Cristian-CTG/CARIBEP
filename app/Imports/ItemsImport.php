<?php

namespace App\Imports;

use App\Models\Tenant\Item;
use App\Models\Tenant\Warehouse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Modules\Item\Models\Category;
use Modules\Item\Models\Brand;
use Modules\Item\Models\Color;
use Modules\Item\Models\Size;


class ItemsImport implements ToCollection
{
    use Importable;

    protected $data;

    public function collection(Collection $rows)
    {
        $total = count($rows);
        $registered = 0;
        $skipped = 0;
        unset($rows[0]);
        foreach ($rows as $row)
        {
            // Rellenar la fila hasta 14 columnas con valores vacíos
            $row = array_pad($row->toArray(), 14, '');

            $name = $row[0];
            $item_type_id = '01';
            $internal_id = ($row[1])?:null;
            $unit_type_id = $row[2];
            $currency_type_id = isset($row[3]) && $row[3] !== '' ? $row[3] : 170;
            $sale_unit_price = $row[4];
            $tax_id = $row[5];
            $purchase_unit_price = ($row[6])?:0;
            $purchase_tax_id = ($row[7])?:1;
            $stock = (isset($row[8]) && trim($row[8]) !== '') ? $row[8] : 0;
            $stock_min = (isset($row[9]) && trim($row[9]) !== '') ? $row[9] : 1;
            $category_name = $row[10];
            $brand_name = $row[11];
            $color_name = $row[12];
            $size_name = $row[13];

            // Validar campos obligatorios
            if (empty($name) || empty($unit_type_id) || empty($sale_unit_price) || empty($tax_id) || empty($internal_id) || floatval($sale_unit_price) <= 0) {
                $skipped++;
                continue;
            }

            $item = Item::where('internal_id', $internal_id)->first();

            if(!$item) {
                $category = !empty($category_name) ? Category::updateOrCreate(['name' => $category_name]) : null;
                $brand = !empty($brand_name) ? Brand::updateOrCreate(['name' => $brand_name]) : null;
                $color = !empty($color_name) ? Color::updateOrCreate(['name' => $color_name]) : null;
                $size = !empty($size_name) ? Size::updateOrCreate(['name' => $size_name]) : null;

                Item::create([
                    'name' => $name,
                    'item_type_id' => $item_type_id,
                    'internal_id' => $internal_id,
                    'unit_type_id' => $unit_type_id,
                    'currency_type_id' => $currency_type_id,
                    'sale_unit_price' => $sale_unit_price,
                    'tax_id' => $tax_id,
                    'purchase_unit_price' => $purchase_unit_price,
                    'purchase_tax_id' => $purchase_tax_id,
                    'stock' => $stock,
                    'stock_min' => $stock_min,
                    'category_id' => $category ? $category->id : null,
                    'brand_id' => $brand ? $brand->id : null,
                    'color_id' => $color ? $color->id : null,
                    'size_id' => $size ? $size->id : null,
                ]);
                $registered += 1;
            } else {
                $item->update([
                    'name' => $name,
                    'item_type_id' => $item_type_id,
                    'internal_id' => $internal_id,
                    'unit_type_id' => $unit_type_id,
                    'currency_type_id' => $currency_type_id,
                    'sale_unit_price' => $sale_unit_price,
                    'tax_id' => $tax_id,
                    'purchase_unit_price' => $purchase_unit_price,
                    'purchase_tax_id' => $purchase_tax_id,
                    'stock_min' => $stock_min,
                ]);
                $registered += 1;
            }
        }
        $this->data = compact('total', 'registered', 'skipped');
    }

    public function getData()
    {
        return $this->data;
    }

}
