<template >
    <div class="row col-lg-12 m-0 p-0 pos-payment" v-loading="loading_submit">
        <div class="col-lg-4 col-md-6 bg-white m-0 p-0 order-list" style="height: calc(100vh - 60px);">
            <div class="h-60" style="overflow-y: auto">

                <div class="row pl-3 py-2 border-bottom m-0 p-0 bg-white">
                    <div class="col-12 px-0 py-3">
                        <h4 class="font-weight-semibold m-0">{{ customer ? customer.description : '' }}</h4>
                    </div>
                </div>

                <template v-for="(item,index) in form.items">
                    <div class="row py-1 border-bottom m-0 p-0" :key="index">
                        <div class="col-2 p-r-0 m-l-2">
                            <p class="font-weight-semibold m-0 text-center">{{item.quantity}}</p>

                        </div>
                        <div class="col-6 px-0">
                            <p class="font-weight-semibold m-0 text-center m-b-0">{{item.item.name}}</p>
                            <!-- <p class="m-b-0">Descripción del producto</p> -->
                            <!-- <p class="text-muted m-b-0"><small>Descuento 2%</small></p> -->
                        </div>
                        <div class="col-4 p-l-0">
                            <!-- <p class="font-weight-semibold m-b-0">{{currencyTypeActive.symbol}} 240.00</p> -->
                            <h4 class="font-weight-semibold m-0 text-right">{{currencyTypeActive.symbol}} {{
                                item.total | numberFormat }}</h4>
                        </div>
                    </div>
                </template>


            </div>
            <div class="h-40 bg-light" style="overflow-y: auto">
                <div class="row m-0 p-0 h-10 d-flex align-items-center">
                    <div class="col-sm-6 py-1">
                        <p class="font-weight-semibold mb-0">TOTAL VENTA</p>
                    </div>
                    <div class="col-sm-6 py-1 text-right">
                        <p class="font-weight-semibold mb-0">{{currencyTypeActive.symbol}} {{
                            form.sale | numberFormat }}</p>
                    </div>
                </div>
                <div class="row m-0 p-0 h-10 d-flex align-items-center" v-if="discount_amount > 0">
                    <div class="col-sm-6 py-1 ">
                        <p class="font-weight-semibold mb-0">TOTAL DESCUENTO (-)</p>
                    </div>
                    <div class="col-sm-6 py-1 text-right">
                        <p class="font-weight-semibold mb-0">{{currencyTypeActive.symbol}}
                            {{discount_amount | numberFormat}}</p>
                    </div>
                </div>

                <template v-for="(tax, index) in form.taxes">
                    <div class="row m-0 p-0 h-10 d-flex align-items-center"
                        v-if="((tax.total > 0) && (!tax.is_retention))" :key="index">
                        <div class="col-sm-8 py-1">
                            <p class="font-weight-semibold mb-0">{{tax.name}}(+)</p>
                        </div>
                        <div class="col-sm-4 py-1 text-right">
                            <p class="font-weight-semibold mb-0">{{currencyTypeActive.symbol}}
                                {{tax.total | numberFormat}}</p>
                        </div>
                    </div>
                </template>

                <div class="row m-0 p-0 h-10 d-flex align-items-center" v-if="form.subtotal > 0">
                    <div class="col-sm-6 py-1">
                        <p class="font-weight-semibold mb-0">SUBTOTAL</p>
                    </div>
                    <div class="col-sm-6 py-1 text-right">
                        <p class="font-weight-semibold mb-0">{{currencyTypeActive.symbol}}
                            {{form.subtotal | numberFormat}}</p>
                    </div>
                </div>

                <div class="row m-0 p-0 h-10 d-flex align-items-center">
                    <div class="col-sm-6 py-1">
                        <p class="font-weight-semibold mb-0 text-sm">TOTAL</p>
                    </div>
                    <div class="col-sm-6 py-2 text-right">
                        <p class="font-weight-semibold mb-0 text-white">{{currencyTypeActive.symbol}} {{
                            form.total | numberFormat }}</p>
                    </div>
                </div>

                <div class="col-lg-12 py-2">
                    <div class="d-flex flex-column">
                        <div class="col-lg-12 px-0">
                            <button class="btn py-3 btn-block btn-primary" @click="clickPayment"
                                :disabled="button_payment">PAGAR
                                <svg  xmlns="http://www.w3.org/2000/svg"  width="20"  height="20"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-wallet"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12" /><path d="M20 12v4h-4a2 2 0 0 1 0 -4h4" /></svg>
                            </button>
                        </div>
                        <div class="col-lg-12 center">
                            <button class="btn btn-link text-danger" @click="clickCancel">CANCELAR</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8 col-md-6 px-4 pt-3 hyo" style="max-height: 100vh;">
            <div class="row d-flex justify-content-center pt-2">
                <div class="col-md-12 col-lg-10 border-highlight">
                    <div class="card card-default mb-0">
                        <div class="card-body ">
                            <div class="row">
                                <div class="col-md-4 ">
                                    <button class="text-center btn btn-sm btn-block btn-primary"
                                        @click="back"><i class="fas fa-angle-left"></i> Regresar</button>
                                </div>

                                <div class="col-md-8 text-right">
                                    <el-radio-group v-model="form.document_type_id" size="small"
                                        @change="filterSeries(form.document_type_id)">
                                        <template v-for="(resource, index) in resources">
                                            <el-radio-button :label="resource.type">{{ resource.description
                                                }}</el-radio-button>
                                        </template>
                                    </el-radio-group>
                                </div>                                
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group" :class="{'has-danger': errors.date_of_issue}">
                                        <label class="control-label">Fecha de emisión</label>
                                        <el-date-picker v-model="form.date_of_issue" type="date"
                                            value-format="yyyy-MM-dd" :clearable="false"></el-date-picker>
                                        <small class="form-control-feedback" v-if="errors.date_of_issue"
                                            v-text="errors.date_of_issue[0]"></small>
                                    </div>
                                </div>
                                <div class="col-md-4" v-show="['COT', 'RM'].includes(form.document_type_id)">
                                    <div class="form-group" :class="{'has-danger': errors.date_of_due}">
                                        <label class="control-label">Tiempo de Validez</label>
                                        <el-date-picker v-model="form.date_of_due" type="date" value-format="yyyy-MM-dd"
                                            :clearable="false"></el-date-picker>
                                        <small class="form-control-feedback" v-if="errors.date_of_due"
                                            v-text="errors.date_of_due[0]"></small>
                                    </div>
                                </div>
                                <div class="col-lg-4" v-show="['COT', 'RM'].includes(form.document_type_id)">
                                    <div class="form-group" :class="{'has-danger': errors.delivery_date}">
                                        <label class="control-label">Tiempo de Entrega</label>
                                        <el-date-picker v-model="form.delivery_date" type="date"
                                            value-format="yyyy-MM-dd" :clearable="true"></el-date-picker>
                                        <small class="form-control-feedback" v-if="errors.delivery_date"
                                            v-text="errors.delivery_date[0]"></small>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4" v-if="form.document_type_id == '80'">
                                    <el-select v-model="form.series_id" class="c-width">
                                        <el-option v-for="option in series" :key="option.id" :label="option.number"
                                            :value="option.id">
                                        </el-option>
                                    </el-select>
                                </div>
                                <div :class="['90','RM'].includes(form.document_type_id) && advanced_configuration.enable_seller_views ? 'col-lg-6' : 'col-lg-12'" v-show="['COT', 'RM'].includes(form.document_type_id)">
                                    <div class="form-group" :class="{'has-danger': errors.exchange_rate_sale}">
                                        <label class="control-label">Descripción
                                        </label>
                                        <el-input type="textarea" :rows="3" v-model="form.description"></el-input>
                                        <small class="form-control-feedback" v-if="errors.description"
                                            v-text="errors.description[0]"></small>
                                    </div>
                                </div>
                                <div class="col-md-6"
                                    v-if="['90','RM'].includes(form.document_type_id) && advanced_configuration.enable_seller_views">
                                    <div class="form-group" :class="{'has-danger': errors.seller_id}">
                                        <label class="control-label">Vendedor</label>
                                        <el-select v-model="form.seller_id" filterable remote reserve-keyword
                                            placeholder="Seleccionar vendedor" :remote-method="searchRemoteSellers"
                                            :loading="loading_sellers" clearable @focus="fetchSellers">
                                            <el-option v-for="seller in sellers" :key="seller.id"
                                                :label="seller.full_name" :value="seller.id"></el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.seller_id"
                                            v-text="errors.seller_id[0]"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-2" v-if="form.document_type_id == '01'">

                                <div class="col-md-6">
                                    <div class="form-group" :class="{'has-danger': errors.type_document_id}">
                                        <label class="control-label">Tipo de factura</label>
                                        <el-select v-model="form.type_document_id">
                                            <el-option v-for="option in type_invoices" :key="option.id"
                                                :value="option.id" :label="option.name"></el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.type_document_id"
                                            v-text="errors.type_document_id[0]"></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group" :class="{'has-danger': errors.payment_form_id}">
                                        <label class="control-label">Forma de pago</label>
                                        <el-select v-model="form.payment_form_id" filterable>
                                            <el-option v-for="option in payment_forms" :key="option.id"
                                                :value="option.id" :label="option.name"></el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.payment_form_id"
                                            v-text="errors.payment_form_id[0]"></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group" :class="{'has-danger': errors.payment_method_id}">
                                        <label class="control-label">Medio de pago</label>
                                        <el-select v-model="form.payment_method_id" filterable>
                                            <el-option v-for="option in payment_methods" :key="option.id"
                                                :value="option.id" :label="option.name"></el-option>
                                        </el-select>
                                        <small class="form-control-feedback" v-if="errors.payment_method_id"
                                            v-text="errors.payment_method_id[0]"></small>
                                    </div>
                                </div>
                                <div class="col-md-6" v-show="form.payment_form_id == 2">
                                    <div class="form-group" :class="{'has-danger': errors.time_days_credit}">
                                        <label class="control-label">Plazo Crédito</label>
                                        <el-input v-model="form.time_days_credit"></el-input>
                                        <small class="form-control-feedback" v-if="errors.time_days_credit"
                                            v-text="errors.time_days_credit[0]"></small>
                                    </div>
                                </div>

                            </div>

                            <div class="row mt-2" v-show="service_dian_enable">
                                <div class="col-12">
                                    <div class="alert alert-warning mb-0">
                                        El servicio de la DIAN no está disponible en este momento. Por favor, inténtelo
                                        más tarde.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-10 border-highlight">
                    <div class="card card-default mb-0">
                        <div class="card-body text-center">
                            <p class="my-0"><small>Monto a cobrar</small></p>
                            <h1 class="mb-2 mt-0">{{currencyTypeActive.symbol}} {{ form.total | numberFormat }}</h1>
                        </div>
                    </div>
                </div>
                <div class="col-lg-10 border-highlight">
                    <div class="card card-default mb-0">
                        <div class="card-body text-center">
                            <div class="row col-lg-12">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="control-label">Ingrese monto</label>
                                        <el-input :value="formattedEnterAmount" @input="onEnterAmountInput"
                                            class="input-text-right">
                                            <template slot="prepend">{{currencyTypeActive.symbol}}</template>
                                        </el-input>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group" :class="{'has-danger': difference < 0}">
                                        <label class="control-label"
                                            v-text="(difference <0) ? 'Faltante' :'Vuelto'"></label>
                                        <!-- <el-input v-model="difference" :disabled="true">
                                            <template slot="prepend">{{currencyTypeActive.symbol}}</template>
                                        </el-input> -->
                                        <h4 class="control-label font-weight-semibold m-0 text-center m-b-0">
                                            {{currencyTypeActive.symbol}} {{ difference | numberFormat }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card card-default">
                        <div class="card-body text-center">
                            <div class="row col-lg-12">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <h2>
                                            <el-switch v-model="enabled_discount" active-text="Aplicar descuento"
                                                class="control-label font-weight-semibold m-0 text-center m-b-0"
                                                @change="changeEnabledDiscount"></el-switch>
                                        </h2>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group" v-if="enabled_discount">
                                        <h2>
                                            <el-switch v-model="is_percentage" active-text="Por porcentaje"
                                                class="control-label font-weight-semibold m-0 text-center m-b-0"
                                                @change="changeDiscountType"></el-switch>
                                        </h2>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="control-label">
                                            <template v-if="is_percentage">Porcentaje descuento (%)</template>
                                            <template v-else>Monto descuento</template>
                                            <el-tooltip class="item" content="Descuento Global" effect="dark"
                                                placement="top">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <el-input v-if="is_percentage" v-model="discount_percentage"
                                            :disabled="!enabled_discount" @input="inputDiscountPercentage">
                                            <template slot="append">%</template>
                                        </el-input>
                                        <el-input v-else v-model="discount_amount" :disabled="!enabled_discount"
                                            @input="inputDiscountAmount">
                                            <template slot="prepend">{{ currencyTypeActive.symbol }}</template>
                                        </el-input>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-10 border-highlight">
                    <div class="card card-default mb-0">
                        <div class="card-body">
                            <!-- <p class="text-center">Método de Pago</p> -->
                            <div class="input-group mb-3">
                                <div class="col-lg-12 m-bottom">
                                    <div class="row">

                                        <div class="col-lg-6">
                                            <h5><strong>Pagos agregados </strong></h5>
                                        </div>
                                        <div class="col-lg-1">
                                        </div>
                                        <div class="col-lg-5">
                                            <button class="btn btn-sm btn-block btn-primary"
                                                @click="clickAddPayment()"><i class="fas fa-plus"></i> Agregar</button>

                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-12 m-bottom">
                                    <div class="row">
                                        <template v-for="(pay,index) in form.payments">
                                            <div class="col-lg-1" :key="pay.id">
                                                <label>{{index + 1}}.-</label>
                                            </div>
                                            <div class="col-lg-6" :key="pay.id">
                                                <label>{{ getDescriptionPaymentMethod(pay.payment_method_id) }}</label>
                                            </div>
                                            <div class="col-lg-5" :key="pay.id">
                                                <label><strong>{{currencyTypeActive.symbol}} {{
                                                        pay.payment | numberFormat }}</strong> </label>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div class="col-lg-12" v-if="form_payment.payment_method_type_id=='01'">
                                    <div class="row">
                                        <div class="col-lg-3 mb-2">
                                            <button class="btn btn-block btn-secondary"
                                                @click="setAmountCash(2000)">{{currencyTypeActive.symbol}}
                                                {{2000 | numberFormat}}</button>
                                        </div>
                                        <div class="col-lg-3 mb-2">
                                            <button class="btn btn-block btn-secondary"
                                                @click="setAmountCash(5000)">{{currencyTypeActive.symbol}}
                                                {{5000 | numberFormat}}</button>
                                        </div>
                                        <div class="col-lg-3 mb-2">
                                            <button class="btn btn-block btn-secondary"
                                                @click="setAmountCash(10000)">{{currencyTypeActive.symbol}}
                                                {{10000 | numberFormat}}</button>
                                        </div>
                                        <div class="col-lg-3 mb-2">
                                            <button class="btn btn-block btn-secondary"
                                                @click="setAmountCash(20000)">{{currencyTypeActive.symbol}}
                                                {{20000 | numberFormat}}</button>
                                        </div>
                                        <div class="col-lg-3 mb-2">
                                            <button class="btn btn-block btn-secondary"
                                                @click="setAmountCash(50000)">{{currencyTypeActive.symbol}}
                                                {{50000 | numberFormat}}</button>
                                        </div>
                                        <div class="col-lg-3 mb-2">
                                            <button class="btn btn-block btn-secondary"
                                                @click="setAmountCash(100000)">{{currencyTypeActive.symbol}}
                                                {{100000 | numberFormat}}</button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>                
            </div>
        </div>
        <options-form :showDialog.sync="showDialogOptions" :recordId="documentNewId" :statusDocument="statusDocument"
            :resource="resource_options"></options-form>

        <multiple-payment-form :showDialog.sync="showDialogMultiplePayment" :payments="payments"
            :isPos="resource_documents === 'document-pos' ? true : false" @add="addRow"></multiple-payment-form>

        <!--<sale-notes-options :showDialog.sync="showDialogSaleNote"
                          :recordId="saleNotesNewId"
                          :originPos="true"
                          :showClose="true"></sale-notes-options>-->

        <card-brands-form :showDialog.sync="showDialogNewCardBrand" :external="true"
            :recordId="null"></card-brands-form>

        <document-pos-options :showDialog.sync="showDialogSaleNote" :recordId="saleNotesNewId" :originPos="true"
            :showClose="true"></document-pos-options>

        <quotation-options :showDialog.sync="showDialogQuotationOptions" :recordId="saleNotesNewId" :typeUser="'admin'"
            :showGenerate="false" :showClose="true" @triggerBack="back"></quotation-options>

        <remission-options :showDialog.sync="showDialogRemissionOptions" :recordId="saleNotesNewId" :showDownload="true"
            :showClose="true" @triggerBack="back"></remission-options>
        <discount-code-dialog :visible.sync="showDiscountCodeDialog" @validated="onDiscountCodeValidated" />
    </div>
</template>
<style>
.c-width{
    width: 80px!important;
    padding: 0!important;
    margin-right: 0!important;
}
.el-input-group__append, .el-input-group__prepend {
    padding: 0 8px;
}
.switch-wrapper .el-switch{
    position: absolute;
    top: 35px;
    transition: 0.2;
}
.switch-wrapper .el-switch.is-checked{
    position: absolute;
    top: -6px !important;
    left: 50%;
    transform: translateX(-50%);
}
.el-input-group--prepend .el-input__inner {
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
}
@media only screen and (max-width: 991px){
    .descount-container{
        margin-top: 1.8rem !important;
    }
    .switch-wrapper .el-switch{
        top: -12px !important;
    }
}
</style>

<script>

    import CardBrandsForm from '../../card_brands/form.vue'
    import SaleNotesOptions from '../../sale_notes/partials/options.vue'
    import OptionsForm from './options.vue'
    import MultiplePaymentForm from './multiple_payment.vue'
    import DocumentPosOptions from './document_pos_options.vue'
    import QuotationOptions from '../../quotations/partials/options.vue'
    import RemissionOptions from '@viewsModuleSale/co-remissions/partials/options.vue'
    import {functions} from '@mixins/functions'
    import DiscountCodeDialog from '../../../../components/DiscountCodeDialog.vue'

    export default {
        components: {OptionsForm, CardBrandsForm, SaleNotesOptions, MultiplePaymentForm, DocumentPosOptions, QuotationOptions, RemissionOptions, DiscountCodeDialog},
        mixins: [functions],
        props:['form','customer', 'currencyTypeActive', 'exchangeRateSale', 'is_payment', 'soapCompany', 'items_refund'],
        data() {
            return {
                enabled_discount: false,
                discount_amount:0,
                is_percentage: true,
                discount_percentage: 0,
                loading_submit: false,
                showDialogOptions:false,
                showDialogMultiplePayment:false,
                showDialogSaleNote:false,
                showDialogNewCardBrand:false,
                showDialogQuotationOptions: false,
                showDialogRemissionOptions: false,
                documentNewId:null,
                saleNotesNewId:null,
                resource_options:null,
                has_card: false,
                resource: 'pos',
                resource_documents: 'document-pos',
                resource_payments: 'document_payments',
                amount: 0,
                enter_amount: 0,
                difference: 0,
                button_payment: false,
                input_item: '',
                form_payment:{},
                series:[],
                all_series:[],
                cards_brand:[],
                cancel:false,
                form_cash_document:{},
                statusDocument:{},
                payment_method_types:[],
                payments:[],
                type_invoices: [],
                type_documents: [],
                payment_methods: [],
                payment_forms: [],
                errors: {},
                limit_uvt: 0,
                resources: [
                    {
                        type: '90',
                        url: 'document-pos',
                        description: 'POS'
                    },
                    {
                        type: 'COT',
                        url: 'quotations',
                        description: 'Cotización'
                    },
                    {
                        type: 'RM',
                        url: 'co-remissions',
                        description: 'Remisión'
                    }
                ],
                service_dian_enable: false,
                // enable_qz_tray: false,
                advanced_configuration: {},
                showDiscountCodeDialog: false,
                discount_code_validated: false,
                sellers: [],
                loading_sellers: false,
                seller_search_timeout: null,
            }
        },
        async created() {
            await this.getAdvancedConfiguration();
            if (this.advanced_configuration.enable_seller_views) {
                await this.fetchSellers();
            }
            // Reiniciar el descuento antes de cualquier otra operación
            await this.resetDiscount()

            await this.initLStoPayment()
            await this.getTables()
            this.initFormPayment()
            // Forzar actualización de la fecha al crear el componente
            this.form.date_of_issue = moment().format('YYYY-MM-DD')
            this.form.payments = []
            this.$eventHub.$on('reloadDataCardBrands', (card_brand_id) => {
                this.reloadDataCardBrands(card_brand_id)
            })

            this.$eventHub.$on('localSPayments', (payments) => {
                this.payments = payments
            })

            await this.getFormPosLocalStorage()
        },
        mounted(){
        },
        computed: {
            formattedEnterAmount() {
                let val = this.enter_amount;
                if (val === null || val === undefined || val === '') return '';
                val = val.toString().replace(/[^0-9.,]/g, '');
                val = val.replace(/,/g, '');
                let num = parseFloat(val);
                if (isNaN(num)) return val;
                let parts = val.split('.');
                if (parts.length > 1) {
                    return parseInt(parts[0]).toLocaleString('en-US') + '.' + parts[1];
                } else {
                    return parseInt(val).toLocaleString('en-US');
                }
            },
            paymentContainerStyle() {
                return window.innerWidth >= 768 ? 'padding-top: 60px !important;' : '';
            }
        },
        methods: {
            // async getConfigPrint() {
            //     console.info('iniciando qztray');
            //     // resources\js\views\tenant\documents\invoice_generate.vue 4315 autoPrintDocument
            //     await this.$http
            //         .get(`/certificates-qztray/record`)
            //         .then(response => {
            //             this.enable_qz_tray = response.data.enable_qz_tray;
            //         })
            //     if(this.enable_qz_tray) {
            //         startConnection()
            //     }
            // },
            // autoPrintDocument() {
            //     console.log('impresion qztray')
            //     if (this.enable_qz_tray) {
            //         this.$http.get(`/certificates-qztray/html/document/${this.form_cash_document.document_pos_id}`)
            //             .then(response => {
            //                 this.printTicket(response.data)
            //             })
            //             .catch(error => {
            //                 console.log(error)
            //             })
            //     }
            // },
            // printTicket(html_pdf) {
            //     if (html_pdf.length > 0) {
            //         const opts = getUpdatedConfig()
            //         const printData = [
            //             {
            //                 type: 'html',
            //                 format: 'plain',
            //                 data: html_pdf,
            //                 options: opts
            //             }
            //         ]
            //         qz.print(opts, printData)
            //             .then(() => {
            //                 this.$message.success('Impresión en proceso...')

            //             })
            //             .catch(displayError)
            //     }
            // },
            changeEnabledDiscount() {
                if (!this.form.hasOwnProperty('total_without_discount')) {
                    this.form.total_without_discount = parseFloat(this.form.total)
                }
                if (!this.enabled_discount) {
                    this.discount_amount = 0
                    this.discount_percentage = 0
                    this.form.total = parseFloat(this.form.total_without_discount)
                    this.form.allowance_charges = []
                    this.enter_amount = parseFloat(this.form.total)
                    this.enterAmount()
                    this.initFormPayment()
                } else {
                    if (this.discount_amount > 0 || this.discount_percentage > 0) {
                        this.inputDiscountAmount()
                        this.inputDiscountPercentage()
                    }
                }
            },
            changeDiscountType() {
                // Al cambiar el tipo de descuento, reiniciar los valores
                this.discount_amount = 0
                this.discount_percentage = 0
                if (this.form.total_without_discount) {
                    this.form.total = parseFloat(this.form.total_without_discount)
                    this.form.allowance_charges = []
                    this.enter_amount = parseFloat(this.form.total)
                    this.enterAmount()
                    this.initFormPayment()
                }
            },
            async getAdvancedConfiguration() {
                try {
                    const response = await this.$http.get('/co-advanced-configuration/record');
                    this.advanced_configuration = response.data.data;
                } catch (e) {
                    this.advanced_configuration = {};
                }
            },
            async inputDiscountPercentage() {
                if (!this.enabled_discount) {
                    this.discount_percentage = 0
                    return
                }

                if (this.discount_percentage === 0 && this.form.total_without_discount != null) {
                    this.form.total = parseFloat(this.form.total_without_discount)
                }

                if (this.discount_percentage && !isNaN(this.discount_percentage) && parseFloat(this.discount_percentage) > 0) {
                    const percentage = parseFloat(this.discount_percentage)

                    if (percentage > 100) {
                        this.$message.error("El porcentaje de descuento no puede ser mayor a 100%")
                        this.discount_percentage = 0
                        return
                    }

                    // Calcular el monto basado en el porcentaje
                    const total = this.form.total_without_discount || this.form.total
                    const discount = (total * percentage) / 100

                    // Actualizar el monto de descuento para mantener la consistencia
                    this.discount_amount = discount.toFixed(2)

                    this.form.allowance_charges = []
                    this.form.allowance_charges = await this.createAllowanceCharge(
                        discount,
                        this.form.total_without_discount || this.form.total
                    )
                    this.form.total = parseFloat(this.form.total_without_discount - discount)
                    this.enter_amount = parseFloat(this.form.total)
                    this.enterAmount()
                    this.initFormPayment()
                } else {
                    this.form.total = parseFloat(this.form.total_without_discount)
                    this.form.allowance_charges = []
                    this.enter_amount = parseFloat(this.form.total)
                    this.enterAmount()
                    this.initFormPayment()
                }
            },
            async inputDiscountAmount() {
                if (!this.enabled_discount) {
                    this.discount_amount = 0
                    return
                }

                if (this.discount_amount === 0 && this.form.total_without_discount != null) {
                    this.form.total = parseFloat(this.form.total_without_discount)
                }
                if (this.discount_amount && !isNaN(this.discount_amount) && parseFloat(this.discount_amount) > 0) {
                    const discount = parseFloat(this.discount_amount)

                    if (discount >= this.form.total_without_discount) {
                        this.$message.error("El monto de descuento debe ser menor al total de venta")
                        this.discount_amount = 0
                        return
                    }
                    this.form.allowance_charges = []
                    this.form.allowance_charges = await this.createAllowanceCharge(
                        discount,
                        this.form.total_without_discount
                    )
                    this.form.total = parseFloat(this.form.total_without_discount - discount)
                    this.enter_amount = parseFloat(this.form.total)
                    this.enterAmount()
                    this.initFormPayment()
                } else {
                    // Si el descuento no es válido, restaurar al total original
                    this.form.total = parseFloat(this.form.total_without_discount)
                    this.form.allowance_charges = []
                    this.enter_amount = parseFloat(this.form.total)
                    this.enterAmount()
                    this.initFormPayment()
                }
            },
            async fetchSellers() {
                this.loading_sellers = true;
                try {
                    const response = await this.$http.get('/co-sellers/active');
                    this.sellers = response.data.data;
                } catch (e) {
                    this.sellers = [];
                }
                this.loading_sellers = false;
            },
            searchRemoteSellers(query) {
                if (!query) {
                    this.fetchSellers();
                    return;
                }
                if (this.seller_search_timeout) clearTimeout(this.seller_search_timeout);

                if (query.length < 3) {
                    this.sellers = [];
                    return;
                }

                this.loading_sellers = true;
                this.seller_search_timeout = setTimeout(() => {
                    this.$http.get('/co-sellers/active', { params: { search: query } })
                        .then(response => {
                            this.sellers = response.data.data;
                        })
                        .finally(() => {
                            this.loading_sellers = false;
                        });
                }, 400);
            },
            back() {
                // Reset form and totals before going back
                this.resetDiscount()
                this.initFormPayment()
                this.cleanLocalStoragePayment()

                // Force parent component to reload data
                this.$nextTick(() => {
                    this.$emit('update:is_payment', false)
                    this.$emit('reload-data')
                })
            },
            async initLStoPayment(){

                this.amount = await this.getLocalStoragePayment('amount', 0)
                this.enter_amount = await this.getLocalStoragePayment('enter_amount', 0)
                this.difference = await this.getLocalStoragePayment('difference', 0)
            },
            getFormPosLocalStorage(){

                let form_pos = localStorage.getItem('form_pos');
                form_pos = JSON.parse(form_pos)
                if (form_pos) {
                    this.form.payments = form_pos.payments
                }

            },
            clickAddPayment(){
                this.showDialogMultiplePayment = true
            },
            reloadDataCardBrands(card_brand_id) {
                this.$http.get(`/${this.resource}/table/card_brands`).then((response) => {
                    this.cards_brand = response.data
                    this.form_payment.card_brand_id = card_brand_id
                    this.changePaymentMethodType()
                })
            },
            getDescriptionPaymentMethodType(id){
                let payment_method_type = _.find(this.payment_method_types,{'id':id})
                return (payment_method_type) ? payment_method_type.description:''

            },
            getDescriptionPaymentMethod(id) {
                let method = _.find(this.payment_methods, {'id': id})
                return method ? method.name : ''
            },
            changePaymentMethodType(){
                let payment_method_type = _.find(this.payment_method_types,{'id':this.form_payment.payment_method_type_id})
                this.has_card = payment_method_type.has_card
                this.form_payment.card_brand_id = (payment_method_type.has_card) ? this.form_payment.card_brand_id:null
            },
            addRow(payments) {
                payments.forEach(pay => {
                    if (!pay.payment_destination_id) pay.payment_destination_id = 'cash'
                })

                this.form.payments = payments
                let acum_payment = 0

                this.form.payments.forEach((item)=>{
                    acum_payment += parseFloat(item.payment)
                })

               // this.amount = acum_payment
                this.setAmount(acum_payment)

                // console.log(this.form.payments)
            },
            setAmount(amount){
                // this.amount = parseFloat(this.amount) + parseFloat(amount)
                this.amount =  parseFloat(amount) //+ parseFloat(amount)
                this.enter_amount =  parseFloat(amount).toFixed(3) //+ parseFloat(amount)
                this.inputAmount()
            },
            setAmountCash(amount) {
                // Suma el monto al input "Ingrese monto"
                this.enter_amount = parseFloat(this.enter_amount || 0) + parseFloat(amount)
                this.enterAmount()
            },
            async enterAmount() {
                // Solo actualizar el campo payment si hay un solo pago
                if (this.form.payments.length === 1) {
                    let r_item = await _.last(this.payments, { 'payment_method_type_id': '01' });
                    if (r_item) {
                        r_item.payment = parseFloat(this.form.total);
                    }

                    let ind = this.form.payments.length - 1;
                    if (this.form.payments[ind]) {
                        this.form.payments[ind].payment = parseFloat(this.form.total);
                    }
                }

                this.amount = parseFloat(this.enter_amount) || 0;
                this.difference = this.amount - parseFloat(this.form.total);

                if (isNaN(this.difference)) {
                    this.button_payment = true;
                    this.difference = "-";
                } else if (this.difference >= 0) {
                    this.button_payment = false;
                } else {
                    this.button_payment = true;
                }
                this.difference = _.round(this.difference, 2);

                this.$eventHub.$emit('eventSetFormPosLocalStorage', this.form);
                await this.lStoPayment();
            },
            getLocalStoragePayment(key, re_default = null){

                let ls_obj = localStorage.getItem(key);
                ls_obj = JSON.parse(ls_obj)

                if (ls_obj) {
                    return ls_obj
                }

                return re_default
            },
            setLocalStoragePayment(key, obj){
                localStorage.setItem(key, JSON.stringify(obj));
            },
            inputAmount(){

                this.difference = this.amount - this.form.total

                if(isNaN(this.difference)) {
                    this.button_payment = true
                    this.difference = "-"
                }else if(this.difference >=0){
                    this.button_payment = false
                    this.difference = this.amount - this.form.total
                }else{
                    this.button_payment = true
                }
                this.difference = _.round(this.difference,2)
                // this.form_payment.payment = this.amount

                this.$eventHub.$emit('eventSetFormPosLocalStorage', this.form)
                this.lStoPayment()

            },
            lStoPayment(){

                this.setLocalStoragePayment('enter_amount', this.enter_amount)
                this.setLocalStoragePayment('amount', this.amount)
                // console.log(this.amount)
                this.setLocalStoragePayment('difference', this.difference)

            },
            initFormPayment() {
                // Asegurar que la fecha siempre es la actual
                const currentDate = moment().format('YYYY-MM-DD')
                this.difference = -this.form.total
                this.form_payment = {
                    id: null,
                    date_of_payment: currentDate,
                    payment_method_type_id: '01',
                    reference: null,
                    card_brand_id: null,
                    document_id: null,
                    sale_note_id: null,
                    payment: 0
                }

                // Actualizar también la fecha del documento principal
                this.form.date_of_issue = currentDate

                this.form_cash_document = {
                    document_id:null,
                    sale_note_id:null,
                    document_pos_id:null
                }
            },

            filterSeries(document_type_id) {
                let resource = this.resources.find(function (element) { return element.type === document_type_id })
                this.resource_documents = resource.url
                this.form.document_type_id = resource.type
                this.form.prefix = resource.type
                // this.form.document_type_id =
                // this.form.document_type_id = '90'
                /*this.form.series_id = null
                this.series = _.filter(this.all_series, {'document_type_id': this.form.document_type_id });
                this.form.series_id = (this.series.length > 0)?this.series[0].id:null


                if(!this.form.series_id && this.form.document_type_id == '80')
                {
                   return this.$message.warning('El establecimiento no tiene series disponibles para el comprobante');
                }*/
            },
            async clickCancel(){

                this.loading_submit = true
                await this.sleep(800);
                this.loading_submit = false
                this.cleanLocalStoragePayment()
                this.$eventHub.$emit('cancelSale')

            },
            cleanLocalStoragePayment(){

                this.setLocalStoragePayment('amount', null)
                this.setLocalStoragePayment('enter_amount', null)
                this.setLocalStoragePayment('difference', null)
            },
            sleep(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            },
            validateLimitUvt()
            {
                if(this.limit_uvt > 0)
                {
                    if(parseFloat(this.form.sale) >= this.limit_uvt)
                    {
                        return {
                            success: false,
                            message: `El valor del documento (${this.currencyTypeActive.symbol} ${this.form.sale}) ha superado el límite configurado para la UVT (${this.currencyTypeActive.symbol} ${this.limit_uvt}), debe generar factura.`
                        }
                    }
                }

                return {
                    success: true
                }
            },
            async redirectCreateDocument()
            {
                this.loading_submit = true
                await this.sleep(2000)
                this.loading_submit = false
                window.open('/co-documents/create', '_blank')
            },

            async clickPayment(){
                const validate_limit_uvt = this.validateLimitUvt()

                if(!validate_limit_uvt.success){
                    this.$setStorage('form_exceed_uvt', this.form)
                    this.$message.error({
                        showClose: true,
                        message: validate_limit_uvt.message,
                        type: 'error',
                        duration: 6000
                    })
                    this.redirectCreateDocument()
                    return
                }
                if (
                    this.advanced_configuration &&
                    this.advanced_configuration.validate_discount_code &&
                    Number(this.discount_amount) > 0 &&
                    !this.discount_code_validated
                ) {
                    this.showDiscountCodeDialog = true;
                    return;
                }
                //this.form.prefix = "NV";
                this.form.paid = 1;
                // this.resource_documents = resources[this.form.document_type_id];
                this.resource_payments = "document_pos_payments";
                this.resource_options = this.resource_documents;
                // if(this.form.document_type_id === 'COT') {
                //     this.form.prefix = this.form.document_type_id
                // }
                // if(this.form.document_type_id === 'RM') {
                //     this.form.prefix = this.form.document_type_id
                // }
                const items_final = this.form.items.concat(this.items_refund);
                this.form.items = items_final
                this.form.enter_amount = this.enter_amount
                this.loading_submit = true
                this.$http.post(`/${this.resource_documents}`, this.form).then(response => {
                    if (response.data.success == true) {
                        this.form_cash_document.document_pos_id = response.data.data.id;
                        this.saleNotesNewId = response.data.data.id;
                        if(this.saleNotesNewId != null){
                            if (this.form.document_type_id === "90") {
                                this.showDialogSaleNote = true;
                            }
                            if (this.form.document_type_id === "COT") {
                                this.showDialogQuotationOptions = true;
                            }
                            if (this.form.document_type_id === "RM") {
                                this.showDialogRemissionOptions = true;
                            }

                            /*if (this.form.document_type_id === "80") {
                                // this.form_payment.sale_note_id = response.data.data.id;
                                this.form_cash_document.sale_note_id = response.data.data.id;
                                this.saleNotesNewId = response.data.data.id;
                                this.showDialogSaleNote = true;
                            } else {
                                // this.form_payment.document_id = response.data.data.id;
                                this.form_cash_document.document_id = response.data.data.id;
                                this.statusDocument = response.data.data.response
                                this.documentNewId = response.data.data.id;
                                this.showDialogOptions = true;
                            }*/
                            // this.savePaymentMethod();
                            this.saveCashDocument();
                            // this.initFormPayment() ;
                            this.cleanLocalStoragePayment()
                            this.$message.success(response.data.message);
                            this.$eventHub.$emit('saleSuccess');
                        }
                        else {
                            this.$message.error(response.data.message);
                        }
                        this.service_dian_enable = false;
                    }
                    else {
                        this.$message.error(response.data.message);
                        if (response.data.message.includes("El servicio de la DIAN no está disponible en este momento")) {
                            this.service_dian_enable = true;
                            console.log("El servicio de la DIAN no está disponible en este momento. Por favor, inténtelo más tarde.");
                        }
                    }
                }).catch(error => {
                    if (error.response && error.response.status === 422){
                        this.errors = error.response.data;
                    }else if (error.response){
                        this.$message.error(error.response.data.message);
                    }else if(response.data.success == false){
                        this.$message.error("Ha ocurrido un error desconocido.");
                    }
                }).then(() => {
                    this.loading_submit = false;
                });
            },

            saveCashDocument(){
                this.$http.post(`/cash/cash_document`, this.form_cash_document)
                    .then(response => {
                        if (response.data.success) {
                            // console.log(response)
                        } else {
                            this.$message.error(response.data.message);
                        }
                    })
                    .catch(error => {
                        console.log(error);
                    })
            },
            savePaymentMethod(){
                this.$http.post(`/${this.resource_payments}`, this.form_payment)
                    .then(response => {
                        if (response.data.success) {
                            // console.log(response)
                        } else {
                            this.$message.error(response.data.message);
                        }
                    })
                    .catch(error => {
                        if (error.response.status === 422) {
                            this.records[index].errors = error.response.data;
                        } else {
                            console.log(error);
                        }
                    })
            },
            getTables(){
                this.$http.get(`/${this.resource}/payment_tables`)
                    .then(response => {
                        this.all_series = response.data.series
                        this.payment_method_types = response.data.payment_method_types
                        this.cards_brand = response.data.cards_brand
                        this.type_invoices = response.data.type_invoices
                        this.type_documents = response.data.type_documents
                        this.payment_methods = response.data.payment_methods
                        this.payment_forms = response.data.payment_forms
                        this.limit_uvt = response.data.limit_uvt
                        this.filterSeries(this.form.document_type_id)
                    })
            },

            async createInvoiceService() {
                // let resol = this.resolution.resolution; //TODO
                const invoice = {
                    number: 0,
                    type_document_id: 1
                };

                invoice.customer = await this.getCustomer();
                invoice.tax_totals = await this.getTaxTotal();
                invoice.legal_monetary_totals = await this.getLegacyMonetaryTotal();
                invoice.allowance_charges = await this.createAllowanceCharge(invoice.legal_monetary_totals.allowance_total_amount, invoice.legal_monetary_totals.line_extension_amount );

                invoice.invoice_lines = await this.getInvoiceLines();
                invoice.with_holding_tax_total = await this.getWithHolding();

                return invoice;
            },
            getCustomer() {

                let customer = this.customer;

                let obj = {
                    identification_number: customer.number,
                    name: customer.name,
                    phone: customer.telephone,
                    address: customer.address,
                    email: customer.email,
                    merchant_registration: "000000"
                };

                this.form.customer_id = customer.id

                if (customer.type_person_id == 2) {
                    obj.dv = customer.dv;
                }

                return obj;
            },

            getTaxTotal() {

                let tax = [];
                this.form.items.forEach(element => {
                    let find = tax.find(x => x.tax_id == element.tax.type_tax_id && x.percent == element.tax.rate);
                    if(find)
                    {
                        let indexobj = tax.findIndex(x => x.tax_id == element.tax.type_tax_id && x.percent == element.tax.rate);
                        tax.splice(indexobj, 1);
                        tax.push({
                            tax_id: find.tax_id,
                            tax_amount: this.cadenaDecimales(Number(find.tax_amount) + Number(element.total_tax)),
                            percent: this.cadenaDecimales(find.percent),
                            taxable_amount: this.cadenaDecimales(Number(find.taxable_amount) + Number(element.unit_price) * Number(element.quantity)) - Number(element.discount)
                        });
                    }
                    else {
                        tax.push({
                            tax_id: element.tax.type_tax_id,
                            tax_amount: this.cadenaDecimales(Number(element.total_tax)),
                            percent: this.cadenaDecimales(Number(element.tax.rate)),
                            taxable_amount: this.cadenaDecimales((Number(element.unit_price) * Number(element.quantity)) - Number(element.discount))
                        });
                    }
                });
            //      console.log(tax);
                this.tax_amount_calculate = tax;
                return tax;
            },

            getLegacyMonetaryTotal() {

                let line_ext_am = 0;
                let tax_incl_am = 0;
                let allowance_total_amount = 0;
                this.form.items.forEach(element => {
                    line_ext_am += (Number(element.unit_price) * Number(element.quantity)) - Number(element.discount);
                    allowance_total_amount += Number(element.discount);
                });

                let total_tax_amount = 0;
                this.tax_amount_calculate.forEach(element => {
                    total_tax_amount += Number(element.tax_amount);
                });

                tax_incl_am = line_ext_am + total_tax_amount;

                return {
                    line_extension_amount: this.cadenaDecimales(line_ext_am),
                    tax_exclusive_amount: this.cadenaDecimales(line_ext_am),
                    tax_inclusive_amount: this.cadenaDecimales(tax_incl_am),
                    allowance_total_amount: this.cadenaDecimales(allowance_total_amount),
                    charge_total_amount: "0.00",
                    payable_amount: this.cadenaDecimales(tax_incl_am - allowance_total_amount)
                };

            },

            getInvoiceLines() {

                let data = this.form.items.map(x => {
                    return {

                        unit_measure_id: x.item.unit_type.code, //codigo api dian de unidad
                        invoiced_quantity: x.quantity,
                        line_extension_amount: this.cadenaDecimales((Number(x.unit_price) * Number(x.quantity)) - x.discount),
                        free_of_charge_indicator: false,
                                allowance_charges: [
                            {
                                        charge_indicator: false,
                                        allowance_charge_reason: "DESCUENTO GENERAL",
                                        amount: this.cadenaDecimales(x.discount),
                                        base_amount: this.cadenaDecimales(Number(x.unit_price) * Number(x.quantity))
                                    }
                        ],
                        tax_totals: [
                            {
                                tax_id: x.tax.type_tax_id,
                                tax_amount: this.cadenaDecimales(x.total_tax),
                                taxable_amount: this.cadenaDecimales((Number(x.unit_price) * Number(x.quantity)) - x.discount),
                                percent: this.cadenaDecimales(x.tax.rate)
                            }
                        ],
                        description: x.item.name,
                        code: x.item.internal_id,
                        type_item_identification_id: 4,
                        price_amount: this.cadenaDecimales(x.unit_price),
                        base_quantity: x.quantity
                    };

                });

                return data;
            },

            getWithHolding() {

                let total = this.form.sale
                let list = this.form.taxes.filter(function(x) {
                    return x.is_retention && x.apply;
                });

                return list.map(x => {
                    return {
                        tax_id: x.type_tax_id,
                        tax_amount: this.cadenaDecimales(x.retention),
                        percent: this.cadenaDecimales(x.rate),
                        taxable_amount: this.cadenaDecimales(total),
                    };
                });

            },

            createAllowanceCharge(amount, base) {
                return [
                    {
                        discount_id: 1,
                        charge_indicator: false,
                        allowance_charge_reason: "DESCUENTO GENERAL",
                        amount: this.cadenaDecimales(amount),
                        base_amount: this.cadenaDecimales(base)
                    }
                ]
            },

            cadenaDecimales(amount){
                if(amount.toString().indexOf(".") != -1)
                    return amount.toString();
                else
                    return amount.toString()+".00";
                },
            async resetDiscount() {
                // Reiniciar flags y montos de descuento
                this.enabled_discount = false
                this.discount_amount = 0
                this.is_percentage = true
                this.discount_percentage = 0

                // Restaurar total original si existe descuento previo
                if (this.form.hasOwnProperty('total_without_discount')) {
                    this.form.total = parseFloat(this.form.total_without_discount)
                    delete this.form.total_without_discount
                }

                // Limpiar cargos y descuentos
                this.form.allowance_charges = []

                // Inicializar todo en 0
                this.enter_amount = 0
                this.amount = 0
                this.difference = -this.form.total

                // Reiniciar pagos
                this.form.payments = []
                this.payments = []

                // Pago por defecto en 0
                const defaultPayment = {
                    payment_method_type_id: null,
                    payment_method_id: 10,
                    payment: 0,
                    date_of_payment: moment().format('YYYY-MM-DD'),
                    payment_destination_id: 'cash'
                }
                this.form.payments.push(defaultPayment)
                this.payments.push(defaultPayment)

                // Actualizar button_payment según la diferencia
                this.button_payment = true

                // Recalcular totales
                await this.enterAmount()

                // Actualizar localStorage
                this.$eventHub.$emit('eventSetFormPosLocalStorage', this.form)
                await this.lStoPayment()

                // Reiniciar formulario de pago
                this.initFormPayment()
            },
            onDiscountCodeValidated(success) {
                if (success) {
                    this.discount_code_validated = true;
                    this.showDiscountCodeDialog = false;
                    this.clickPayment();
                }
            },
            onEnterAmountInput(value) {
                let clean = value.replace(/,/g, '');
                clean = clean.replace(/[^0-9.]/g, '');
                this.enter_amount = clean;
                this.enterAmount();
            },
        },
        watch: {
            discount_amount(nuevo, anterior) {
                if (this.discount_code_validated && Number(nuevo) !== Number(anterior)) {
                    this.discount_code_validated = false;
                }
            }
        },
    }
</script>
