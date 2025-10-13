@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;
    $path = explode('/', request()->path());
    $path[1] = (array_key_exists(1, $path)> 0)?$path[1]:'';
    $path[2] = (array_key_exists(2, $path)> 0)?$path[2]:'';
    $path[0] = ($path[0] === '')?'documents':$path[0];
    $advanced_config = null; // Inicializa la variable
    $tenantConnection = config('tenancy.db.tenant_connection', 'tenant');
    if (Schema::connection($tenantConnection)->hasTable('co_advanced_configuration')) {
        $advanced_config = \Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration::first();
    } else {
        \Log::error('ERROR: La tabla co_advanced_configuration NO existe en la base de datos del tenant');
    }
@endphp
<aside id="sidebar-left" class="sidebar-left">
    <div class="sidebar-header">
        <a href="{{route('tenant.dashboard.index')}}"
           class="logo logo-contain pt-2 pt-md-0">
            @if($vc_company->logo)
                <img src="{{ asset('storage/uploads/logos/'.$vc_company->logo) }}"
                     alt="Logo"/>
            @else
                <img src="{{asset('logo/tulogo.png')}}"
                     alt="Logo"/>
            @endif
        </a>
        <div class="d-md-none toggle-sidebar-left"
            data-toggle-class="sidebar-left-opened"
            data-target="html"
            data-fire-event="sidebar-left-opened">
            <i class="fas fa-bars"
               aria-label="Toggle sidebar"></i>
        </div>
    </div>
    <div class="nano">
        <div class="nano-content">
            <nav id="menu" class="nav-main" role="navigation">
                <ul class="nav nav-main">
                    @if(in_array('dashboard', $vc_modules))
                    <li class="{{ ($path[0] === 'dashboard')?'nav-active':'' }}">
                        <a class="nav-link" href="{{ route('tenant.dashboard.index') }}">
                            {{-- <span class="float-right badge badge-red badge-danger mr-3">Nuevo</span> --}}
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-dashboard">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M12 13m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                <path d="M13.45 11.55l2.05 -2.05"></path>
                                <path d="M6.4 20a9 9 0 1 1 11.2 0z"></path>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    @endif

                    @if(in_array('documents', $vc_modules))
                    <li class="
                        nav-parent
                        {{ ($path[0] === 'documents')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'summaries')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'voided')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'quotations')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'sale-notes')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'contingencies')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'person-types')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'incentives')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'order-notes')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'sale-opportunities')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'contracts')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'production-orders')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'technical-services')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'user-commissions')?'nav-active nav-expanded':'' }}

                        {{ ($path[0] === 'co-documents')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'co-taxes')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'co-documents-aiu')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'co-documents-health')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'co-remissions')?'nav-active nav-expanded':'' }}
                        ">
                        <a class="nav-link" href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <span>Ventas</span>
                        </a>
                        <ul class="nav nav-children" style="">
                            @if(auth()->user()->type != 'integrator' && $vc_company->soap_type_id != '03')
                                @if(in_array('documents', $vc_modules))
                                    @if(in_array('new_document', $vc_module_levels))
                                        <li class="{{ ($path[0] === 'co-documents'  && $path[1] === 'create')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.co-documents.create')}}">
                                                Factura Electronica
                                            </a>
                                        </li>

                                        @if(in_array('invoicehealth', $vc_modules))
                                            <li class="{{ ($path[0] === 'co-documents-health'  && $path[1] === 'create')?'nav-active':'' }}">
                                                <a class="nav-link" href="{{route('tenant.co-documents-health.create')}}">
                                                    Factura Salud
                                                </a>
                                            </li>
                                        @endif

                                        <li class="{{ ($path[0] === 'co-documents-aiu'  && $path[1] === 'create')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.co-documents-aiu.create')}}">
                                                Factura AIU
                                            </a>
                                        </li>

                                        <li class="{{ ($path[0] === 'co-documents-unreferenced-note'  && $path[1] === 'create')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.co-documents-unreferenced-note.create')}}">
                                                Nota Contable (sin ref.)
                                            </a>
                                        </li>
                                        {{-- <li class="{{ ($path[0] === 'documents' && $path[1] === 'create')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.documents.create')}}">
                                                Nuevo comprobante electrónico
                                            </a>
                                        </li> --}}
                                    @endif
                                @endif
                            @endif

                            @if(in_array('documents', $vc_modules) && $vc_company->soap_type_id != '03')

                                @if(in_array('list_document', $vc_module_levels))
                                    {{-- <li class="{{ ($path[0] === 'documents' && $path[1] != 'create' && $path[1] != 'not-sent')?'nav-active':'' }}">
                                        <a class="nav-link" href="{{route('tenant.documents.index')}}">
                                            Listado de comprobantes
                                        </a>
                                    </li> --}}

                                    <li class="{{ ($path[0] === 'co-documents'  && $path[1] != 'create'  )?'nav-active':'' }}">
                                        <a class="nav-link" href="{{route('tenant.co-documents.index')}}">
                                            Mis comprobantes
                                        </a>
                                    </li>
                                @endif

                            @endif

                            {{-- @if(in_array('documents', $vc_modules) && $vc_company->soap_type_id != '03')

                                @if(in_array('document_not_sent', $vc_module_levels))
                                    <li class="{{ ($path[0] === 'documents' && $path[1] === 'not-sent')?'nav-active':'' }}">
                                        <a class="nav-link" href="{{route('tenant.documents.not_sent')}}">
                                            Comprobantes no enviados
                                        </a>
                                    </li>
                                @endif

                            @endif --}}

                            @if(auth()->user()->type != 'integrator' && in_array('documents', $vc_modules) )

                                {{-- @if(auth()->user()->type != 'integrator' && in_array('document_contingengy', $vc_module_levels) && $vc_company->soap_type_id != '03')
                                <li class="{{ ($path[0] === 'contingencies' )?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.contingencies.index')}}">
                                        Documentos de contingencia
                                    </a>
                                </li>
                                @endif --}}

                                {{-- Catálogos removed: Productos moved to a top-level 'Productos' menu below Clientes --}}

                                {{-- @if(in_array('summary_voided', $vc_module_levels) && $vc_company->soap_type_id != '03')

                                    <li class="nav-parent
                                        {{ ($path[0] === 'summaries')?'nav-active nav-expanded':'' }}
                                        {{ ($path[0] === 'voided')?'nav-active nav-expanded':'' }}
                                        ">
                                        <a class="nav-link" href="#">
                                            Resúmenes y Anulaciones
                                        </a>
                                        <ul class="nav nav-children">
                                            <li class="{{ ($path[0] === 'summaries')?'nav-active':'' }}">
                                                <a class="nav-link" href="{{route('tenant.summaries.index')}}">
                                                    Resúmenes
                                                </a>
                                            </li>
                                            <li class="{{ ($path[0] === 'voided')?'nav-active':'' }}">
                                                <a class="nav-link" href="{{route('tenant.voided.index')}}">
                                                    Anulaciones
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif --}}

                                {{-- <li class="{{ ($path[0] === 'sale-opportunities')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.sale_opportunities.index')}}">
                                        Oportunidad de venta
                                    </a>
                                </li> --}}

                                @if(in_array('quotations', $vc_module_levels))

                                    <li class="{{ ($path[0] === 'quotations')?'nav-active':'' }}">
                                        <a class="nav-link" href="{{route('tenant.quotations.index')}}">
                                            Cotizaciones
                                        </a>
                                    </li>
                                @endif

                                @if(auth()->user()->type != 'integrator' && in_array('documents', $vc_modules))
                                <li class="{{ ($path[0] === 'co-remissions')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.co-remissions.index')}}">
                                        Remisiones
                                    </a>
                                </li>
                                @endif

                                {{-- <li class="nav-parent
                                    {{ ($path[0] === 'contracts')?'nav-active nav-expanded':'' }}
                                    {{ ($path[0] === 'production-orders')?'nav-active nav-expanded':'' }}
                                    ">
                                    <a class="nav-link" href="#">
                                        Contratos
                                    </a>
                                    <ul class="nav nav-children">
                                        <li class="{{ ($path[0] === 'contracts')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.contracts.index')}}">
                                                Listado
                                            </a>
                                        </li>
                                        <li class="{{ ($path[0] === 'production-orders')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.production_orders.index')}}">
                                                Ordenes de Producción
                                            </a>
                                        </li>
                                    </ul>
                                </li> --}}


                                {{-- <li class="{{ ($path[0] === 'order-notes')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.order_notes.index')}}">
                                        Pedidos
                                    </a>
                                </li> --}}

                                {{--@if(in_array('sale_notes', $vc_module_levels))

                                    <li class="{{ ($path[0] === 'sale-notes')?'nav-active':'' }}">
                                        <a class="nav-link" href="{{route('tenant.sale_notes.index')}}">
                                            Notas de Venta
                                        </a>
                                    </li>
                                @endif --}}

                              {{--  <li class="{{ ($path[0] === 'technical-services')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.technical_services.index')}}">
                                        Servicio de soporte técnico
                                    </a>
                                </li> --}}

                                @if(in_array('incentives', $vc_module_levels))

                                    <li class="nav-parent
                                        {{ ($path[0] === 'incentives')?'nav-active nav-expanded':'' }}
                                        {{ ($path[0] === 'user-commissions')?'nav-active nav-expanded':'' }}
                                        ">
                                        <a class="nav-link" href="#">
                                            Comisiones
                                        </a>
                                        <ul class="nav nav-children">
                                            <li class="{{ ($path[0] === 'user-commissions')?'nav-active':'' }}">
                                                <a class="nav-link" href="{{route('tenant.user_commissions.index')}}">
                                                    Vendedores
                                                </a>
                                            </li>
                                            <li class="{{ ($path[0] === 'incentives')?'nav-active':'' }}">
                                                <a class="nav-link" href="{{route('tenant.incentives.index')}}">
                                                    Productos
                                                </a>
                                            </li>
                                        </ul>
                                    </li>

                                @endif

                                <li class="{{ ($path[0] === 'co-coupon')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.co-coupon.index')}}">
                                        Cupones
                                    </a>
                                </li>

                                @php
                                    $advanced_config = \Modules\Factcolombia1\Models\TenantService\AdvancedConfiguration::first();
                                @endphp

                                @if($advanced_config && $advanced_config->enable_seller_views)
                                    <li class="{{ ($path[0] === 'co-sellers')?'nav-active':'' }}">
                                        <a class="nav-link" href="{{route('tenant.co-sellers.index')}}">
                                            Vendedores
                                        </a>
                                    </li>
                                @endif

                            @endif

                        </ul>
                    </li>
                    @endif

                    @if(auth()->user()->type != 'integrator')
                        @if(in_array('pos', $vc_modules))
                        <li class="
                        nav-parent
                        {{ ($path[0] === 'pos')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'cash')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'item-sets')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'document-pos')?'nav-active nav-expanded':'' }}
                        ">
                            <a class="nav-link" href="#">
                                {{-- <span class="float-right badge badge-red badge-danger mr-3">Nuevo</span> --}}
                                <svg  xmlns="http://www.w3.org/2000/svg"  width="20"  height="20"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-cash-register"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M21 15h-2.5c-.398 0 -.779 .158 -1.061 .439c-.281 .281 -.439 .663 -.439 1.061c0 .398 .158 .779 .439 1.061c.281 .281 .663 .439 1.061 .439h1c.398 0 .779 .158 1.061 .439c.281 .281 .439 .663 .439 1.061c0 .398 -.158 .779 -.439 1.061c-.281 .281 -.663 .439 -1.061 .439h-2.5" /><path d="M19 21v1m0 -8v1" /><path d="M13 21h-7c-.53 0 -1.039 -.211 -1.414 -.586c-.375 -.375 -.586 -.884 -.586 -1.414v-10c0 -.53 .211 -1.039 .586 -1.414c.375 -.375 .884 -.586 1.414 -.586h2m12 3.12v-1.12c0 -.53 -.211 -1.039 -.586 -1.414c-.375 -.375 -.884 -.586 -1.414 -.586h-2" /><path d="M16 10v-6c0 -.53 -.211 -1.039 -.586 -1.414c-.375 -.375 -.884 -.586 -1.414 -.586h-4c-.53 0 -1.039 .211 -1.414 .586c-.375 .375 -.586 .884 -.586 1.414v6m8 0h-8m8 0h1m-9 0h-1" /><path d="M8 14v.01" /><path d="M8 17v.01" /><path d="M12 13.99v.01" /><path d="M12 17v.01" /></svg>
                                <span>Punto de Venta P.O.S.</span>
                            </a>
                            <ul class="nav nav-children">
                                <li class="{{ ($path[0] === 'pos'  )?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.pos.index')}}">
                                        Punto de venta
                                    </a>
                                </li>
                                <li class="{{ ($path[0] === 'cash'  )?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.cash.index')}}">
                                        Caja
                                    </a>
                                </li>
                                <li class="{{ ($path[0] === 'item-sets'  )?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.item_sets.index')}}">
                                        Conjuntos/Packs/Promociones
                                    </a>
                                </li>
                                @if(auth()->user()->type == 'admin')
                                    <li class="{{ ($path[0] === 'item-sets'  )?'nav-active':'' }}">
                                        <a class="nav-link" href="{{route('tenant.pos.configuration')}}">
                                            Configuración
                                        </a>
                                    </li>
                                @endif
                                <li class="{{ ($path[0] === 'document-pos'  )?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.document_pos.index')}}">
                                        Lista Documentos
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif
                        {{-- Nuevo menú Contactos con clientes y proveedores --}}
                        @if(auth()->user()->type != 'integrator' && (in_array('contacts', $vc_modules) ))
                        <li class="nav-parent
                            {{ ($path[0] === 'persons' && in_array($path[1], ['customers', 'suppliers'])) ? 'nav-active nav-expanded' : '' }}
                            ">
                            <a class="nav-link" href="#">
                                <svg  xmlns="http://www.w3.org/2000/svg"  width="20"  height="20"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-address-book"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 6v12a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2z" /><path d="M10 16h6" /><path d="M13 11m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M4 8h3" /><path d="M4 12h3" /><path d="M4 16h3" /></svg>
                                <span>Contactos</span>
                            </a>
                            <ul class="nav nav-children">
                                @if(in_array('contacts', $vc_modules))
                                <li class="{{ ($path[0] === 'persons' && $path[1] === 'suppliers') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{route('tenant.persons.index', ['type' => 'suppliers'])}}">
                                        Proveedores
                                    </a>
                                </li>
                                <li class="{{ ($path[0] === 'persons' && $path[1] === 'customers') ? 'nav-active' : '' }}">
                                    <a class="nav-link" href="{{route('tenant.persons.index', ['type' => 'customers'])}}">
                                        Clientes
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </li>
                        @endif

                        {{-- Nuevo menú Productos (antes en Catálogos) colocado debajo de Clientes --}}
                        @if(in_array('products', $vc_modules) && auth()->user()->type != 'integrator')
                        <li class="nav-parent
                            {{ ($path[0] === 'items')?'nav-active nav-expanded':'' }}
                            {{ ($path[0] === 'categories')?'nav-active nav-expanded':'' }}
                            {{ ($path[0] === 'brands')?'nav-active nav-expanded':'' }}
                            ">
                            <a class="nav-link" href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-category-2">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M14 4h6v6h-6z"></path>
                                    <path d="M4 14h6v6h-6z"></path>
                                    <path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                    <path d="M7 7m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                </svg>
                                <span>Productos</span>
                            </a>
                            <ul class="nav nav-children">
                                <li class="{{ ($path[0] === 'items')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.items.index')}}">
                                        Productos
                                    </a>
                                </li>
                                <li class="{{ ($path[0] === 'categories')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.categories.index')}}">
                                        Categorías
                                    </a>
                                </li>
                                <li class="{{ ($path[0] === 'brands')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.brands.index')}}">
                                        Marcas
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif
                    @endif


                    @if(in_array('ecommerce', $vc_modules))
                    <li class="nav-parent {{ in_array($path[0], ['ecommerce','items_ecommerce', 'tags', 'promotions', 'orders', 'configuration'])?'nav-active nav-expanded':'' }}">
                        <a class="nav-link" href="#">
                            {{-- <span class="float-right badge badge-red badge-danger mr-3">Nuevo</span> --}}
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-cart">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                <path d="M17 17h-11v-14h-2"></path>
                                <path d="M6 5l14 1l-1 7h-13"></path>
                            </svg>
                            <span>Tienda Virtual</span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="">
                                <a class="nav-link" onclick="window.open( '{{ route("tenant.ecommerce.index") }} ')">
                                    Ir a Tienda
                                </a>
                            </li>
                            <li class="{{ ($path[0] === 'orders')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant_orders_index')}}">
                                    Pedidos
                                </a>
                            </li>
                            <li class="{{ ($path[0] === 'items_ecommerce')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.items_ecommerce.index')}}">
                                    Productos Tienda Virtual
                                </a>
                            </li>
                            <li class="{{ ($path[0] === 'tags')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.tags.index')}}">
                                    Tags - Categorias
                                </a>
                            </li>
                            <li class="{{ ($path[0] === 'promotions')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.promotion.index')}}">
                                    Promociones
                                </a>
                            </li>
                            <li class="{{ ($path[1] === 'configuration')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant_ecommerce_configuration')}}">
                                    Configuración
                                </a>
                            </li>

                        </ul>
                    </li>
                    @endif

                    @if(auth()->user()->type != 'integrator')

                        @if(in_array('purchases', $vc_modules))
                        <li class="
                            nav-parent
                            {{ ($path[0] === 'purchases')?'nav-active nav-expanded':'' }}
                            {{ ($path[0] === 'expenses')?'nav-active nav-expanded':'' }}
                            {{ ($path[0] === 'purchase-quotations')?'nav-active nav-expanded':'' }}
                            {{ ($path[0] === 'purchase-orders')?'nav-active nav-expanded':'' }}
                            {{ ($path[0] === 'fixed-asset')?'nav-active nav-expanded':'' }}
                            {{ ($path[0] === 'support-documents')?'nav-active nav-expanded':'' }}
                            {{ ($path[0] === 'support-document-adjust-notes')?'nav-active nav-expanded':'' }}
                            ">
                            <a class="nav-link" href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-bag">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z"></path>
                                    <path d="M9 11v-5a3 3 0 0 1 6 0v5"></path>
                                </svg>
                                <span>Compras</span>
                            </a>
                            <ul class="nav nav-children" style="">



                                <li class="{{ ($path[0] === 'purchases' && $path[1] === 'create')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.purchases.create')}}">
                                        Nuevo
                                    </a>
                                </li>

                                <li class="{{ ($path[0] === 'purchases' && $path[1] != 'create')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.purchases.index')}}">
                                        Listado
                                    </a>
                                </li>

                                <li class="{{ ($path[0] === 'purchase-orders')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.purchase-orders.index')}}">
                                        Ordenes de compra
                                    </a>
                                </li>
                                <li class="{{ ($path[0] === 'expenses' )?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.expenses.index')}}">
                                        Gastos diversos
                                    </a>
                                </li>

                                <li class="{{ ($path[0] === 'purchase-quotations')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('tenant.purchase-quotations.index')}}">
                                        Solicitar cotización
                                    </a>
                                </li>

                                {{-- documento de soporte --}}

                                <li class="nav-parent
                                    {{ ($path[0] === 'support-documents')?'nav-active nav-expanded':'' }}
                                    {{ ($path[0] === 'support-document-adjust-notes')?'nav-active nav-expanded':'' }}
                                    ">
                                    <a class="nav-link" href="#">
                                        Documentos de soporte (DSNOF)
                                    </a>
                                    <ul class="nav nav-children">

                                        <li class="{{ ($path[0] === 'support-documents' && $path[1] === 'create')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.support-documents.create')}}">
                                                Nuevo
                                            </a>
                                        </li>
                                        <li class="{{ (in_array($path[0], ['support-documents', 'support-document-adjust-notes']) && $path[1] === '')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.support-documents.index')}}">
                                                Listado
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                {{-- documento de soporte --}}


                                {{-- <li class="nav-parent
                                    {{ ($path[0] === 'fixed-asset' )?'nav-active nav-expanded':'' }}
                                    ">
                                    <a class="nav-link" href="#">
                                        Activos fijos
                                    </a>
                                    <ul class="nav nav-children">

                                        <li class="{{ ($path[0] === 'fixed-asset' && $path[1] === 'items')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.fixed_asset_items.index')}}">
                                                Ítems
                                            </a>
                                        </li>
                                        <li class="{{ ($path[0] === 'fixed-asset' && $path[1] === 'purchases')?'nav-active':'' }}">
                                            <a class="nav-link" href="{{route('tenant.fixed_asset_purchases.index')}}">
                                                Compras
                                            </a>
                                        </li>
                                    </ul>
                                </li> --}}
                            </ul>
                        </li>
                        @endif

                        @if(in_array('inventory', $vc_modules))
                        <li class="nav-parent {{ (in_array($path[0], ['inventory', 'warehouses', 'moves', 'transfers']) ||
                                                ($path[0] === 'reports' && in_array($path[1], ['kardex', 'inventory', 'valued-kardex'])))?'nav-active nav-expanded':'' }}">
                            <a class="nav-link" href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-building-warehouse">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M3 21v-13l9 -4l9 4v13"></path>
                                    <path d="M13 13h4v8h-10v-6h6"></path>
                                    <path d="M13 21v-9a1 1 0 0 0 -1 -1h-2a1 1 0 0 0 -1 1v3"></path>
                                </svg>
                                <span>Inventario</span>
                            </a>
                            <ul class="nav nav-children" style="">
                                <li class="{{ ($path[0] === 'warehouses')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('warehouses.index')}}">Almacenes</a>
                                </li>
                                <li class="{{ ($path[0] === 'inventory')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('inventory.index')}}">Movimientos</a>
                                </li>
                                <li class="{{ ($path[0] === 'transfers')?'nav-active':'' }}">
                                    <a class="nav-link" href="{{route('transfers.index')}}">Traslados</a>
                                </li>
                                <li class="{{(($path[0] === 'reports') && ($path[1] === 'kardex')) ? 'nav-active' : ''}}">
                                    <a class="nav-link" href="{{route('reports.kardex.index')}}">
                                        Reporte Kardex
                                    </a>
                                </li>
                                <li class="{{(($path[0] === 'reports') && ($path[1] == 'inventory')) ? 'nav-active' : ''}}">
                                    <a class="nav-link" href="{{route('reports.inventory.index')}}">
                                        Reporte Inventario
                                    </a>
                                </li>
                                {{-- <li class="{{(($path[0] === 'reports') && ($path[1] === 'valued-kardex')) ? 'nav-active' : ''}}">
                                    <a class="nav-link" href="{{route('reports.valued_kardex.index')}}">
                                        Kardex valorizado
                                    </a>
                                </li> --}}
                            </ul>
                        </li>
                        @endif

                    @endif

                    {{-- @if(in_array('advanced', $vc_modules) && $vc_company->soap_type_id != '03')
                    <li class="
                        nav-parent
                        {{ ($path[0] === 'retentions')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'dispatches')?'nav-active nav-expanded':'' }}
                        {{ ($path[0] === 'perceptions')?'nav-active nav-expanded':'' }}
                        ">
                        <a class="nav-link" href="#">
                            <i class="fas fa-receipt" aria-hidden="true"></i>
                            <span>Comprobantes avanzados</span>
                        </a>
                        <ul class="nav nav-children" style="">
                            <li class="{{ ($path[0] === 'retentions')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.retentions.index')}}">
                                    Retenciones
                                </a>
                            </li>
                            <li class="{{ ($path[0] === 'dispatches')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.dispatches.index')}}">
                                    Guías de remisión
                                </a>
                            </li>
                            <li class="{{ ($path[0] === 'perceptions')?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.perceptions.index')}}">
                                Percepciones
                                </a>
                            </li>

                        </ul>
                    </li>
                    @endif --}}

                    @if(in_array('reports', $vc_modules))
                    <li class="nav-parent {{  ($path[0] === 'reports' && in_array($path[1], ['report-taxes','purchases', 'search','sales','customers','items',
                                        'general-items','consistency-documents', 'quotations', 'sale-notes','cash','commissions','document-hotels',
                                        'validate-documents', 'document-detractions','commercial-analysis', 'order-notes-consolidated', 'document-pos',
                                        'order-notes-general', 'sales-consolidated', 'user-commissions', 'co-remissions', 'co-items-sold', 'co-sales-book'])) ? 'nav-active nav-expanded' : ''}}">

                        <a class="nav-link" href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-analytics">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
                                <path d="M9 17l0 -5"></path>
                                <path d="M12 17l0 -1"></path>
                                <path d="M15 17l0 -3"></path>
                            </svg>
                            <span>Reportes</span>
                        </a>
                        <ul class="nav nav-children" style="">
                            <li class="{{(($path[0] === 'reports') && ($path[1] === 'purchases')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.reports.purchases.index')}}">
                                    Compras
                                </a>
                            </li>

                            <li class="nav-parent {{  ($path[0] === 'reports' &&
                                    in_array($path[1], ['sales','customers','items','quotations', 'sale-notes', 'document-detractions', 'document-pos',
                                    'commissions',  'general-items','sales-consolidated', 'user-commissions', 'co-remissions'])) ? 'nav-active nav-expanded' : ''}}">

                                <a class="nav-link" href="#">
                                    Ventas
                                </a>
                                <ul class="nav nav-children">
                                    @if($vc_company->soap_type_id != '03')
                                    <li class="{{(($path[0] === 'reports') && ($path[1] === 'sales')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.sales.index')}}">
                                            Documentos
                                        </a>
                                    </li>
                                    @endif
                                    <li class="{{(($path[0] === 'reports') && ($path[1] === 'customers')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.customers.index')}}">
                                            Clientes
                                        </a>
                                    </li>

                                    <li class="{{(($path[0] === 'reports') && ($path[1] === 'document-pos')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.document_pos.index')}}">
                                            Documentos POS
                                        </a>
                                    </li>

                                    <li class="{{(($path[0] === 'reports') && ($path[1] === 'items')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.items.index')}}">
                                            Producto - busqueda individual
                                        </a>
                                    </li>
                                    <li class="{{(($path[0] === 'reports') && ($path[1] === 'general-items')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.general_items.index')}}">
                                            Productos
                                        </a>
                                    </li>
                                    <li class="{{(($path[0] === 'reports') && ($path[1] == 'quotations')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.quotations.index')}}">
                                            Cotizaciones
                                        </a>
                                    </li>
                                    <li class="{{(($path[0] === 'reports') && ($path[1] == 'co-remissions')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.co-remissions.index')}}">
                                            Remisiones
                                        </a>
                                    </li>
                                   <!-- <li class="{{(($path[0] === 'reports') && ($path[1] == 'sale-notes')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.sale_notes.index')}}">
                                            Notas de Venta
                                        </a>
                                    </li>-->
                                    {{-- @if($vc_company->soap_type_id != '03')
                                    <li class="{{(($path[0] === 'reports') && ($path[1] == 'document-detractions')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.document_detractions.index')}}">
                                            Detracciones
                                        </a>
                                    </li>
                                    @endif --}}


                                    <li class="nav-parent
                                        {{ (($path[0] === 'reports') && ($path[1] == 'commissions')) ?'nav-active nav-expanded':'' }}
                                        {{ (($path[0] === 'reports') && ($path[1] == 'user-commissions')) ?'nav-active nav-expanded':'' }}
                                        ">
                                        <a class="nav-link" href="#">
                                            Comisiones
                                        </a>
                                        <ul class="nav nav-children">

                                            <li class="{{(($path[0] === 'reports') && ($path[1] == 'user-commissions')) ? 'nav-active' : ''}}">
                                                <a class="nav-link" href="{{route('tenant.reports.user_commissions.index')}}">
                                                    Utilidad ventas
                                                </a>
                                            </li>

                                            <li class="{{(($path[0] === 'reports') && ($path[1] == 'commissions')) ? 'nav-active' : ''}}">
                                                <a class="nav-link" href="{{route('tenant.reports.commissions.index')}}">
                                                    Ventas
                                                </a>
                                            </li>
                                        </ul>
                                    </li>

                                    @if($advanced_config && $advanced_config->enable_seller_views)
                                    <li class="{{(($path[0] === 'reports') && ($path[1] === 'sellers')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.sellers.index')}}">
                                            Vendedores
                                        </a>
                                    </li>
                                    @endif

                                    {{-- <li class="{{(($path[0] === 'reports') && ($path[1] == 'sales-consolidated')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.sales_consolidated.index')}}">
                                            Consolidado de items
                                        </a>
                                    </li> --}}
                                </ul>
                            </li>

                            {{-- <li class="nav-parent {{  ($path[0] === 'reports' &&
                                    in_array($path[1], ['order-notes-consolidated', 'order-notes-general'])) ? 'nav-active nav-expanded' : ''}}">

                                <a class="nav-link" href="#">
                                    Pedidos
                                </a>
                                <ul class="nav nav-children">

                                    <li class="{{(($path[0] === 'reports') && ($path[1] == 'order-notes-general')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.order_notes_general.index')}}">
                                            General
                                        </a>
                                    </li>

                                    <li class="{{(($path[0] === 'reports') && ($path[1] == 'order-notes-consolidated')) ? 'nav-active' : ''}}">
                                        <a class="nav-link" href="{{route('tenant.reports.order_notes_consolidated.index')}}">
                                            Consolidado de items
                                        </a>
                                    </li>
                                </ul>
                            </li> --}}

                            {{-- @if($vc_company->soap_type_id != '03')
                            <li class="{{(($path[0] === 'reports') && ($path[1] == 'consistency-documents')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.consistency-documents.index')}}">Consistencia documentos</a>
                            </li>

                             <li class="{{(($path[0] === 'reports') && ($path[1] == 'validate-documents')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.validate_documents.index')}}">
                                    Validador de documentos
                                </a>
                            </li>
                            @endif --}}
                            @if(in_array('hotel', $vc_business_turns))
                            <li class="{{(($path[0] === 'reports') && ($path[1] == 'document-hotels')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.reports.document_hotels.index')}}">
                                    Giro negocio hoteles
                                </a>
                            </li>
                            @endif
                            <!-- <li class="{{(($path[0] === 'reports') && ($path[1] == 'commercial-analysis')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.reports.commercial_analysis.index')}}">
                                    Análisis comercial
                                </a>
                            </li> -->
                            <li class="{{(($path[0] === 'reports') && ($path[1] === 'report-taxes')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.reports.taxes')}}">
                                    Impuestos
                                </a>
                            </li>

                            <li class="{{(($path[0] === 'reports') && ($path[1] === 'co-items-sold')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.co-items-sold.index')}}">
                                    Artículos vendidos
                                </a>
                            </li>

                            <li class="{{(($path[0] === 'reports') && ($path[1] === 'co-sales-book')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.co-sales-book.index')}}">
                                    Libro de ventas
                                </a>
                            </li>

                        </ul>
                    </li>
                    @endif

                    {{-- @if(in_array('accounting', $vc_modules))
                    <li class="
                        nav-parent
                        {{ ($path[0] === 'account')?'nav-active nav-expanded':'' }}
                        ">
                        <a class="nav-link" href="#">
                            <span class="float-right badge badge-red badge-danger mr-3">Nuevo</span>
                            <i class="fas fa-chart-bar" aria-hidden="true"></i>
                            <span>Contabilidad</span>
                        </a>
                        <ul class="nav nav-children" style="">
                            <li class="{{(($path[0] === 'account') && ($path[1] === 'format')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{ route('tenant.account_format.index') }}">
                                    Exportar formatos
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'account') && ($path[1] == ''))   ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{ route('tenant.account.index') }}">
                                    <!-- Exportar SISCONT/CONCAR -->
                                    Exportar formatos - Sis. Contable
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'account') && ($path[1] == 'summary-report'))   ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{ route('tenant.account_summary_report.index') }}">
                                    Reporte resumido - Ventas
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif --}}

                    {{-- Debug temporal --}}
                    {{-- {{ dd($vc_modules) }} --}}
                    @if(in_array('accounting', $vc_modules))
                    <li class="nav-parent {{$path[0] === 'accounting' && in_array($path[1], ['journal', 'charts', 'income-statement', 'financial-position', 'auxiliary-movement']) ? 'nav-active nav-expanded' : ''}}">
                        <a class="nav-link" href="#">
                            <span class="float-right badge badge-red badge-danger mr-3 bg-secondary bg-danger mt-1">Nuevo</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chart-histogram">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M3 3v18h18"></path>
                                <path d="M20 18v3"></path>
                                <path d="M16 16v5"></path>
                                <path d="M12 13v8"></path>
                                <path d="M8 16v5"></path>
                                <path d="M3 11c6 0 5 -5 9 -5s3 5 9 5"></path>
                            </svg>
                            <span>Contabilidad</span>
                        </a>
                        <ul class="nav nav-children" style="">
                            <li class="{{(($path[0] === 'accounting') && ($path[1] == 'charts')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.accounting.charts.index')}}">
                                    Cuentas contables
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'accounting') && ($path[1] == 'journal')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.accounting.journal.entries.index')}}">
                                    Asientos contables
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'accounting') && ($path[1] == 'financial-position')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.accounting.report.financial-position')}}">
                                    Reporte de Situacion Financiera
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'accounting') && ($path[1] == 'income-statement')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.accounting.report.income-statement')}}">
                                    Reporte de Estado de resultado
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'accounting') && ($path[1] == 'auxiliary-movement')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.accounting.report.auxiliary-movement')}}">
                                    Reporte de Movimiento auxiliar
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    @if(in_array('finance', $vc_modules))

                    <li class="nav-parent {{$path[0] === 'finances' && in_array($path[1], [
                                                'global-payments', 'balance','payment-method-types', 'unpaid', 'to-pay', 'income'
                                            ])
                                            ? 'nav-active nav-expanded' : ''}}">

                        <a class="nav-link" href="#">
                            {{-- <span class="float-right badge badge-red badge-danger mr-3">Nuevo</span> --}}
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calculator">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M4 3m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"></path>
                                <path d="M8 7m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v1a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z"></path>
                                <path d="M8 14l0 .01"></path>
                                <path d="M12 14l0 .01"></path>
                                <path d="M16 14l0 .01"></path>
                                <path d="M8 17l0 .01"></path>
                                <path d="M12 17l0 .01"></path>
                                <path d="M16 17l0 .01"></path>
                            </svg>
                            <span>Finanzas</span>
                        </a>
                        <ul class="nav nav-children" style="">
                            <li class="{{(($path[0] === 'finances') && ($path[1] == 'global-payments')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.finances.global_payments.index')}}">
                                    Pagos
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'finances') && ($path[1] == 'balance')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.finances.balance.index')}}">
                                    Balance
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'finances') && ($path[1] == 'payment-method-types')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.finances.payment_method_types.index')}}">
                                    Ingresos y Egresos - M. Pago
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'finances') && ($path[1] == 'unpaid')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.finances.unpaid.index')}}">
                                    Cuentas por cobrar
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'finances') && ($path[1] == 'to-pay')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.finances.to_pay.index')}}">
                                    Cuentas por pagar
                                </a>
                            </li>
                            <li class="{{(($path[0] === 'finances') && ($path[1] == 'income')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.finances.income.index')}}">
                                    Ingresos
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif



                    @if(in_array('payroll', $vc_modules))

                    <li class="nav-parent {{$path[0] === 'payroll' &&
                                            in_array($path[1], [
                                                'workers', 'document-payrolls', 'document-payroll-adjust-notes'
                                            ])
                                            // &&
                                            // in_array($path[2], [
                                            //     'create'
                                            // ])
                                            ? 'nav-active nav-expanded' : ''}}">

                        <a class="nav-link" href="#">
                            {{-- <span class="float-right badge badge-red badge-danger mr-3">Nuevo</span> --}}
                            <svg  xmlns="http://www.w3.org/2000/svg"  width="20"  height="20"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-clipboard-list"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" /><path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /><path d="M9 12l.01 0" /><path d="M13 12l2 0" /><path d="M9 16l.01 0" /><path d="M13 16l2 0" /></svg>
                            <span>Nóminas</span>
                        </a>

                        <ul class="nav nav-children" style="">

                            <li class="{{(($path[0] === 'payroll') && ($path[1] == 'workers')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.payroll.workers.index')}}">
                                    Empleados
                                </a>
                            </li>

                            <li class="{{(($path[0] === 'payroll') && ($path[1] == 'document-payrolls') && $path[2] == 'create') ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.payroll.document-payrolls.create')}}">
                                    Nueva nómina
                                </a>
                            </li>

                            <li class="{{(($path[0] === 'payroll') && (in_array($path[1], ['document-payrolls', 'document-payroll-adjust-notes'])) && ($path[2] !== 'create')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.payroll.document-payrolls.index')}}">
                                    Listado de nóminas
                                </a>
                            </li>

                        </ul>

                        {{-- <ul class="nav nav-children" style="">
                            <li class="{{(($path[0] === 'payroll') && ($path[1] == 'type-workers')) ? 'nav-active' : ''}}">
                                <a class="nav-link" href="{{route('tenant.payroll.type-workers.index')}}">
                                    Tipos de empleados
                                </a>
                            </li>
                        </ul> --}}
                    </li>
                    @endif

                    @if(in_array('radian', $vc_modules))
                        <li class="nav-parent {{in_array($path[0], ['co-radian-events', 'co-email-reading']) ? 'nav-active nav-expanded' : ''}}">
                            <a class="nav-link" href="#">
                                <svg  xmlns="http://www.w3.org/2000/svg"  width="20"  height="20"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.5 21h-5.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v6" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /><path d="M15 19l2 2l4 -4" /></svg>
                                {{-- <i class="fas fa-calendar-check"></i> --}}
                                <span>Eventos RADIAN</span>
                            </a>
                            <ul class="nav nav-children">

                                <li class="{{($path[0] === 'co-email-reading' && $path[1] == 'process-emails') ? 'nav-active' : ''}}">
                                    <a class="nav-link" href="{{route('tenant.co-email-reading-process-emails.index')}}">
                                        Procesar correos
                                    </a>
                                </li>

                                <li class="{{($path[0] === 'co-radian-events' && $path[1] == 'reception') ? 'nav-active' : ''}}">
                                    <a class="nav-link" href="{{route('tenant.co-radian-events-reception.index')}}">
                                        Recepción de documentos
                                    </a>
                                </li>

                                <li class="{{($path[0] === 'co-radian-events' && $path[1] == 'manage') ? 'nav-active' : ''}}">
                                    <a class="nav-link" href="{{route('tenant.co-radian-events-manage.index')}}">
                                        Gestionar eventos
                                    </a>
                                </li>

                                {{-- <li class="{{($path[0] === 'co-advanced-configuration') ? 'nav-active' : ''}}">
                                    <a class="nav-link" href="{{route('tenant.co-advanced-configuration.index')}}">
                                        Avanzado
                                    </a>
                                </li> --}}
                            </ul>
                        </li>
                    @endif


                    {{-- @if(in_array('cuenta', $vc_modules))
                    <li class=" nav-parent
                        {{ ($path[0] === 'cuenta')?'nav-active nav-expanded':'' }}">
                        <a class="nav-link" href="#">
                            <span class="float-right badge badge-red badge-danger mr-3">Nuevo</span>
                            <i class="fas fa-dollar-sign" aria-hidden="true"></i>
                            <span>Mis Pagos</span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="{{ (($path[0] === 'cuenta') && ($path[1] === 'configuration')) ?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.configuration.index')}}">
                                    Configuracion
                                </a>
                            </li>
                            <li class="{{ (($path[0] === 'cuenta') && ($path[1] === 'payment_index')) ?'nav-active':'' }}">
                                <a class="nav-link" href="{{route('tenant.payment.index')}}">
                                    Lista de Pagos
                                </a>
                            </li>

                        </ul>
                    </li>
                    @endif --}}
                </ul>
            </nav>
        </div>
        <script>
            // Maintain Scroll Position
            if (typeof localStorage !== 'undefined') {
                if (localStorage.getItem('sidebar-left-position') !== null) {
                    var initialPosition = localStorage.getItem('sidebar-left-position'),
                        sidebarLeft = document.querySelector('#sidebar-left .nano-content');
                    sidebarLeft.scrollTop = initialPosition;
                }
            }
            
        </script>
    </div>
    @php
        $isConfigRoute = in_array($path[0], [
            'co-configuration-change-ambient', 'co-configuration', 'co-configuration-documents',
            'companies', 'catalogs', 'advanced', 'tasks', 'inventories','company_accounts','bussiness_turns',
            'offline-configurations','series-configurations','configurations','co-advanced-configuration'
        ]);
    @endphp

    @if(in_array('configuration', $vc_modules))
    <div id="sticky-config" class="sidebar-config dropup">
        <a href="#" id="configMenuToggle"
           class="config-btn d-flex align-items-center justify-content-between"
           data-toggle="dropdown" aria-expanded="{{ $isConfigRoute ? 'true' : 'false' }}">
            <span class="d-inline-flex align-items-center span-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings mr-1">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z">
                    </path>
                </svg>
                <span>Configuración y más</span>
            </span>
            <i class="fa custom-caret"></i>
        </a>

        <div id="configDropdownMenu" class="dropdown-menu dropdown-menu-sidebar"
             role="menu" aria-labelledby="configMenuToggle">
            <ul class="list-unstyled mb-0">
                <li class="{{($path[0] === 'co-configuration-change-ambient') ? 'nav-active': ''}}">
                    <a class="dropdown-item" href="{{route('tenant.configuration.change.ambient')}}">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-server"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 4m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M3 12m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M7 8l0 .01" /><path d="M7 16l0 .01" /></svg>
                        Cambiar ambiente
                    </a>
                </li>
                <li class="{{($path[0] === 'co-configuration-documents') ? 'nav-active': ''}}">                    
                    <a class="dropdown-item" href="{{route('tenant.configuration.documents')}}">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-file-text"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 9l1 0" /><path d="M9 13l6 0" /><path d="M9 17l6 0" /></svg>
                        Documentos</a>
                </li>
                <li class="{{($path[0] === 'co-taxes') ? 'nav-active': ''}}">
                    <a class="dropdown-item" href="{{route('tenant.co-taxes.index')}}">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-receipt"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2m4 -14h6m-6 4h6m-2 4h2" /></svg>
                        Impuestos colombia
                    </a>
                </li>
                <li class="{{($path[0] === 'co-configuration') ? 'nav-active': ''}}">                    
                    <a class="dropdown-item" href="{{route('tenant.configuration')}}">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-building"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M9 8l1 0" /><path d="M9 12l1 0" /><path d="M9 16l1 0" /><path d="M14 8l1 0" /><path d="M14 12l1 0" /><path d="M14 16l1 0" /><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" /></svg>
                        Empresa</a>
                </li>
                @if(auth()->user()->type != 'integrator')
                <li class="{{($path[0] === 'users') ? 'nav-active': ''}}">
                    <a class="dropdown-item" href="{{route('tenant.users.index')}}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-users-group"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path><path d="M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1"></path><path d="M15 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path><path d="M17 10h2a2 2 0 0 1 2 2v1"></path><path d="M5 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path><path d="M3 13v-1a2 2 0 0 1 2 -2h2"></path></svg>
                        Usuarios
                    </a>
                </li>
                <li class="{{($path[0] === 'establishments') ? 'nav-active': ''}}">
                    <a class="dropdown-item" href="{{route('tenant.establishments.index')}}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M3 21l18 0"></path><path d="M4 21l0 -10"></path><path d="M20 21l0 -10"></path><path d="M5 11l14 0"></path><path d="M5 11l1 -6h12l1 6"></path><path d="M9 21l0 -8l6 0l0 8"></path></svg>
                        Establecimientos
                    </a>
                </li>
                @endif
                @if(auth()->user()->type != 'integrator')
                <li class="{{($path[0] === 'catalogs') ? 'nav-active' : ''}}">                    
                    <a class="dropdown-item" href="{{route('tenant.catalogs.index')}}">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-library"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 3m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 7.26a2.005 2.005 0 0 0 -1.012 1.737v10c0 1.1 .9 2 2 2h10c.75 0 1.158 -.385 1.5 -1" /><path d="M11 7h5" /><path d="M11 10h6" /><path d="M11 13h3" /></svg>
                        Catálogos</a>
                </li>
                @endif
                <li class="{{($path[0] === 'co-advanced-configuration') ? 'nav-active' : ''}}">                    
                    <a class="dropdown-item" href="{{route('tenant.co-advanced-configuration.index')}}">
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-tools"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21h4l13 -13a1.5 1.5 0 0 0 -4 -4l-13 13v4" /><path d="M14.5 5.5l4 4" /><path d="M12 8l-5 -5l-4 4l5 5" /><path d="M7 8l-1.5 1.5" /><path d="M16 12l5 5l-4 4l-5 -5" /><path d="M16 17l-1.5 1.5" /></svg>
                        Avanzado</a>
                </li>
            </ul>
        </div>
    </div>
    @endif

</aside>
