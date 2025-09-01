<?php

namespace Modules\Factcolombia1\Http\Resources\Tenant;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentMethodCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return $this->collection->transform(function($row, $key){
            return [
                'id' => $row->id,
                'name' => $row->name,
                'code' => $row->code,
            ];
        });
    }
}