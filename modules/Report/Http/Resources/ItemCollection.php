<?php

namespace Modules\Report\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ItemCollection extends ResourceCollection
{
     

    public function toArray($request)
    {
        return [
            'data' => $this->collection->transform(function($row, $key){
                $is_pos = isset($row->document_pos);
                $is_remission = isset($row->remission);

                if ($is_pos) {
                    $document = $row->document_pos;
                } elseif ($is_remission) {
                    $document = $row->remission;
                } else {
                    $document = $row->document;
                }

                return [
                    'date_of_issue' => $document && $document->date_of_issue ? $document->date_of_issue->format('Y-m-d') : '',
                    'document_type_description' => $is_remission ? 'REMISIÓN' : ($is_pos ? 'FACT POS' : 'FACT VENTAS'),
                    'series' => $document->series ?? $document->prefix ?? '',
                    'alone_number' => $document->number ?? '',
                    'customer_number' => $document && $document->customer ? $document->customer->number ?? '' : '',
                    'customer_name' => $document && $document->customer ? $document->customer->name ?? '' : '',
                    'quantity' => $row->quantity,
                    'total' => $row->total,
                ];
            }),
            'meta' => [
                'current_page' => $this->currentPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
            ]
        ];
    }
}
