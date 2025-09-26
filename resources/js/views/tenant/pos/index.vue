<template>
<div>
    <header class="page-header pr-0">
        <!-- <h2 class="text-sm">POS</h2>
      <div class="right-wrapper pull-right">
        <h2 class="text-sm pr-5">T/C 3.321</h2>
        <h2 class="text-sm">{{user.name}}</h2>
      </div> -->
        <div class="row">
            <div class="col-md-6">
                <div class="header-controls-row d-flex align-items-center h-100 my-0">
                    <el-switch v-model="search_item_by_barcode" active-text="Buscar por código de barras" @change="changeSearchItemBarcode" class="el-switch el-switch-barcode"></el-switch>
                    <template v-if="!electronic">
                        <el-switch v-model="type_refund" active-text="Devolución"></el-switch>
                    </template>
                    <div class="balanza-btn-group">
                        <button
                            v-if="!scale.connected"
                            size="small"
                            class="el-button btn-balanza el-button--primary el-button--small d-flex align-items-center"
                            type="primary"
                            :loading="scale.connecting"
                            @click="connectScale"
                        >
                            <i class="fa fa-balance-scale" style="margin-right:6px;"></i>
                            <span class="balanza-btn-text">Conectar balanza</span>
                            <el-tooltip
                                effect="dark"
                                content="Para establecer la conexión, asegúrese de que la balanza esté conectada a un puerto COM. Si no aparece el puerto, instale el driver correspondiente al modelo de su balanza."
                                placement="top"
                            >
                                <i class="fa fa-info-circle balanza-tooltip"></i>
                            </el-tooltip>
                        </button>
                        <button
                            v-if="scale.connected"
                            size="small"
                            type="danger"
                            class="el-button btn-balanza el-button--primary el-button--small d-flex align-items-center"
                            @click="disconnectScale"
                            :loading="scale.connecting"
                        >
                            <i class="fa fa-plug" style="margin-right:6px;"></i>
                            <span class="balanza-btn-text">Desconectar balanza</span>
                            <el-tooltip
                                effect="dark"
                                content="Para establecer la conexión, asegúrese de que la balanza esté conectada a un puerto COM. Si no aparece el puerto, instale el driver correspondiente al modelo de su balanza."
                                placement="top"
                            >
                                <i class="fa fa-info-circle balanza-tooltip"></i>
                            </el-tooltip>
                        </button>                        
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <h2 class="px-2"> <button type="button" @click="place = 'cat'" class="btn btn-custom btn-sm m-auto"><i class="fa fa-border-all"></i></button> </h2>
                <h2 class="px-2"> <button type="button" :disabled="place == 'cat2'" @click="setView" class="btn btn-custom btn-sm m-auto"><i class="fa fa-bars"></i></button> </h2>
                <h2 class="px-2"> <button type="button" :disabled="place== 'cat'" @click="back()" class="btn btn-custom btn-sm m-auto"><i class="fa fa-undo"></i></button> </h2>
            </div>
            <div class="col-md-2">
                <div class="right-wrapper">
                    <!-- <h2 class="text-sm pr-5">T/C  {{form.exchange_rate_sale}}</h2> -->
                    <h2 class="text-sm  pull-right">{{user.name}}</h2>
                </div>
            </div>
        </div>
    </header>

    <div v-if="plate_number_valid">
        <div v-if="!is_payment" class="row col-lg-12 m-0 p-0" v-loading="loading">
            <div class="col-lg-8 col-md-6 px-4 pt-3 hyo">

                <template v-if="!search_item_by_barcode">
                    <el-autocomplete
                        v-show="place  == 'prod' || place == 'cat2'"
                        v-model="input_item"
                        :fetch-suggestions="querySearchAsync"
                        placeholder="Buscar productos"
                        size="medium"
                        @select="handleSelectProduct"
                        class="m-bottom"
                        :trigger-on-focus="false"
                        autofocus
                    >
                        <el-button slot="append" icon="el-icon-plus" @click.prevent="showDialogNewItem = true"></el-button>
                    </el-autocomplete>
                </template>
                <template v-else>
                    <el-input v-show="place  == 'prod' || place == 'cat2'" placeholder="Buscar productos" size="medium" v-model="input_item" @change="searchItemsBarcode" autofocus class="m-bottom">
                        <el-button slot="append" icon="el-icon-plus" @click.prevent="showDialogNewItem = true"></el-button>
                    </el-input>
                </template>

                <div v-if="place == 'cat2'" class="container testimonial-group">
                    <div class="row text-center flex-nowrap">
                        <div v-for="(item, index) in categories" @click="filterCategorie(item.id, true)" :style="{ backgroundColor: item.color}" :key="index" class="col-sm-3 pointer">{{item.name}}</div>
                    </div>
                </div> <br>

                <div v-if="place == 'cat'" class="row no-gutters">
                    <div v-for="(item, index) in categories" class="col" :key="index">
                        <div @click="filterCategorie(item.id)" class="card p-0 m-0 mb-1 mr-1 text-center">
                            <div :style="{ backgroundColor: item.color}" class="card-body pointer rounded-0" style="font-weight: bold;color: white;font-size: 18px;">
                                {{item.name}}
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="place == 'prod' || place == 'cat2'" class="row pos-items">
                    <div v-for="(item,index) in items" v-bind:class="classObjectCol" :key="index">
                        <section class="card ">
                            <div class="card-body pointer px-2 pt-2" @click="clickAddItem(item,index)">
                                <el-tooltip class="item" effect="dark" :content="item.name" placement="bottom-end">
                                    <p class="font-weight-semibold mb-0 truncate-text">
                                        <!-- <span
                                            class="favorite-star"
                                            @click.stop="toggleFavorite(item)"
                                            :title="isFavorite(item) ? 'Quitar de favoritos' : 'Marcar como favorito'"
                                        >
                                            <i :class="isFavorite(item) ? 'fas fa-star text-warning' : 'far fa-star text-secondary'"></i>
                                        </span> -->
                                        {{item.name}}
                                    </p>
                                </el-tooltip>
                                <!-- Mostrar imagen solo si la pantalla es >= 600px -->
                                <img v-if="!hideProductImage" :src="item.image_url" class="img-thumbail img-custom product-image-responsive" />
                                <p class="text-muted font-weight-lighter mb-0">
                                    <small>{{item.internal_id}}</small>
                                    <small style="float: right; clear">{{item.lot_code ? 'Lote:' + item.lot_code : ''}}   {{item.date_of_due ? 'FV:' + item.date_of_due : ''}}</small>
                                    <template v-if="item.sets.length  > 0">
                                        <br>
                                        <small> {{ item.sets.join('-') }} </small>
                                    </template>
                                </p>
                            </div>
                            <div class="card-footer pointer text-center bg-primary">
                                <template v-if="!item.edit_unit_price">
                                    <h5 class="font-weight-semibold text-right text-white">
                                        <button
                                            type="button"
                                            class="btn btn-xs btn-primary-pos"
                                            @click="clickOpenInputEditUP(index)">
                                            <span style="font-size:16px;">&#9998;</span>
                                        </button>
                                        {{currency.symbol}} 
                                        <span v-if="!advanced_configuration.item_tax_included">
                                            {{ getFormatDecimal(item.sale_unit_price) }}
                                        </span>
                                        <span v-else>
                                            {{ getFormatDecimal(item.sale_unit_price_with_tax) }}
                                        </span>
                                    </h5>
                                </template>
                                <template v-else>
                                    <el-input
                                        min="0"
                                        v-model="items[index].edit_sale_unit_price"
                                        class="mt-3 mb-3"
                                        size="mini"
                                    >
                                        <el-button slot="append" icon="el-icon-check" type="primary" @click="clickEditUnitPriceItem(index)"></el-button>
                                        <el-button slot="append" icon="el-icon-close" type="danger" @click="clickCancelUnitPriceItem(index)"></el-button>
                                    </el-input>
                                </template>
                            </div>

                            <div v-if="configuration.options_pos" class=" card-footer  bg-primary btn-group flex-wrap" style="width:100% !important; padding:0 !important; ">
                                <el-row style="width:100%">
                                    <el-col :span="4">
                                        <el-tooltip class="item" effect="dark" content="Visualizar stock" placement="bottom-end">
                                            <button type="button" style="width:100% !important;" class="btn btn-xs btn-primary-pos" @click="clickWarehouseDetail(item)">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </el-tooltip>
                                    </el-col>
                                    <el-col :span="4">
                                        <el-tooltip class="item" effect="dark" content="Visualizar historial de ventas del producto (precio venta) y cliente" placement="bottom-end">
                                            <button type="button" style="width:100% !important;" class="btn btn-xs btn-primary-pos" @click="clickHistorySales(item.item_id)"><i class="fa fa-list"></i></button>
                                        </el-tooltip>
                                    </el-col>
                                    <el-col :span="4">
                                        <el-tooltip class="item" effect="dark" content="Visualizar historial de compras del producto (precio compra)" placement="bottom-end">
                                            <button type="button" style="width:100% !important;" class="btn btn-xs btn-primary-pos" @click="clickHistoryPurchases(item.item_id)"><i class="fas fa-cart-plus"></i></button>
                                        </el-tooltip>
                                    </el-col>
                                    <el-col :span="4">
                                        <el-tooltip class="item" effect="dark" content="Visualizar lista de precios disponibles" placement="bottom-end">
                                            <el-popover placement="top" title="Precios" width="400" trigger="click">
                                                <el-table v-if="item.item_unit_types" :data="item.item_unit_types">
                                                    <el-table-column width="140" label="Descripción" property="description"></el-table-column>
                                                    <el-table-column width="80" label="Unidad" property="unit_type_name"></el-table-column>
                                                    <el-table-column width="80" label="Precio">
                                                        <template slot-scope="{row}">
                                                            <span v-if="row.price_default == 1">{{ row.price1 }}</span>
                                                            <span v-else-if="row.price_default == 2">{{ row.price2 }}</span>
                                                            <span v-else-if="row.price_default == 3">{{ row.price3 }}</span>
                                                        </template>
                                                    </el-table-column>
                                                    <el-table-column width="70" label="">
                                                        <template slot-scope="{row}">
                                                            <button @click="setListPriceItem(row,index)" type="button" class="btn btn-custom btn-xs">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </template>
                                                    </el-table-column>
                                                </el-table>
                                                <button type="button" slot="reference" style="width:100% !important;" class="btn btn-xs btn-primary-pos"><i class="fas fa-money-bill-alt"></i></button>
                                            </el-popover>
                                        </el-tooltip>
                                    </el-col>
                                    <el-col :span="4">
                                        <el-tooltip class="item" effect="dark" :content="isFavorite(item) ? 'Quitar de favoritos' : 'Marcar como favorito'" placement="bottom-end">
                                            <button type="button"
                                                style="width:100% !important;"
                                                class="btn btn-xs btn-primary-pos"
                                                @click.stop="toggleFavorite(item)">
                                                <i :class="isFavorite(item) ? 'fas fa-star text-warning' : 'far fa-star text-secondary'"></i>
                                            </button>
                                        </el-tooltip>
                                    </el-col>
                                </el-row>
                            </div>

                            <!-- <div v-if="configuration.options_pos" class=" card-footer  bg-primary btn-group flex-wrap" style="width:100% !important; padding:0 !important; ">

                                <el-tooltip class="item" effect="dark" content="Visualizar stock" placement="bottom-end">
                                    <button type="button" style="width:25% !important;" class="btn btn-xs btn-primary-pos" @click="clickWarehouseDetail(item)">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </el-tooltip>

                                <el-tooltip class="item" effect="dark" content="Visualizar historial de ventas del producto (precio venta) y cliente" placement="bottom-end">
                                    <button type="button" style="width:25% !important;" class="btn btn-xs btn-primary-pos" @click="clickHistorySales(item.item_id)"><i class="fa fa-list"></i></button>
                                </el-tooltip>

                                <el-tooltip class="item" effect="dark" content="Visualizar historial de compras del producto (precio compra)" placement="bottom-end">
                                    <button type="button" style="width:25% !important;" class="btn btn-xs btn-primary-pos" @click="clickHistoryPurchases(item.item_id)"><i class="fas fa-cart-plus"></i></button>
                                </el-tooltip>

                                <el-tooltip class="item" effect="dark" content="Visualizar lista de precios disponibles" placement="bottom-end">
                                    <el-popover placement="top" title="Precios" width="370" trigger="click">
                                        <el-table v-if="item.item_unit_types" :data="item.item_unit_types">
                                            <el-table-column width="90" label="Precio">
                                                <template slot-scope="{row}">
                                                    <span v-if="row.price_default == 1">{{row.price1}}</span>
                                                    <span v-else-if="row.price_default == 2">{{row.price2}}</span>
                                                    <span v-else-if="row.price_default == 3">{{row.price3}}</span>
                                                </template>
                                            </el-table-column>
                                            <el-table-column width="80" label="Unidad" property="unit_type_id"></el-table-column>
                                            <el-table-column width="120" label="Descripción" property="description"></el-table-column>

                                            <el-table-column width="80" label="">
                                                <template slot-scope="{row}">
                                                    <button @click="setListPriceItem(row,index)" type="button" class="btn btn-custom btn-xs">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </template>
                                            </el-table-column>
                                        </el-table>
                                        <button type="button" slot="reference" style="width:100% !important;" class="btn btn-xs btn-primary-pos"><i class="fas fa-money-bill-alt"></i></button>
                                    </el-popover>
                                </el-tooltip>
                            </div> -->
                        </section>
                    </div>
                </div>
                <div v-if="place == 'prod' || place == 'cat2'" class="row">
                    <div class="col-md-12 text-center">
                        <el-pagination
                            @current-change="getRecords"
                            layout="total, prev, pager, next"
                            :total="pagination.total"
                            :current-page.sync="pagination.current_page"
                            :page-size="pagination.per_page"
                        >
                        </el-pagination>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 bg-white m-0 p-0" style="height: calc(100vh - 110px)">
                <div class="h-75 bg-light" style="overflow-y: auto">
                    <div class="row py-3 border-bottom m-0 p-0">
                        <div class="col-8">
                            <el-select ref="select_person" v-model="form.customer_id" filterable placeholder="Cliente" @change="changeCustomer" @keyup.native="keyupCustomer" @keyup.enter.native="keyupEnterCustomer">
                                <el-option v-for="option in all_customers" :key="option.id" :label="option.description" :value="option.id"></el-option>
                            </el-select>
                        </div>
                        <div class="col-4">
                            <div class="btn-group d-flex" role="group">
                                <a class="btn btn-sm btn-default w-100" @click.prevent="showDialogNewPerson = true">
                                    <i class="fas fa-plus fa-wf"></i>
                                </a>
                                <a class="btn btn-sm btn-default w-100" @click="clickDeleteCustomer">
                                    <i class="fas fa-trash fa-wf"></i>
                                </a>
                                <!-- <a class="btn btn-sm btn-default w-100" @click="selectCurrencyType"> -->
                                <!-- <template v-if="form.currency_id == 'PEN'">
                        <strong>S/</strong>
                      </template>
                      <template v-else>
                        <strong>$</strong>
                      </template> -->
                                <!-- <i class="fa fa-usd" aria-hidden="true"></i> -->
                                <!-- </a> -->
                            </div>
                        </div>
                    </div>
                    <div class="row py-1 border-bottom m-0 p-0">
                        <div class="col-12">
                            <!-- Responsive tabla SOLO en móvil -->
                            <table v-show="isMobile" class="table table-sm table-borderless mb-0 table-pos-products">
                                <tr v-for="(item,index) in form.items" :key="index" class="pos-product-row">
                                    <td width="20%" class="td-main">
                                        <div class="row-main">
                                            <div style="width: 45%;">
                                                <div class="product-info">
                                                    <div class="product-name">
                                                        <span v-html="clearText(item.item.name)"></span>
                                                    </div>
                                                    <div class="product-details">
                                                        <small v-if="item.unit_type">{{ item.unit_type.name }}</small>
                                                        <template v-if="item.item.lot_code || item.item.date_of_due">
                                                            <small class="text-muted lote-info">
                                                                <span v-if="item.item.lot_code">Lote: {{item.item.lot_code}}</span>
                                                                <span v-if="item.item.lot_code && item.item.date_of_due"> - </span>
                                                                <span v-if="item.item.date_of_due">FV: {{item.item.date_of_due}}</span>
                                                            </small>
                                                        </template>
                                                        <small> {{nameSets(item.item_id)}} </small>
                                                    </div>
                                                </div>                                                
                                            </div>
                                            <div class="row-secondary">
                                                <el-input v-model="item.item.aux_quantity" :readonly="scale.connected" class="input-qty" @focus="startContinuousWeight(item, index)" @blur="stopContinuousWeight(item, index)" @change="onQuantityInput(item, index)" @keyup.enter="onEnterQuantity(item, index)"></el-input>
                                                <el-input v-model="item.sale_unit_price_with_tax" class="input-price input-text-right" @input="clickAddItem(item,index,true)" :readonly="item.item.calculate_quantity"></el-input>
                                                <span class="input-text-right">
                                                  {{currency.symbol}} {{ item.total }}
                                                </span>
                                                <a class="btn btn-sm btn-default btn-trash" @click="clickDeleteItem(index)">
                                                    <i class="fas fa-trash fa-wf"></i>
                                                </a>
                                            </div>
                                        </div>                                        
                                    </td>
                                </tr>
                                <!-- Refund items (puedes adaptar igual si lo necesitas) -->
                                <tr v-for="(item,index) in items_refund" :key="index + 'R'" class="pos-product-row">
                                    <td class="td-main">
                                        <div class="row-main">
                                            <span v-if="item.unit_type" class="pos-list-label">{{ item.unit_type.name }}</span>
                                            <el-input :value=" '-' +item.quantity" :readonly="true" class="input-qty"></el-input>
                                            <div class="product-name">
                                                {{item.item.name}}
                                                <small> {{nameSets(item.item_id)}} </small>
                                            </div>
                                        </div>
                                        <div class="row-secondary">
                                            <span>{{currency.symbol}}</span>
                                            <el-input v-model="item.sale_unit_price_with_tax" class="input-price" @input="clickAddItem(item,index,true)" :readonly="item.item.calculate_quantity"></el-input>
                                            <el-input :value="'-' + item.total" :readonly="true" class="input-total"></el-input>
                                            <a class="btn btn-sm btn-default btn-trash" @click="clickDeleteItemRefund(index)">
                                                <i class="fas fa-trash fa-wf"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <!-- Tabla tradicional SOLO en escritorio -->
                            <table v-show="!isMobile" class="table table-sm table-borderless mb-0">
                                <tr v-for="(item,index) in form.items" :key="index" class="pos-product-row">
                                    <td width="20%">
                                        <p class="m-0 product-name-desktop" style="line-height: 1em;">
                                            <span v-html="clearText(item.item.name)"></span><br>
                                            <small v-if="item.unit_type">{{ item.unit_type.name }}</small>
                                            <template v-if="item.item.lot_code || item.item.date_of_due">
                                                <small class="text-muted lote-info">
                                                    <span v-if="item.item.lot_code">Lote: {{item.item.lot_code}}</span>
                                                    <span v-if="item.item.lot_code && item.item.date_of_due"> - </span>
                                                    <span v-if="item.item.date_of_due">FV: {{item.item.date_of_due}}</span>
                                                </small>
                                            </template>
                                        </p>
                                        <small> {{nameSets(item.item_id)}} </small>
                                    </td>
                                    <td width="20%">
                                        <el-input v-model="item.item.aux_quantity" :readonly="scale.connected" class="input-qty" @focus="startContinuousWeight(item, index)" @blur="stopContinuousWeight(item, index)" @change="onQuantityInput(item, index)" @keyup.enter="onEnterQuantity(item, index)"></el-input>
                                    </td>                                    
                                    <td width="20%">
                                        <p class="font-weight-semibold m-0 text-center">
                                            <el-input v-model="item.sale_unit_price_with_tax" class="input-text-right" @input="clickAddItem(item,index,true)" :readonly="item.item.calculate_quantity">
                                            </el-input>
                                        </p>
                                    </td>
                                    <td width="30%">
                                        <p class="font-weight-semibold m-0 text-center">
                                            <el-input v-model="item.total" @input="calculateQuantity(index)" class="input-text-right" :readonly="!item.item.calculate_quantity">
                                            </el-input>
                                        </p>
                                    </td>
                                    <td class="text-right">
                                        <a class="btn btn-sm btn-default" @click="clickDeleteItem(index)">
                                            <i class="fas fa-trash fa-wf"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr v-for="(item,index) in items_refund" :key="index + 'R'">
                                    <td width="5%" style="text-align: center;" class="pos-list-label" v-if="item.unit_type">
                                        {{ item.unit_type.name }}
                                    </td>
                                    <td width="20%">
                                        <el-input :value=" '-' +item.quantity" :readonly="true" class></el-input>
                                    </td>
                                    <td width="20%">
                                        <p class="m-0">{{item.item.name}}</p>
                                        <small> {{nameSets(item.item_id)}} </small>
                                    </td>
                                    <td>
                                        <p class="font-weight-semibold m-0 text-center">{{currency.symbol}}</p>
                                    </td>
                                    <td width="20%">
                                        <p class="font-weight-semibold m-0 text-center">
                                            <el-input v-model="item.sale_unit_price_with_tax" class @input="clickAddItem(item,index,true)" :readonly="item.item.calculate_quantity">
                                            </el-input>
                                        </p>
                                    </td>
                                    <td width="30%">
                                        <p class="font-weight-semibold m-0 text-center">
                                            <el-input :value="'-' + item.total" :readonly="true">
                                            </el-input>
                                        </p>
                                    </td>
                                    <td class="text-right">
                                        <a class="btn btn-sm btn-default" @click="clickDeleteItemRefund(index)">
                                            <i class="fas fa-trash fa-wf"></i>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="h-25 bg-light" style="overflow-y: auto">
                    <div class="row border-top bg-light m-0 p-0 h-50 d-flex align-items-right pr-3 pt-2">

                        <div class="col-md-12" style="display: flex; flex-direction: column; align-items: flex-end;">
                            <table>
                                <tr class="font-weight-semibold  m-0" v-if="form.sale > 0">
                                    <td class="font-weight-semibold">SUBTOTAL</td>
                                    <td class="font-weight-semibold">:</td>
                                    <td class="text-right text-blue">{{ form.sale | numberFormat }}</td>
                                </tr>
                                <tr class="font-weight-semibold  m-0" v-if="form.total_discount > 0">
                                    <td class="font-weight-semibold">TOTAL DESCUENTO (-)</td>
                                    <td class="font-weight-semibold">:</td>
                                    <td class="text-right text-blue">{{ form.total_discount | numberFormat }}</td>
                                </tr>
                                <template v-for="(tax, index) in form.taxes">
                                    <tr v-if="((tax.total > 0) && (!tax.is_retention))" :key="index" class="font-weight-semibold  m-0">
                                        <td class="font-weight-semibold">
                                            {{tax.name}}[+]
                                        </td>
                                        <td class="font-weight-semibold">:</td>
                                        <td class="text-right text-blue">{{ tax.total | numberFormat }}</td>
                                    </tr>
                                </template>
                                <tr class="font-weight-semibold  m-0" v-if="form.subtotal > 0">
                                    <td class="font-weight-semibold">TOTAL VENTA</td>
                                    <td class="font-weight-semibold">:</td>
                                    <td class="text-right text-blue">{{ form.subtotal | numberFormat }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row text-white m-0 p-0 h-50 d-flex align-items-center" @click="clickPayment" v-bind:class="[form.total > 0 ? 'bg-info pointer' : 'bg-dark']">
                        <div class="col-6 text-center h5">
                            <i class="fa fa-chevron-circle-right"></i>
                            <span class="font-weight-semibold">PAGO</span>
                        </div>
                        <div class="col-6 text-center">
                            <h5 class="font-weight-semibold h5">{{ form.total | numberFormat }}</h5>
                        </div>
                    </div>
                </div>
            </div>

            <person-form :showDialog.sync="showDialogNewPerson" type="customers" :input_person="input_person" :external="true" :document_type_id="form.document_type_id"></person-form>

            <item-form :showDialog.sync="showDialogNewItem" :external="true"></item-form>
        </div>
        <template v-else>
            <payment-form 
                :is_payment.sync="is_payment" 
                :form="form" 
                :items_refund="items_refund" 
                :currency-type-id-active="form.currency_id" 
                :currency-type-active="currency" 
                :exchange-rate-sale="form.exchange_rate_sale" 
                :customer="customer" 
                :soapCompany="soapCompany"
                @reload-data="reloadTotals">
            </payment-form>
        </template>

        <history-sales-form :showDialog.sync="showDialogHistorySales" :item_id="history_item_id" :customer_id="form.customer_id"></history-sales-form>

        <history-purchases-form :showDialog.sync="showDialogHistoryPurchases" :item_id="history_item_id"></history-purchases-form>

        <warehouses-detail :showDialog.sync="showWarehousesDetail" :warehouses="warehousesDetail" :unit_type="unittypeDetail"></warehouses-detail>
    </div>
    <div v-else>
        <div class="text-center">
            <br>
            <br>
            <br>
            <br>
            <br>
            <i class="fas fa-chevron-circle-right fa fw h5"></i>
            <span class="font-weight-semibold h5">USUARIO NO VALIDO PARA ESTA CAJA</span>
            <i class="fas fa-chevron-circle-left fa fw h5"></i>
        </div>
    </div>
</div>
</template>

<style>
/* The heart of the matter */
.favorite-star .fa-star,
.btn-primary-pos .fa-star {
    color: #fff !important;
}
.favorite-star .fa-star.text-warning,
.btn-primary-pos .fa-star.text-warning {
    color: #ffc107 !important;
}
.favorite-star .fa-star.text-secondary,
.btn-primary-pos .fa-star.text-secondary {
    color: #fff !important;
}
.testimonial-group>.row {
    overflow-x: auto;
    white-space: nowrap;
    overflow-y: hidden;
}

.testimonial-group>.row>.col-sm-3 {
    display: inline-block;
    float: none;
}

/* Decorations */
.col-sm-3 {
    height: 70px;
    margin-right: 0.5%;
    color: white;
    font-size: 18px;
    padding-bottom: 20px;
    padding-top: 18px;
    font-weight: bold
}

.card-block {
    min-height: 220px;
}

.ex1 {
    overflow-x: scroll;
}

.cat_c {
    width: 100px;
    margin: 1%;
    padding: 3px;
    font-weight: bold;
    color: white;
    min-height: 90px;
}

.cat_c p {
    color: white;
}

.c-width {
    width: 80px !important;
    padding: 0 !important;
    margin-right: 0 !important;
}

.el-select-dropdown {
    max-width: 80% !important;
    margin-right: 1% !important;
}

.el-input-group__append {
    padding: 0 10px !important;
}

.input-text-right {
    text-align: right;
}

.truncate-text {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.lote-info {
  display: block;
  font-size: 11px !important;
  color: #888 !important;
  margin: 0 !important;
  padding: 0 !important;
  line-height: 1.1 !important;
  white-space: normal !important;
}
.page-header .header-controls-row[data-v-5563e231]{
    min-height: auto !important;
}
.el-autocomplete.m-bottom {
    width: 100% !important;
}
.el-autocomplete.m-bottom .el-input {
    width: 100% !important;
}
.el-autocomplete.m-bottom .el-input__inner {
    width: 100% !important;
}
/* --- INICIO: Responsive para listado de items --- */
@media (max-width: 1000px) {
  .row.pos-items > div[class^="col-"], 
  .row.pos-items > div[class*=" col-"] {
    flex: 0 0 100%;
    max-width: 100%;
    padding-left: 0 !important;
    padding-right: 0 !important;
  }
  .row.pos-items {
    flex-direction: column;
    display: flex;
  }
}
@media (max-width: 1800px) {
  /* Estilos para la tabla de productos seleccionados en modo responsive */
  .table-pos-products .pos-product-row {
    display: block;
    border-bottom: 1px solid #eee;
    margin-bottom: 2px;
    padding-bottom: 2px;
    overflow-x: auto;
    white-space: nowrap;
  }
  .table-pos-products .td-main {
    display: block;
    width: 100% !important;
    padding: 4px 2px !important;
    box-sizing: border-box;
    white-space: nowrap;
  }
  .table-pos-products .row-main {
    display: flex;
    align-items: center;
    width: 100%;
    gap: 8px;
    min-width: fit-content;
  }
  .table-pos-products .input-qty {
    flex: 0 0 60px;
    max-width: 60px;
    min-width: 40px;
    margin-right: 6px;
  }
  .table-pos-products .product-info {
    flex: 1 1 auto;
    white-space: normal;
    min-width: 150px;
  }
  .table-pos-products .product-name {
    font-size: 14px;
    line-height: 1.2;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    max-height: calc(1.2em * 2); /* 2 líneas * line-height */
    word-break: break-word;
    margin-bottom: 2px;
  }
  .table-pos-products .product-details {
    display: flex;
    flex-direction: column;
    gap: 1px;
    white-space: normal;
  }
  .table-pos-products .product-details small {
    display: block;
    font-size: 12px;
    color: #888;
    margin-top: 0;
    margin-bottom: 0;
    white-space: normal;
    word-break: break-word;
    line-height: 1.1;
  }
  .table-pos-products .row-secondary {
    display: flex;
    align-items: center;
    width: 55%;
    gap: 8px;
    margin-top: 2px;
    min-width: 256px !important;
  }
  .table-pos-products .row-secondary input{
    padding: 0 5px !important;
  }
  .table-pos-products .input-price,
  .table-pos-products .input-total {
    flex: 1 1 0;
    min-width: 0;
    font-size: 13px !important;
    height: 28px !important;
    min-height: 28px !important;
  }
  .table-pos-products .btn-trash {
    flex: 0 0 36px;
    max-width: 36px;
    margin-left: auto;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
/* --- FIN: Responsive para tabla de productos seleccionados --- */
.product-image-responsive {
  /* fallback: ocultar imagen en pantallas pequeñas */
  display: block;
}
@media (max-width: 600px) {
  .product-image-responsive {
    display: none !important;
  }
}

/* Clase para limitar el nombre del producto en vista de escritorio a 2 líneas */
.product-name-desktop {
  max-height: calc(1em * 2 + 0.5em); /* 2 líneas + espacio para el <br> */
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  word-break: break-word;
}
</style>

<script>
// import { calculateRowItem } from "../../../helpers/functions";
import PaymentForm from "./partials/payment.vue";
import ItemForm from "./partials/form.vue";
import HistorySalesForm from "../../../../../modules/Pos/Resources/assets/js/views/history/sales.vue";
import HistoryPurchasesForm from "../../../../../modules/Pos/Resources/assets/js/views/history/purchases.vue";
import PersonForm from "../persons/form.vue";
import WarehousesDetail from '../items/partials/warehouses.vue'
import queryString from "query-string";
import {functions} from '@mixins/functions';
import scaleMixin from '@mixins/scaleMixin'

export default {
    props: ['configuration', 'soapCompany'],
    components: {
        PaymentForm,
        ItemForm,
        HistorySalesForm,
        HistoryPurchasesForm,
        PersonForm,
        WarehousesDetail
    },
    mixins: [functions, scaleMixin],
    data() {
        return {
            place: 'cat',
            history_item_id: null,
            search_item_by_barcode: false,
            warehousesDetail: [],
            unittypeDetail: [],
            input_person: {},
            showDialogHistoryPurchases: false,
            showDialogHistorySales: false,
            showDialogNewPerson: false,
            showDialogNewItem: false,
            loading: false,
            is_payment: false, //aq
            // is_payment: true,//aq
            showWarehousesDetail: false,
            resource: "pos",
            recordId: null,
            input_item: "",
            items: [],
            all_items: [],
            customers: [],
            currencies: [],
            taxes: [],
            all_customers: [],
            establishment: null,
            currency: {},
            form_item: {},
            customer: {},
            row: {},
            user: {},
            form: {},
            categories: [],
            colors: ['#1cb973', '#bf7ae6', '#fc6304', '#9b4db4', '#77c1f3'],
            type_refund: false,
            items_refund: [],
            pagination: {},
            category_selected: "",
            plate_number_valid: true,
            electronic: false,
            advanced_configuration: {},
            isMobile: window.innerWidth <= 1800,
            windowWidth: window.innerWidth, // <-- agregar para computada
        };
    },

    mounted(){
        window.addEventListener('resize', this.handleResize);
    },
    beforeDestroy() {
        window.removeEventListener('resize', this.handleResize);
        this.disconnectScale();
    },
    async created() {
        try {
            // Cargar configuración avanzada antes de todo
            await this.$http.get('/co-advanced-configuration/record').then(response => {
                this.advanced_configuration = response.data.data
            })
            // Verificar y establecer plate_number inicial
            const configPlateNumber = this.configuration?.configuration_pos?.plate_number;
            let storedPlateNumber = localStorage.getItem("plate_number");

            // Si no hay plate_number almacenado pero existe en la configuración
            if (!storedPlateNumber && configPlateNumber) {
                localStorage.setItem("plate_number", configPlateNumber);
                storedPlateNumber = configPlateNumber;
            }

            this.electronic = this.configuration?.configuration_pos?.electronic || false;

            // Validación mejorada
            const isValidPlate = !this.electronic || 
                (storedPlateNumber && configPlateNumber && 
                 storedPlateNumber.toString().trim() === configPlateNumber.toString().trim());

            if (isValidPlate) {
                this.plate_number_valid = true;
                await this.initForm();
                await this.getTables();
                this.events();
                await this.getFormPosLocalStorage();
                this.customer = await this.getLocalStorageIndex('customer');
            } else {
                console.warn('Validación de placa fallida:', {
                    stored: storedPlateNumber,
                    config: configPlateNumber,
                    electronic: this.electronic
                });
                this.plate_number_valid = false;

                // Si hay discrepancia entre los números de placa
                if (configPlateNumber && storedPlateNumber !== configPlateNumber) {
                    localStorage.setItem("plate_number", configPlateNumber);
                    location.reload();
                }
            }
        } catch (error) {
            console.error('Error en created():', error);
            this.plate_number_valid = false;
        }
    },

    computed: {
        classObjectCol() {

            let cols = this.configuration.colums_grid_item

            let clase = 'c3'
            switch (cols) {
                case 2:
                    clase = '6'

                    break;
                case 3:
                    clase = '4'

                    break;
                case 4:
                    clase = '3'

                    break;
                case 5:
                    clase = '2'

                    break;
                case 6:
                    clase = '2'
                    break;
                default:

            }
            return {
                [`col-md-${clase}`]: true
            }
        },
        hideProductImage() {
            return this.windowWidth < 600;
        },
    },
    watch: {
        async input_item(val) {
            // Si está activo el modo código de barras, el watch NO hace nada
            if (this.search_item_by_barcode) return;

            if (!val || val.trim().length === 0) {
                // Si está vacío, mostrar todos los productos de la página actual
                await this.getRecords();
            } else {
                // Buscar en el backend, igual que el autocomplete
                this.loading = true;
                try {
                    let url = `/${this.resource}/search_items?input_item=${encodeURIComponent(val.trim())}`;
                    if (this.category_selected) {
                        url += `&cat=${encodeURIComponent(this.category_selected)}`;
                    }
                    if (this.search_item_by_barcode) {
                        url += `&barcode_only=1`;
                    }
                    const response = await this.$http.get(url);
                    this.items = response.data.data;
                    this.pagination = response.data.meta;
                    this.pagination.per_page = parseInt(response.data.meta.per_page);
                    this.pagination.total = response.data.meta.total;
                    // if (this.search_item_by_barcode && this.items.length === 1) {
                    //     await this.clickAddItem(this.items[0], 0);
                    //     this.input_item = ""; // Limpia el input después de agregar
                    // }
                } catch (e) {
                    this.items = [];
                }
                this.loading = false;
            }
        }
    },
    methods: {
        async onBarcodeChange() {
            if (this.search_item_by_barcode) {
                // Forzar búsqueda antes de intentar agregar
                this.loading = true;
                try {
                    let url = `/${this.resource}/search_items?input_item=${encodeURIComponent(this.input_item.trim())}`;
                    if (this.category_selected) {
                        url += `&cat=${encodeURIComponent(this.category_selected)}`;
                    }
                    url += `&barcode_only=1`;
                    const response = await this.$http.get(url);
                    this.items = response.data.data;
                    this.pagination = response.data.meta;
                    this.pagination.per_page = parseInt(response.data.meta.per_page);
                    this.pagination.total = response.data.meta.total;

                    if (this.items.length === 1) {
                        await this.clickAddItem(this.items[0], 0);
                        this.input_item = ""; // Limpia el input después de agregar
                    }
                } catch (e) {
                    this.items = [];
                }
                this.loading = false;
            }
        },
        async querySearchAsync(queryString, cb) {
            if (!queryString || queryString.length < 1) {
                return cb([]);
            }
            try {
                let url = `/${this.resource}/search_items?input_item=${encodeURIComponent(queryString)}`;
                if (this.category_selected) {
                    url += `&cat=${encodeURIComponent(this.category_selected)}`;
                }
                const response = await this.$http.get(url);
                let results = [];
                response.data.data.forEach(item => {
                    let stock = '';
                    if (item.warehouses && Array.isArray(item.warehouses)) {
                        const wh = item.warehouses.find(w => w.warehouse_id == this.establishment.id || w.id == this.establishment.id);
                        stock = wh ? wh.stock : 0;
                    }
                    results.push({
                        value: `${item.name} ${item.internal_id ? '(' + item.internal_id + ')' : ''} - ${this.getFormatDecimal(item.sale_unit_price)} ${this.currency.symbol} | Stock: ${stock}`,
                        itemData: item
                    });
                });
                cb(results);
            } catch (e) {
                cb([]);
            }
        },
        handleSelectProduct(option) {
            // Al seleccionar, agrega el producto
            this.clickAddItem(option.itemData, 0);
            this.input_item = '';
        },
        async toggleFavorite(item) {
            this.loading = true;
            try {
                const res = await this.$http.post(`/pos/toggle-favorite/${item.item_id}`);
                item.is_favorite = res.data.is_favorite;
                this.sortItemsByFavorites();
                this.$message.success(res.data.message);
            } catch (e) {
                this.$message.error('Error al marcar favorito');
            }
            this.loading = false;
        },
        isFavorite(item) {
            return !!item.is_favorite;
        },
        sortItemsByFavorites() {
            this.items = [...this.items].sort((a, b) => {
                const aFav = a.is_favorite ? 1 : 0;
                const bFav = b.is_favorite ? 1 : 0;
                return bFav - aFav;
            });
        },
        getQueryParameters() {
            return queryString.stringify({
                page: this.pagination.current_page
                    ? this.pagination.current_page
                    : 1,
                input_item: this.input_item,
                cat: this.category_selected,
                limit: this.limit
            });
        },
        getRecords() {
            this.loading = true;
            return this.$http
                .get(
                    `/${this.resource}/search_items?${this.getQueryParameters()}&cat=${
                        this.category_selected
                    }`
                )
                .then(response => {
                    this.all_items = response.data.data;
                    this.items = response.data.data;
                    this.filterItems();
                    this.pagination = response.data.meta;
                    this.pagination.per_page = parseInt(
                        response.data.meta.per_page
                    );
                    this.loading = false;
                    if (response.data.meta.total > 0) {
                        this.pagination.total = response.data.meta.total;
                    } else {
                        this.pagination.total = 0;
                    }
                    this.sortItemsByFavorites();
                });
        },
        setListPriceItem(item_unit_type, index) {

            let list_price = 0

            switch (item_unit_type.price_default) {
                case 1:
                    list_price = item_unit_type.price1
                    break
                case 2:
                    list_price = item_unit_type.price2
                    break
                case 3:
                    list_price = item_unit_type.price3
                    break
            }

            this.items[index].sale_unit_price = parseFloat(list_price)
            this.items[index].unit_type_id = item_unit_type.unit_type_id
            this.items[index].unit_type = item_unit_type.unit_type
            this.items[index].presentation = item_unit_type

            this.$message.success("Precio seleccionado")
        },
        filterCategorie(id, mod = false) {

            if (id) {
                this.category_selected = id;
                this.getRecords();
            } else {
                this.category_selected = "";
                this.getRecords();
            }

            if (mod) {
                this.place = 'cat2'
            } else {
                this.place = 'prod'
            }

        },
        getColor(i) {
            return this.colors[(i % this.colors.length)]
        },
        initCurrencyType() {
            this.currency = _.find(this.currencies, {
                'id': this.form.currency_id
            })
        },
        getFormPosLocalStorage() {
            let form_pos = localStorage.getItem('form_pos');
            form_pos = JSON.parse(form_pos)
            if (form_pos) {
                this.form = form_pos
                // this.calculateTotal()
            }

            if (!this.form.customer_id) {
                const customer_default = _.find(this.all_customers, {'number': '222222222222'}) ?? null
                if (customer_default) {
                    this.form.customer_id = customer_default.id
                    this.changeCustomer()
                }
            }

        },
        setFormPosLocalStorage(form_param = null) {

            if (form_param) {

                localStorage.setItem('form_pos', JSON.stringify(form_param));

            } else {

                localStorage.setItem('form_pos', JSON.stringify(this.form));
            }

        },
        cancelFormPosLocalStorage() {

            localStorage.setItem('form_pos', JSON.stringify(null));
            this.setLocalStorageIndex('customer', null)

        },
        clickOpenInputEditUP(index) {
            this.items[index].edit_unit_price = true;
            // Inicializa el valor editable según la configuración
            this.items[index].edit_sale_unit_price = !this.advanced_configuration.item_tax_included
                ? this.items[index].sale_unit_price
                : this.items[index].sale_unit_price_with_tax;
        },
        clickEditUnitPriceItem(index) {
            if (!this.advanced_configuration.item_tax_included) {
                // El precio editado ya incluye impuesto
                this.items[index].sale_unit_price = this.items[index].edit_sale_unit_price;
                this.items[index].sale_unit_price_with_tax = this.items[index].edit_sale_unit_price;
            } else {
                // El precio editado es sin impuesto, calcular el precio con impuesto
                let price_with_tax = this.items[index].edit_sale_unit_price;
                this.items[index].sale_unit_price_with_tax = price_with_tax;
                this.items[index].sale_unit_price = price_with_tax / (1 + (this.items[index].tax.rate / this.items[index].tax.conversion));
            }
            this.items[index].edit_unit_price = false;
        },
        clickCancelUnitPriceItem(index) {
            // console.log(index)
            this.items[index].edit_unit_price = false

        },
        clickWarehouseDetail(item) {
            this.unittypeDetail = item.unit_type
            this.warehousesDetail = item.warehouses
            this.showWarehousesDetail = true
        },
        clickHistoryPurchases(item_id) {

            this.history_item_id = item_id
            this.showDialogHistoryPurchases = true
            // console.log(item)
        },
        clickHistorySales(item_id) {
            if (!this.form.customer_id)
                return this.$message.error("Debe seleccionar el cliente")

            this.history_item_id = item_id
            this.showDialogHistorySales = true
            // console.log(item)
        },
        keyupEnterCustomer() {

            if (this.input_person.number) {

                if (!isNaN(parseInt(this.input_person.number))) {

                    switch (this.input_person.number.length) {
                        case 8:
                            this.input_person.identity_document_type_id = '1'
                            this.showDialogNewPerson = true
                            break;

                        case 11:
                            this.input_person.identity_document_type_id = '6'
                            this.showDialogNewPerson = true
                            break;
                        default:
                            this.input_person.identity_document_type_id = '6'
                            this.showDialogNewPerson = true
                            break;
                    }
                }
            }
        },
        keyupCustomer(e) {

            if (e.key !== "Enter") {

                this.input_person.number = this.$refs.select_person.$el.getElementsByTagName('input')[0].value
                let exist_persons = this.all_customers.filter((customer) => {
                    let pos = customer.description.search(this.input_person.number);
                    return (pos > -1)
                })

                this.input_person.number = (exist_persons.length == 0) ? this.input_person.number : null

            }

        },
        calculateQuantity(index) {
            // console.log(this.form.items[index])
            if (this.form.items[index].item.calculate_quantity) {
                let quantity = _.round(
                    parseFloat(this.form.items[index].total) /
                    parseFloat(this.form.items[index].unit_price),
                    4
                );

                if (quantity) {
                    // Validación de stock mínimo y stock suficiente
                    if (
                        this.advanced_configuration &&
                        this.advanced_configuration.validate_min_stock &&
                        this.form.items[index].item.warehouses &&
                        this.form.items[index].item.unit_type_id !== 'ZZ'
                    ) {
                        const warehouse = this.form.items[index].item.warehouses.find(w => w.checked) || this.form.items[index].item.warehouses[0];
                        const stock = warehouse ? warehouse.stock : 0;
                        const stock_min = this.form.items[index].item.stock_min !== undefined ? this.form.items[index].item.stock_min : 0;
                        if (Number(stock) < Number(stock_min)) {
                            this.$message.error('El stock actual es menor al stock mínimo para este producto.');
                            // Revertir cantidad
                            this.form.items[index].quantity = stock;
                            this.form.items[index].item.aux_quantity = stock;
                            return;
                        }
                        if (Number(quantity) > Number(stock)) {
                            this.$message.error('No hay stock suficiente para este producto.');
                            // Revertir cantidad
                            this.form.items[index].quantity = stock;
                            this.form.items[index].item.aux_quantity = stock;
                            return;
                        }
                    }
                    this.form.items[index].quantity = quantity;
                    this.form.items[index].item.aux_quantity = quantity;
                } else {
                    this.form.items[index].quantity = 0;
                    this.form.items[index].item.aux_quantity = 0;
                }
            }
            
        },

        changeCustomer() {
            let customer = _.find(this.all_customers, {
                id: this.form.customer_id
            });
            this.customer = customer;
            this.form.document_type_id = '90';
            this.setLocalStorageIndex('customer', this.customer)
            this.setFormPosLocalStorage()
        },

        getLocalStorageIndex(key, re_default = null) {

            let ls_obj = localStorage.getItem(key);
            ls_obj = JSON.parse(ls_obj)

            if (ls_obj) {
                return ls_obj
            }

            return re_default
        },
        setLocalStorageIndex(key, obj) {
            localStorage.setItem(key, JSON.stringify(obj));
        },
        async events() {

            await this.$eventHub.$on('initInputPerson', () => {
                this.initInputPerson()
            })

            await this.$eventHub.$on('eventSetFormPosLocalStorage', (form_param) => {
                this.setFormPosLocalStorage(form_param)
            })

            await this.$eventHub.$on("cancelSale", () => {
                this.is_payment = false;
                this.initForm();
                this.getTables();
                this.filterItems();
                this.changeExchangeRate();
                this.cancelFormPosLocalStorage();
                const customer_default = _.find(this.all_customers, {'number': '222222222222'}) ?? null
                if (customer_default) {
                    this.form.customer_id = customer_default.id
                    this.changeCustomer()
                }
            });

            await this.$eventHub.$on("reloadDataPersons", customer_id => {
                this.reloadDataCustomers(customer_id);
                this.setFormPosLocalStorage()
            });

            await this.$eventHub.$on("reloadDataItems", item_id => {
                this.reloadDataItems(item_id);
            });

            await this.$eventHub.$on("saleSuccess", () => {
                // this.is_payment = false
                this.initForm();
                this.getTables();
                this.setFormPosLocalStorage()
                this.items_refund = []
            });
        },

        reloadTotals() {
            this.calculateTotal()
            this.filterItems()
            this.changeDateOfIssue()
            this.changeExchangeRate()
            this.setFormPosLocalStorage()
        },

        initForm() {
            this.form = {
                customer_id: null,
                document_type_id: '01',
                series_id: null,
                establishment_id: null,
                type_document_id: 1,
                currency_id: 170,
                date_issue: moment().format('YYYY-MM-DD'),
                date_of_issue: moment().format('YYYY-MM-DD'),
                time_of_issue: moment().format('HH:mm:ss'),
                exchange_rate_sale: 0,
                date_expiration: null,
                type_invoice_id: 1,
                total_discount: 0,
                total_tax: 0,
                watch: false,
                subtotal: 0,
                items: [],
                taxes: [],
                total: 0,
                sale: 0,
                time_days_credit: 0,
                service_invoice: {},
                payment_form_id: 1,
                payment_method_id: 1,
                payments: [],
                electronic: false,
                seller_id: null,
                head_note: this.advanced_configuration.head_note || '',
                foot_note: this.advanced_configuration.foot_note || '',
            }
            this.initFormItem();
            this.changeDateOfIssue();
            this.initInputPerson()
        },

        initInputPerson() {
            this.input_person = {
                number: '',
                identity_document_type_id: ''
            }
        },

        initFormItem() {
            this.form_item = {
                id: null,
                item_id: null,
                item: {},
                code: null,
                discount: 0,
                name: null,
                unit_price_value: 0,
                unit_price: 0,
                quantity: 1,
                aux_quantity: 1,
                subtotal: null,
                tax: {},
                tax_id: null,
                total: 0,
                total_tax: 0,
                edited_price: false,
                type_unit: {},
                unit_type_id: null,
                item_unit_types: [],
                IdLoteSelected: null,
                sale_unit_price_with_tax: 0,
                refund: false
            };
            //this.items_refund = []
        },

        async clickPayment() {

            let flag = 0;
            this.form.items.forEach(row => {
                if (row.aux_quantity < 0 || row.total < 0 || isNaN(row.total)) {
                    flag++;
                }
            });

            if (flag > 0)
                return this.$message.error("Cantidad negativa o incorrecta");
            if (!this.form.customer_id)
                return this.$message.error("Seleccione un cliente");
            if (!this.form.items[0])
                return this.$message.error("Seleccione un producto");

            this.form.establishment_id = this.establishment.id;
            this.loading = true;
            await this.sleep(800);
            this.is_payment = true;
            this.loading = false;

        },
        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },
        clickDeleteCustomer() {
            this.form.customer_id = null;
            this.setFormPosLocalStorage()
        },
        async clickAddItem(item, index, input = false) {
            // Validar stock mínimo si la opción está activa y no es devolución
            if (!this.type_refund && this.advanced_configuration && this.advanced_configuration.validate_min_stock) {
                if (item.warehouses && item.unit_type_id !== 'ZZ') {
                    const warehouse = item.warehouses.find(w => w.checked) || item.warehouses[0];
                    const stock = warehouse ? warehouse.stock : 0;
                    const stock_min = item.stock_min !== undefined ? item.stock_min : 0;
                    if (Number(stock) < Number(stock_min)) {
                        this.$message.error('El stock actual es menor al stock mínimo para este producto.');
                        return;
                    }
                    // Si ya existe el item, sumar la cantidad
                    let exist_item = null;
                    if(!item.presentation) {
                        exist_item = _.find(this.form.items, {
                            item_id: item.item_id,
                            unit_type_id: item.unit_type_id
                        })
                    } else {
                        exist_item = _.find(this.form.items, {
                            item_id: item.item_id,
                            presentation: item.presentation,
                            unit_type_id: item.unit_type_id
                        })
                    }
                    let next_quantity = exist_item ? (parseFloat(exist_item.item.aux_quantity) + (input ? 0 : 1)) : 1;
                    if (Number(next_quantity) > Number(stock)) {
                        this.$message.error('No hay stock suficiente para este producto.');
                        return;
                    }
                }
            }
            const presentation = item.presentation
            // console.log(item)
            if (this.type_refund) {
//                console.log("Aqui devolucion...")
                this.form_item.item = item;
                if (!this.advanced_configuration.item_tax_included) {
                    // El precio mostrado incluye impuesto, pero internamente debe ser sin impuesto
                    if (item.tax && item.tax.rate && item.tax.conversion) {
                        this.form_item.unit_price_value = item.sale_unit_price / (1 + (item.tax.rate / item.tax.conversion));
                    } else {
                        // Si no hay impuesto, usa el precio tal cual
                        this.form_item.unit_price_value = item.sale_unit_price;
                    }
                    this.form_item.unit_price = this.form_item.unit_price_value;
                    this.form_item.item.unit_price = this.form_item.unit_price_value;
                    this.form_item.sale_unit_price = this.form_item.unit_price_value;
                } else {
                    // El precio mostrado es sin impuesto
                    this.form_item.unit_price_value = item.sale_unit_price;
                    this.form_item.unit_price = item.sale_unit_price;
                    this.form_item.item.unit_price = item.sale_unit_price;
                    this.form_item.sale_unit_price = item.sale_unit_price;
                }
                this.form_item.quantity = 1;
                this.form_item.aux_quantity = 1;

                let unit_price = this.form_item.unit_price_value;

                this.form_item.unit_price = unit_price;
                this.form_item.item.unit_price = unit_price;
                this.form_item.item.presentation = null;

                // this.form_item.id = this.form_item.item.item_id
                this.form_item.item_id = this.form_item.item.item_id
                this.form_item.unit_type_id = this.form_item.item.unit_type_id
                this.form_item.tax_id = (this.taxes.length > 0) ? this.form_item.item.tax.id : null
                this.form_item.tax = _.find(this.taxes, {
                    'id': this.form_item.tax_id
                })
                this.form_item.unit_type = this.form_item.item.unit_type
                this.form_item.refund = true
                this.form_item.sale_unit_price_with_tax = -1 * item.sale_unit_price_with_tax
                this.items_refund.push(this.form_item);
                //item.aux_quantity = 1;
            } else {
                this.loading = true;
                // let exchangeRateSale = this.form.exchange_rate_sale;
                // let exist_item = _.find(this.form.items, {
                //     item_id: item.item_id
                // });

                let exist_item = null

                if(!presentation) {

                    exist_item = _.find(this.form.items, {
                        item_id: item.item_id,
                        unit_type_id: item.unit_type_id
                    })

                }else{

                    exist_item = _.find(this.form.items, {
                        item_id: item.item_id,
                        presentation: presentation,
                        unit_type_id: item.unit_type_id
                    })
                }


                let pos = this.form.items.indexOf(exist_item);
                let response = null;

                if (exist_item) {
                    item.edited_price = input
                    if (input) {
                        response = await this.getStatusStock(item.item_id, exist_item.item.aux_quantity);

                        if (!response.success) {
                            item.item.aux_quantity = item.quantity;
                            this.loading = false;
                            return this.$message.error(response.message);
                        }

                        exist_item.quantity = Number(Number(exist_item.item.aux_quantity).toFixed(4));
                    } else {
                        // Corregir suma de cantidades para evitar decimales extraños
                        let newQty = Number(Number(exist_item.item.aux_quantity) + 1);
                        newQty = Number(newQty.toFixed(4));
                        response = await this.getStatusStock(item.item_id, newQty);

                        if (!response.success) {
                            this.loading = false;
                            return this.$message.error(response.message);
                        }

                        exist_item.quantity = newQty;
                        exist_item.item.aux_quantity = newQty;
                    }

                    let search_item_bd = await _.find(this.items, {
                        item_id: item.item_id
                    });

                    if (search_item_bd) {
                        exist_item.item.unit_price = parseFloat(search_item_bd.sale_unit_price)
                    }

                    let unit_price = exist_item.item.sale_unit_price
                    exist_item.item.unit_price = unit_price

                    exist_item.unit_type_id = item.unit_type_id

                    this.form.items[pos] = exist_item;

                } else {

                    response = await this.getStatusStock(item.item_id, 1);

                    if (!response.success) {
                        this.loading = false;
                        return this.$message.error(response.message);
                    }

                    this.form_item.item = { ...item }
                    // this.form_item.item = item;
                    if (!this.advanced_configuration.item_tax_included) {
                        if (item.tax && item.tax.rate && item.tax.conversion) {
                            this.form_item.unit_price_value = item.sale_unit_price / (1 + (item.tax.rate / item.tax.conversion));
                        } else {
                            // Si no hay impuesto, usa el precio tal cual
                            this.form_item.unit_price_value = item.sale_unit_price;
                        }
                        this.form_item.unit_price = this.form_item.unit_price_value;
                        this.form_item.item.unit_price = this.form_item.unit_price_value;
                        this.form_item.sale_unit_price = this.form_item.unit_price_value;
                    } else {
                        this.form_item.unit_price_value = item.sale_unit_price;
                        this.form_item.unit_price = item.sale_unit_price;
                        this.form_item.item.unit_price = item.sale_unit_price;
                        this.form_item.sale_unit_price = item.sale_unit_price;
                    }
                    this.form_item.quantity = 1;
                    this.form_item.aux_quantity = 1;

                    let unit_price = this.form_item.unit_price_value;

                    this.form_item.unit_price = unit_price;
                    this.form_item.item.unit_price = unit_price;
                    // this.form_item.item.presentation = null;

                    // this.form_item.id = this.form_item.item.item_id
                    this.form_item.item_id = this.form_item.item.item_id
                    this.form_item.tax_id = (this.taxes.length > 0) ? (this.form_item.item.tax !== null ? this.form_item.item.tax.id : null) : null
                    this.form_item.tax = _.find(this.taxes, {
                        'id': this.form_item.tax_id
                    })

                    // lista precios
                    if(presentation)
                    {
                        this.form_item.presentation = presentation
                        this.form_item.unit_type_id = presentation.unit_type_id
                        this.form_item.unit_type = presentation.unit_type

                    }else
                    {
                        this.form_item.presentation = null
                        this.form_item.unit_type_id = this.form_item.item.unit_type_id
                        this.form_item.unit_type = this.form_item.item.unit_type
                    }


                    this.form.items.push(this.form_item);
                    item.aux_quantity = 1;

                }

                if(!input)
    	                this.$notify({
                        title: "",
                        message: "Producto añadido!",
                        type: "success",
                        duration: 700
                    });

            }

            // console.log(this.form.items)
            await this.calculateTotal();
            this.loading = false;
            await this.setFormPosLocalStorage()
            await this.initFormItem()
        },
        async getStatusStock(item_id, quantity) {
            let data = {};
            if (!quantity) quantity = 0;
            await this.$http
                .get(`/${this.resource}/validate_stock/${item_id}/${quantity}`)
                .then(response => {
                    data = response.data;
                });
            return data;
        },
        async clickDeleteItem(index) {
            this.form.items.splice(index, 1);

            this.calculateTotal();

            await this.setFormPosLocalStorage()
        },
        async clickDeleteItemRefund(index) {
            this.items_refund.splice(index, 1);
            this.calculateTotal();
            await this.setFormPosLocalStorage()
        },

        calculateTotal() {
            this.setDataTotals()
        },
        changeDateOfIssue() {
            // this.searchExchangeRateByDate(this.form.date_of_issue).then(response => {
            //     this.form.exchange_rate_sale = response
            // })

        },
        setDataTotals() {
            let val = this.form
            val.taxes = this.taxes;
            val.taxes.forEach(tax => {
                tax.total = 0
            });

            val.items.forEach(item => {
                item.tax = this.taxes.find(tax => tax.id == item.tax_id);

                if (
                    item.discount == null ||
                    item.discount == "" ||
                    item.discount > item.unit_price * item.quantity
                ) {
                    this.$set(item, "discount", 0);
                }

                if (item.tax != null) {

                    let tax = val.taxes.find(tax => tax.id == item.tax.id);
//                    tax.total = 0

                    if (item.tax.is_fixed_value)
                        item.total_tax = (
                            item.tax.rate * item.quantity -
                            (item.discount < item.unit_price * item.quantity ? item.discount : 0)
                        ).toFixed(2);

//                    console.log(item)
                    if (item.tax.is_percentage) {
                        if(!item.edited_price){
                            item.total_tax = (
                                (item.unit_price * item.quantity -
                                    (item.discount < item.unit_price * item.quantity ? 
                                        item.discount : 
                                        0)) *
                                (item.tax.rate / item.tax.conversion)
                            ).toFixed(2);
                        }
                        else{
//                            console.log("Aquui 2");
//                            console.log(item.unit_price)
//                            console.log(item.sale_unit_price_with_tax)
//                            console.log(item.tax.rate)
//                            console.log(item.tax.conversion)
//                            console.log(1 + (item.tax.rate / item.tax.conversion))
                            item.unit_price = (item.sale_unit_price_with_tax / (1 + (item.tax.rate / item.tax.conversion)))
//                            console.log(item.unit_price)
                            item.total_tax = (
                                (item.unit_price * item.quantity - 
                                    (item.discount < item.sale_unit_price_with_tax * item.quantity ? 
                                        item.discount : 
                                        0)) *
                                (item.tax.rate / item.tax.conversion)
                            ).toFixed(2);
                        }
                    }

                    if (!tax.hasOwnProperty("total")) {
                        tax.total = Number(0).toFixed(2);
                    }
//                    console.log(tax.total)
//                    console.log(item.total_tax)
                    tax.total = (Number(tax.total) + Number(item.total_tax)).toFixed(2);
//                    console.log(tax.total)
                }
                // Asegurar que las cantidades y totales sean números con precisión controlada
                item.quantity = Number(Number(item.quantity).toFixed(4));
                item.item.aux_quantity = Number(Number(item.item.aux_quantity).toFixed(4));
                if(!item.edited_price){
                    item.subtotal = (
                        Number(item.unit_price * item.quantity) + Number(item.total_tax)
                    ).toFixed(2);
                }
                else{
                    item.subtotal = (
                        Number(item.sale_unit_price_with_tax * item.quantity)
                    ).toFixed(2);
                }

                this.$set(
                    item,
                    "total",
                    Math.round(Number(item.subtotal) - Number(item.discount))
                );

                if(!item.edited_price){
                    this.$set(
                        item,
                        "sale_unit_price_with_tax",
                        Math.round(Number(item.subtotal) / Number(item.quantity))
                    );
                }
            });

            this.items_refund.forEach(item => {
                item.tax = this.taxes.find(tax => tax.id == item.tax_id);
                this.$set(item, "discount", 0);
                item.total_tax = 0;
                if (item.tax != null) {
                    let tax = val.taxes.find(tax => tax.id == item.tax.id);
                    if (item.tax.is_fixed_value) {
                        item.total_tax = (
                            item.tax.rate * item.quantity - 
                            (item.discount < item.unit_price * item.quantity ? item.discount : 0)
                        ).toFixed(2);
                    }

                    if (item.tax.is_percentage) {
                        item.total_tax = (
                            (item.unit_price * item.quantity - 
                                (item.discount < item.unit_price * item.quantity ? 
                                    item.discount : 
                                    0)) *
                            (item.tax.rate / item.tax.conversion)
                        ).toFixed(2);
                    }

                    if (!tax.hasOwnProperty("total")) {
                        tax.total = Number(0).toFixed(2);
                    }

                    tax.total = (Number(tax.total) - Number(item.total_tax)).toFixed(2);
                }

                item.subtotal = (
                    Number(item.unit_price * item.quantity) + Number(item.total_tax)
                ).toFixed(2);

                this.$set(
                    item,
                    "total",
                    ((Number(item.subtotal) - Number(item.discount))).toFixed(2)
                );

            })
            const subtotal = val.items.reduce((p, c) => Number(p) + (Number(c.subtotal) - Number(c.discount)), 0);
            const subtotal_refund = this.items_refund.reduce((p, c) => Number(p) + (Number(c.subtotal) - Number(c.discount)), 0);

            val.subtotal = (subtotal - subtotal_refund).toFixed(2)
//            console.log(val.items)
//            console.log(val.items.reduce((p, c) => Number(p) + ((Number(c.sale_unit_price_with_tax) *  Number(c.quantity))) - Number(c.total_tax) - Number(c.discount), 0))
            const sale = !val.items.edited_price ? val.items.reduce((p, c) => Number(p) + Number((c.sale_unit_price_with_tax * c.quantity) - c.total_tax) - Number(c.discount), 0) : val.items.reduce((p, c) => Number(p) + Number(c.unit_price * c.quantity) - Number(c.discount), 0);
//            console.log(this.items_refund)
//            console.log(!val.items.edited_price)

//            const sale_refund = !val.items.edited_price ? this.items_refund.reduce((p, c) => Number(p) - Number((c.sale_unit_price_with_tax  * c.quantity) + c.total_tax) - Number(c.discount), 0) : this.items_refund.reduce((p, c) => Number(p) - Number(c.unit_price * c.quantity) - Number(c.discount), 0);
//            const s1 = this.items_refund.reduce((p, c) => Number(p) - Number((c.sale_unit_price_with_tax  * c.quantity) + c.total_tax) - Number(c.discount), 0)
            const sale_refund = this.items_refund.reduce((p, c) => Number(p) - Number(c.unit_price * c.quantity) - Number(c.discount), 0)
//            console.log(sale)
//            console.log(sale_refund)
            val.sale = (sale + sale_refund).toFixed(2)
//            console.log(val.sale)
            val.total_discount = val.items
                .reduce((p, c) => Number(p) + Number(c.discount), 0)
                .toFixed(2);
//            console.log(val.items.reduce((p, c) => Number(p) + Number(c.total_tax), 0))
            val.total_tax = val.items
                .reduce((p, c) => Number(p) + Number(c.total_tax), 0)
                .toFixed(2);

            let total = val.items
                .reduce((p, c) => Number(p) + Number(c.total), 0);

            let total_refund = this.items_refund
                .reduce((p, c) => Number(p) + Number(c.total), 0);

            let totalRetentionBase = Number(0);

            // this.taxes.forEach(tax => {
            val.taxes.forEach(tax => {
                if (tax.is_retention && tax.in_base && tax.apply) {
                    tax.retention = (
                        Number(val.sale) *
                        (tax.rate / tax.conversion)
                    ).toFixed(2);

                    totalRetentionBase =
                        Number(totalRetentionBase) + Number(tax.retention);

                    if (Number(totalRetentionBase) >= Number(val.sale))
                        this.$set(tax, "retention", Number(0).toFixed(2));

                    total -= Number(tax.retention).toFixed(2);
                }

                if (
                    tax.is_retention &&
                    !tax.in_base &&
                    tax.in_tax != null &&
                    tax.apply
                ) {
                    let row = val.taxes.find(row => row.id == tax.in_tax);

                    tax.retention = Number(
                        Number(row.total) * (tax.rate / tax.conversion)
                    ).toFixed(2);

                    if (Number(tax.retention) > Number(row.total))
                        this.$set(tax, "retention", Number(0).toFixed(2));

                    row.retention = Number(tax.retention).toFixed(2);
                    total -= Number(tax.retention).toFixed(2);
                }
            });

            val.total = (Number(total) - Number(total_refund)).toFixed(2)

        },

        changeExchangeRate() {
            // this.searchExchangeRateByDate(this.form.date_of_issue).then(response => {
            //     this.form.exchange_rate_sale = response
            // })
        },

        async getTables() {
            await this.$http.get(`/${this.resource}/tables`).then(response => {
                this.all_items = response.data.items;
                this.all_customers = response.data.customers;
                this.currencies = response.data.currencies;
                this.establishment = response.data.establishment;
                this.user = response.data.user;
                this.form.currency_id = this.currencies.length > 0 ? this.currencies[0].id : null;
                // console.log(this.form.currency_id)
                this.taxes = response.data.taxes
                this.renderCategories(response.data.categories)
                // this.currency = _.find(this.currencys, {'id': this.form.currency_id})
                // this.changeCurrencyType();
                this.initCurrencyType()
                this.filterItems();
                this.changeDateOfIssue();
                this.changeExchangeRate()
            });
//            console.log(this.electronic);
        },

        renderCategories(source) {
            const contex = this
            this.categories = source.map((obj, index) => {
                return {
                    id: obj.id,
                    name: obj.name,
                    color: contex.getColor(index)
                }
            })

            this.categories.unshift({
                id: null,
                name: 'Todos',
                color: '#2C8DE3'
            })
        },
        searchItems() {
            if (this.input_item.length > 3) {
                this.loading = true;
                let parameters = `input_item=${this.input_item}`;

                this.$http
                    .get(`/${this.resource}/search_items?${parameters}`)
                    .then(response => {
                        // console.log(response)
                        this.items = response.data.data;

                        this.pagination = response.data.meta;
                        this.pagination.per_page = parseInt(
                            response.data.meta.per_page
                        );

                        this.loading = false;
                        if (this.items.length == 0) {
                            this.filterItems();
                        }
                    });
            } else {
                // this.customers = []
                this.filterItems();
            }

        },
        async searchItemsBarcode() { 

            // console.log(query)
            // console.log("in:" + this.input_item)

            if (this.input_item.length > 1) {

                this.loading = true;
                let parameters = `input_item=${this.input_item}&barcode_only=1`;

                await this.$http.get(`/${this.resource}/search_items?${parameters}`)
                    .then(response => {

                        this.items = response.data.data;

                        this.pagination = response.data.meta;
                        this.pagination.per_page = parseInt(
                            response.data.meta.per_page
                        );

                        this.enabledSearchItemsBarcode()
                        this.loading = false;
                        if (this.items.length == 0) {
                            this.filterItems();
                        }

                    });

            } else {

                await this.filterItems();

            }

        },
        enabledSearchItemsBarcode() {

            if (this.search_item_by_barcode) {
                console.log(this.items)
                if (this.items.length == 1) {

                    // console.log(this.items)
                    this.clickAddItem(this.items[0], 0);
                    this.filterItems();
                    // this.cleanInput();

                }

                this.cleanInput();

            }

        },
        changeSearchItemBarcode() {
            this.cleanInput()
        },
        cleanInput() {
            this.input_item = null;
        },
        filterItems() {
            this.items = this.all_items;
            this.sortItemsByFavorites();
        },
        reloadDataCustomers(customer_id) {
            this.$http.get(`/${this.resource}/table/customers`).then(response => {
                this.all_customers = response.data;
                this.form.customer_id = customer_id;
                this.changeCustomer();
            });
        },
        reloadDataItems(item_id) {
            this.$http.get(`/${this.resource}/table/items`).then(response => {
                this.all_items = response.data;
                this.filterItems();
            });
        },
        selectCurrencyType() {
            // this.form.currency_id = (this.form.currency_id === 'PEN') ? 'USD':'PEN'
            // this.changeCurrencyType()
        },
        async changeCurrencyType() {

            // console.log(this.form.currency_id)
            // this.currency = await _.find(this.currencys, {'id': this.form.currency_id})
            // let items = []
            // this.form.items.forEach((row) => {
            //     items.push(calculateRowItem(row, this.form.currency_id, this.form.exchange_rate_sale))
            // });
            // this.form.items = items
            // this.calculateTotal()

            // await this.setFormPosLocalStorage()

        },
        openFullWindow() {
            location.href = `/${this.resource}/pos_full`
        },
        back() {
            this.place = 'cat'
        },
        setView() {
            this.place = 'cat2'
        },
        nameSets(id) {
            let row = this.items.find(x => x.item_id == id)
            if (row) {

                if (row.sets.length > 0) {
                    return row.sets.join('-')
                } else {
                    return ''
                }

            }
        },
        clearText(texto) {
            return texto.replace(/&nbsp;/g, ' ').replace(/\s{2,}/g, ' ').trim();
        },
        onQuantityInput(item, index) {
            // Solo valida si está activa la validación y no es servicio
            if (
                this.advanced_configuration &&
                this.advanced_configuration.validate_min_stock &&
                item.item.warehouses &&
                item.item.unit_type_id !== 'ZZ'
            ) {
                const warehouse = item.item.warehouses.find(w => w.checked) || item.item.warehouses[0];
                const stock = warehouse ? Number(warehouse.stock) : 0;
                const stock_min = item.item.stock_min !== undefined ? Number(item.item.stock_min) : 0;
                let qty = Number(item.item.aux_quantity);

                if (stock < stock_min) {
                    this.$message.error('El stock actual es menor al stock mínimo para este producto.');
                    item.item.aux_quantity = stock;
                    item.quantity = stock;
                    this.calculateTotal();
                    return;
                }
                if (qty > stock) {
                    this.$message.error('No hay stock suficiente para este producto.');
                    item.item.aux_quantity = stock;
                    item.quantity = stock;
                    this.calculateTotal();
                    return;
                }
                if (qty < 0.001) { // Cambia de 1 a 0.001 para permitir decimales pequeños
                    // Si hay un valor leído válido, no lo sobrescribas por 0
                    if (this.scale.lastWeightValue && Number(this.scale.lastWeightValue) > 0) {
                        item.item.aux_quantity = Number(this.scale.lastWeightValue).toFixed(3);
                        item.quantity = Number(this.scale.lastWeightValue).toFixed(3);
                    } else {
                        item.item.aux_quantity = 1;
                        item.quantity = 1;
                    }
                    this.calculateTotal();
                    return;
                }
                item.quantity = qty;
                this.calculateTotal();
            } else {
                item.quantity = Number(item.item.aux_quantity);
                this.calculateTotal();
            }
        },
        handleResize() {
            this.isMobile = window.innerWidth <= 1800;
            this.windowWidth = window.innerWidth; // <-- actualizar para computada
        },
        getFormatDecimal(value) {
            return Math.round(Number(value));
        },
    }
};
</script>
<style scoped>
.page-header .header-controls-row {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 8px;
    margin-top: 8px;
    width: 100%;
    min-height: 48px;
    max-width: 100%;
    padding-left: 24px; /* <-- Ajusta este valor según lo que necesites */
}

.page-header .header-controls-row > * {
    flex-shrink: 1;
    min-width: 0;
}

.page-header .el-switch {
    min-width: 110px;
    max-width: 160px;
    font-size: 15px;
    flex: 1 1 110px;
}

.page-header .el-switch__label {
    font-size: 14px !important;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.page-header .balanza-btn-group {
    gap: 8px;
    display: flex;
    align-items: center;
    min-width: 0;
    max-width: 180px;
    flex: 1 1 120px;
}
.el-switch-barcode {
    min-width: 190px !important;
    max-width: 260px !important;
}
.page-header .btn-balanza {
    height: 30px;
    padding: 0 10px;    
    overflow: hidden;    
}
.page-header .btn-balanza .balanza-btn-text{
    max-width: 110px;
    font-size: 13px;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
    display: inline-block;
}
.page-header .balanza-tooltip {
    margin-left: 6px;
    font-size: 13px !important;
}

/* Responsive: apila verticalmente los controles y separa del campo de búsqueda */
</style>
