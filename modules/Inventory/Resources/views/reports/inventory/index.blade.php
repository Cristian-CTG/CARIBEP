@extends('tenant.layouts.app')

@section('content')
<div>
    <div class="page-header pr-0">
        <h2><a href="/reports/inventory">
            <svg  xmlns="http://www.w3.org/2000/svg" style="margin-top: -5px;" width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-building-warehouse"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21v-13l9 -4l9 4v13" /><path d="M13 13h4v8h-10v-6h6" /><path d="M13 21v-9a1 1 0 0 0 -1 -1h-2a1 1 0 0 0 -1 1v3" /></svg>
        </a></h2>
        <ol class="breadcrumbs">
            <li class="active"><span> Consulta de inventarios </span></li>
        </ol>
    </div>
    <div class="row card-table-report">
        <div class="col-md-12">
            <div class="card card-primary">
                {{-- <div class="card-header">
                    <div>
                        <h4 class="card-title">Consulta de inventarios</h4>
                    </div>
                </div> --}}
                <div class="card-body mt-5">
                    <div>
                        <form action="{{route('reports.inventory.search')}}" class="el-form demo-form-inline el-form--inline" method="POST">
                            {{csrf_field()}}
                            {{-- <div class="el-form-item col-xs-12">
                                <div class="el-form-item__content">
                                    <button class="btn btn-custom" type="submit"><i class="fa fa-search"></i> Buscar</button>
                                </div>
                            </div> --}}
                        </form>
                    </div>
                    <div class="">
                        <div class="box-body no-padding">
                            <div style="margin-bottom: 10px" class="row">
                                <div style="padding-top: 0.5%" class="col-md-6">
                                    <form action="{{route('reports.inventory.index')}}" method="get">
                                        {{csrf_field()}}
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="">Establecimiento</label>
                                                <select class="form-control" name="warehouse_id" id="">
                                                    <option {{ request()->warehouse_id == 'all' ?  'selected' : ''}} selected value="all">Todos</option>
                                                    @foreach($warehouses as $item)
                                                    <option {{ request()->warehouse_id == $item->id ?  'selected' : ''}} value="{{$item->id}}">{{$item->description}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="filter">Características</label>
                                                <select class="form-control" id="filter" name="filter">
                                                    <option value="">--</option>
                                                    @foreach ($filter as $group => $items)
                                                        <optgroup label="@lang('app.'.$group)">
                                                            @forelse ($items as $item)
                                                                <option
                                                                    {{ request()->filter ==  $group . '_' . $item['id'] ?  'selected' : ''}}
                                                                    value="{{ $group . '_' . $item['id'] }}">
                                                                    {{ $item['name'] }}
                                                                </option>
                                                            @empty
                                                                <option disabled>No hay @lang('app.'.$group) disponibles</option>
                                                            @endforelse
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="filter">Fecha</label>
                                                <input name="date" value="{{ request()->date ? request()->date : ''}}" type="text" data-plugin-datepicker class="form-control">
                                            </div>
                                            <div class="col-md-4"> <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Buscar</button></div>
                                        </div>
                                    </form>
                                </div>
                                @if(!empty($reports) && $reports->count())
                                    @if(isset($reports))
                                        <div class="col-md-4">
                                            <form action="{{route('reports.inventory.pdf')}}" class="d-inline" method="POST">
                                                {{csrf_field()}}
                                                <input type="hidden" name="warehouse_id" value="{{request()->warehouse_id ? request()->warehouse_id : 'all'}}">
                                                <input type="hidden" name="filter" value="{{request()->filter ? request()->filter : ''}}">
                                                <input type="hidden" name="date" value="{{request()->date ? request()->date : ''}}">
                                                <button class="btn btn-custom   mt-2 mr-2" type="submit"><i class="fa fa-file-pdf"></i> Exportar PDF</button>
                                                {{-- <label class="pull-right">Se encontraron {{$reports->count()}} registros.</label> --}}
                                            </form>

                                            <form action="{{route('reports.inventory.report_excel')}}" class="d-inline" method="POST">
                                                {{csrf_field()}}
                                                <input type="hidden" name="warehouse_id" value="{{request()->warehouse_id ? request()->warehouse_id : 'all'}}">
                                                <input type="hidden" name="filter" value="{{request()->filter ? request()->filter : ''}}">
                                                <input type="hidden" name="date" value="{{request()->date ? request()->date : ''}}">
                                                <button class="btn btn-custom   mt-2 mr-2" type="submit"><i class="fa fa-file-excel"></i> Exportar Excel</button>
                                                {{-- <label class="pull-right">Se encontraron {{$reports->count()}} registros.</label> --}}
                                            </form>
                                        </div>

                                    @endif
                                @endif
                            </div>
                            <div class="table-responsive">
                            <table width="100%" class="table">
                                <thead class="">
                                    <tr>
                                        {{-- <th>#</th> --}}
                                        <th>Descripción</th>
                                        <th>Inventario actual</th>
                                        <th>Precio de venta</th>
                                        <th>Costo</th>
                                        <th>Almacén</th>

                                        <th class="text-right">
                                            Precio de venta Global
                                            <el-tooltip class="item" effect="dark" content="Precio de venta * Inventario actual (Stock)" placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </th>
                                        <th class="text-right">
                                            Costo Global
                                            <el-tooltip class="item" effect="dark" content="Costo * Inventario actual (Stock)" placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </th>

                                    </tr>
                                </thead>
                                <tbody>

                                    {{-- @php
                                        $total_global_sale_unit_price = 0;
                                        $total_global_purchase_unit_price = 0;
                                    @endphp --}}
                                    @if(!empty($reports) && $reports->count())
                                        @foreach($reports as $key => $value)

                                            @php
                                                $global_sale_unit_price = $value->getGlobalSaleUnitPrice();
                                                $global_purchase_unit_price = $value->getGlobalPurchaseUnitPrice();
                                                $item_prices = $value->item->item_unit_types;
                                            @endphp

                                            <tr>
                                                {{-- <td class="celda">{{$loop->iteration}}</td> --}}
                                                <td class="celda">{{$value->item->internal_id ?? ''}} {{$value->item->internal_id ? '-':''}} {{$value->item->name ?? ''}}</td>
                                                <td class="celda">{{number_format($value->stock, 0, '.', ',')}}</td>
                                                <td class="celda">
                                                    {{number_format($value->item->sale_unit_price, 2, '.', ',')}}
                                                    @if($item_prices->count() > 0)
                                                        @foreach($item_prices as $price)
                                                            @if($price->price1 > 0)<br>Precio1: {{number_format($price->price1, 2, '.', ',')}}@endif
                                                            @if($price->price2 > 0)<br>Precio2: {{number_format($price->price2, 2, '.', ',')}}@endif
                                                            @if($price->price3 > 0)<br>Precio3: {{number_format($price->price3, 2, '.', ',')}}@endif
                                                        @endforeach
                                                    @endif
                                                </td>
                                                <td class="celda">{{number_format($value->item->purchase_unit_price, 2, '.', ',')}}</td>
                                                <td class="celda">{{$value->warehouse->description}}</td>

                                                <td class="celda text-right">{{number_format($global_sale_unit_price, 2, '.', ',')}}</td>
                                                <td class="celda text-right">{{number_format($global_purchase_unit_price, 2, '.', ',')}}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="8">No se encontraron registros</td>
                                        </tr>
                                    @endif
                                    {{-- <tr>
                                        <td class="celda" colspan="5"></td>
                                        <td class="celda">Total</td>
                                        <td class="celda text-right">{{ number_format($total_global_sale_unit_price, 6, ".", "") }}</td>
                                        <td class="celda text-right">{{ number_format($total_global_purchase_unit_price, 6, ".", "") }}</td>
                                    </tr> --}}

                                </tbody>
                            </table>
                            </div>                            
                            Total {{$reports->total()}}
                            <label class="pagination-wrapper ml-2">
                                {{$reports->appends($_GET)->render()}}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>    
@endsection

@push('scripts')
    <script>
        // Datepicker
        (function($) {

        'use strict';

        $(document).ready(function () {
            // Inicializar Bootstrap Datepicker
            $('[data-plugin-datepicker]').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true,
                language: 'es'
            });
        });

        // if ( $.isFunction($.fn[ 'bootstrapDP' ]) ) {

        //     $(function() {
        //         $('[data-plugin-datepicker]').each(function() {
        //             var $this = $( this ),
        //                 opts = {};

        //             var pluginOptions = $this.data('plugin-options');
        //             if (pluginOptions)
        //                 opts = pluginOptions;

        //             $this.themePluginDatePicker(opts);
        //         });
        //     });

        // }

        }).apply(this, [jQuery]);
    </script>
@endpush
