<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @open="create" @close="close">
        <form autocomplete="off" @submit.prevent="clickAddItem">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.item_id}">
                            <label class="control-label">
                                Producto/Servicio
                                <a href="#" @click.prevent="showDialogNewItem = true">[+ Nuevo]</a>
                            </label>
                            <el-select 
                                v-model="form.item_id" 
                                @change="changeItem"
                                filterable
                                remote
                                reserve-keyword
                                :remote-method="remoteSearchItems"
                                :loading="loading_search"
                                placeholder="Buscar producto o servicio">
                                <el-option 
                                    v-for="option in items" 
                                    :key="option.id" 
                                    :value="option.id" 
                                    :label="option.full_description">
                                </el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.item_id" v-text="errors.item_id[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.tax_id}">
                            <label class="control-label">Impuesto</label>
                            <el-select v-model="form.tax_id"  filterable>
                                <el-option v-for="option in itemTaxes" :key="option.id" :value="option.id" :label="option.name"></el-option>
                            </el-select>
                            <!-- <el-checkbox :disabled="recordItem != null" v-model="change_tax_id">Editar</el-checkbox> -->
                            <small class="form-control-feedback" v-if="errors.tax_id" v-text="errors.tax_id[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group" :class="{'has-danger': errors.quantity}">
                            <label class="control-label">Cantidad</label>
                            <el-input-number v-model="form.quantity" :min="0.01"></el-input-number>
                            <small class="form-control-feedback" v-if="errors.quantity" v-text="errors.quantity[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group" :class="{'has-danger': errors.unit_price}">
                            <label class="control-label">Precio Unitario</label>
                            <el-input v-model="form.unit_price">
                                <template slot="prepend" v-if="form.item.currency_type_symbol">{{ form.item.currency_type_symbol }}</template>
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.unit_price" v-text="errors.unit_price[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" :class="{'has-danger': errors.warehouse_id}">
                            <label class="control-label">Almacén de destino</label>
                            <el-select v-model="form.warehouse_id"   filterable  >
                                <el-option v-for="option in warehouses" :key="option.id" :value="option.id" :label="option.description"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.warehouse_id" v-text="errors.warehouse_id[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-6 mt-2" v-if="form.item_id">
                        <div class="form-group" :class="{'has-danger': errors.lot_code}" v-if="form.item.lots_enabled">
                            <label class="control-label">
                                Código lote
                            </label>
                            <el-input v-model="lot_code" >
                                <!--<el-button slot="append" icon="el-icon-edit-outline"  @click.prevent="clickLotcode"></el-button> -->
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.lot_code" v-text="errors.lot_code[0]"></small>
                        </div>
                    </div>
                    <div style="padding-top: 1%;" class="col-md-3" v-show="form.item_id">
                        <div class="form-group" :class="{'has-danger': errors.date_of_due}" v-if="form.item.lots_enabled">
                            <label class="control-label">Fec. Vencimiento</label>
                            <el-date-picker v-model="form.date_of_due" type="date" value-format="yyyy-MM-dd" :clearable="true"></el-date-picker>
                            <small class="form-control-feedback" v-if="errors.date_of_due" v-text="errors.date_of_due[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-3" v-show="form.item_id">  <br>
                        <div class="form-group" :class="{'has-danger': errors.lot_code}" v-if="form.item.series_enabled">
                            <label class="control-label">
                                <!-- <el-checkbox v-model="enabled_lots"  @change="changeEnabledPercentageOfProfit">Código lote</el-checkbox> -->
                                Ingrese series
                            </label>

                            <el-button style="margin-top:2%;" type="primary" icon="el-icon-edit-outline"  @click.prevent="clickLotcode"></el-button>

                            <small class="form-control-feedback" v-if="errors.lot_code" v-text="errors.lot_code[0]"></small>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="form-group" :class="{'has-danger': errors.discount}">
                            <label class="control-label">Descuento</label>
                            <el-input v-model="form.discount"
                                min="0"
                                class="input-with-select"
                                :disabled="!form.item_id">
                                <el-select v-model="form.discount_type"
                                    slot="prepend"
                                    :disabled="!form.item_id">
                                    <el-option label="%" value="percentage"></el-option>
                                    <el-option :label="form.item.currency_type_symbol" value="amount"></el-option>
                                </el-select>
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.discount" v-text="errors.discount[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label class="control-label" for="sale_unit_price">
                                Cambiar precio de venta <el-checkbox v-model="applyWeightedPrice" @change="onChangeApplyWeighted"></el-checkbox>
                            </label>
                            <el-input 
                                v-model="form.sale_unit_price"
                                :placeholder="applyWeightedPrice ? 'Precio ponderado calculado' : 'Ingrese precio de venta'">
                                <template slot="prepend" v-if="form.item.currency_type_symbol">
                                    {{ form.item.currency_type_symbol }}
                                </template>
                            </el-input>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group" :class="{'has-danger': errors.chart_of_account_code}">
                            <label class="control-label">
                                Clasificación contable
                                <el-tooltip placement="top">
                                    <i class="el-icon-info" style="color:#409EFF;cursor:pointer;"></i>
                                    <div slot="content">
                                        Este campo es <b>opcional</b> y se recomienda solo para servicios u otros productos <b>no inventariables</b>.<br>
                                        Si es un producto inventariable, se usará la cuenta fija <b>143505</b>.
                                    </div>
                                </el-tooltip>
                            </label>
                            <el-select
                                v-model="form.chart_of_account_code"
                                filterable
                                remote
                                reserve-keyword
                                :remote-method="remoteSearchChartAccounts"
                                :loading="loading_chart_accounts"
                                placeholder="Buscar cuenta contable">
                                <el-option
                                    v-for="option in chartOfAccounts"
                                    :key="option.code"
                                    :value="option.code"
                                    :label="`${option.code} - ${option.name}`">
                                </el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.chart_of_account_code" v-text="errors.chart_of_account_code[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="control-label">Notas del ítem</label>
                            <el-input
                                type="textarea"
                                v-model="form.notes"
                                :rows="2"
                                maxlength="250"
                                show-word-limit
                                placeholder="Notas para este ítem">
                            </el-input>
                        </div>
                    </div>
                    <div class="col-md-12"  v-if="form.item_unit_types.length > 0">
                        <div style="margin:3px" class="table-responsive">
                            <h5 class="separator-title">
                                Listado de Precios
                                <el-tooltip class="item" effect="dark" content="Aplica para realizar compra/venta en presentacion de diferentes precios y/o cantidades" placement="top">
                                    <i class="fa fa-info-circle"></i>
                                </el-tooltip>
                            </h5>
                            <table class="table">
                            <thead>
                            <tr>
                                <th class="text-center">Unidad</th>
                                <th class="text-center">Descripción</th>
                                <th class="text-center">Factor</th>

                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(row, index) in form.item_unit_types" :key="index">

                                    <td class="text-center">{{row.unit_type.name}}</td>
                                    <td class="text-center">{{row.description}}</td>
                                    <td class="text-center">{{row.quantity_unit}}</td>

                                    <td class="series-table-actions text-right">
                                       <button type="button" class="btn waves-effect waves-light btn-xs btn-success" @click.prevent="selectedPrice(row)">
                                            <i class="el-icon-check"></i>
                                        </button>
                                    </td>


                            </tr>
                            </tbody>
                        </table>

                        </div>

                    </div>
                </div>
            </div>
            <div class="form-actions text-right pt-2">
                <el-button @click.prevent="close()">Cerrar</el-button>
                <el-button type="primary" native-type="submit" :disabled="!form.item_id">{{titleAction}}</el-button>
            </div>
        </form>
        <item-form :showDialog.sync="showDialogNewItem"
                   :external="true"></item-form>

        <lots-form
            :showDialog.sync="showDialogLots"
            :stock="form.quantity"
            :lots="lots"
            @addRowLot="addRowLot">
        </lots-form>

    </el-dialog>
</template>
<style>
.el-select-dropdown {
    max-width: 80% !important;
    margin-right: 5% !important;
}
.input-with-select .el-select .el-input {
    width: 50px;
}
.input-with-select .el-select .el-input .el-input__inner {
    padding-right: 10px;
}
</style>
<script>

    import itemForm from '../../items/form.vue'
    import {calculateRowItem} from '../../../../helpers/functions'
    import LotsForm from '../../items/partials/lots.vue'

    export default {
        props: ['showDialog', 'currencyTypeIdActive', 'exchangeRateSale', 'recordItem'],
        components: {itemForm, LotsForm},
        data() {
            return {
                titleDialog: 'Agregar Producto o Servicio',
                showDialogLots:false,
                chartOfAccounts: [],
                loading_chart_accounts: false,
                resource: 'purchases',
                showDialogNewItem: false,
                errors: {},
                form: {},
                items: [],
                warehouses: [],
                lots: [],
                affectation_igv_types: [],
                system_isc_types: [],
                discount_types: [],
                charge_types: [],
                attribute_types: [],
                use_price: 1,
                lot_code: null,
                change_affectation_igv_type_id: false,
                all_taxes:[],
                taxes:[],
                titleAction: '',
                showWeightedCalculation: false,
                applyWeightedPrice: false,
                loading_search: false,
            }
        },
        computed: {
            itemTaxes() {
                return this.taxes.filter(tax => !tax.is_retention);
            },
        },
        created() {
            this.initForm()
            this.$http.get(`/${this.resource}/item/tables`).then(response => {

                this.items = response.data.items
                this.warehouses = response.data.warehouses
                this.taxes = response.data.taxes;
                // this.filterItems()
            })

            this.$eventHub.$on('reloadDataItems', (item_id) => {
                this.reloadDataItems(item_id)
            })
            this.remoteSearchChartAccounts('');
        },
        methods: {
            addRowLot(lots){
                this.lots = lots
            },
            async remoteSearchChartAccounts(query) {
                this.loading_chart_accounts = true;
                let params = {
                    column: 'code',
                    value: query,
                    per_page: 50,
                    page: 1
                };
                await this.$http.get('/accounting/charts/records-by-groups', { params })
                    .then(response => {
                        this.chartOfAccounts = response.data.data;
                    })
                    .finally(() => {
                        this.loading_chart_accounts = false;
                    });
            },
            clickLotcode(){
                // if(this.form.stock <= 0)
                //     return this.$message.error('El stock debe ser mayor a 0')

                this.showDialogLots = true
            },
            filterItems(){
                this.items = this.items.filter(item => item.warehouses.length >0)
            },
            initForm() {
                this.errors = {}
                this.form = {
                    item_id: null,
                    warehouse_id: 1,
                    warehouse_description: null,
                    item: {},
                    quantity: 1,
                    unit_price: 0,
                    item_unit_types: [],
                    lot_code:null,
                    date_of_due: null,
                    subtotal: null,
                    tax: {},
                    tax_id: null,
                    total: 0,
                    total_tax: 0,
                    type_unit: {},
                    discount: 0,
                    unit_type_id: null,
                    lots: [],
                    discount_type: 'percentage',
                    discount_percentage: 0,
                    sale_unit_price: 0,
                    notes: '',
                }

                this.item_unit_type = {};
                this.lots = []
                this.lot_code = null
            },
            async create() {
                this.titleDialog = (this.recordItem) ? ' Editar Producto o Servicio' : ' Agregar Producto o Servicio';
                this.titleAction = (this.recordItem) ? ' Editar' : ' Agregar';

                if (this.recordItem) {
                    // console.log(this.recordItem)
                    this.form.item_id = await this.recordItem.item_id
                    await this.changeItem()
                    this.form.quantity = this.recordItem.quantity
                    this.form.unit_price = this.recordItem.unit_price
                    this.form.discount_type = this.recordItem.discount_type
                    this.form.notes = this.recordItem.notes || ''

                    if(this.form.discount_type == 'percentage') {
                        this.form.discount = this.recordItem.discount_percentage
                    } else {
                        this.form.discount = this.recordItem.discount
                    }
                }
            },
            close() {
                this.initForm()
                this.$emit('update:showDialog', false)
            },
            selectedPrice(row)
            {

                let valor = 0
                switch(row.price_default)
                {
                    case 1:
                        valor = row.price1
                        break
                    case 2:
                         valor = row.price2
                        break
                    case 3:
                         valor = row.price3
                        break

                }

                this.form.item_unit_type_id = row.id
                this.item_unit_type = row

                this.form.unit_price = valor
                this.form.item.unit_type_id = row.unit_type_id
            },
            changeItem() {
                this.form.item = _.find(this.items, {'id': this.form.item_id})
                this.form.unit_price = this.form.item.purchase_unit_price
                // this.form.affectation_igv_type_id = this.form.item.purchase_affectation_igv_type_id
                this.form.item_unit_types = _.find(this.items, {'id': this.form.item_id}).item_unit_types

                this.form.unit_type_id = this.form.item.unit_type_id
                this.form.tax_id = (this.taxes.length > 0) ? this.form.item.purchase_tax_id: null
                
                // Establecer el precio de venta inicial al cambiar item
                this.form.sale_unit_price = this.form.item.sale_unit_price
                
                // Cargar cuenta contable de inventario si existe
                this.form.chart_of_account_code = this.form.item.chart_of_account_code || null;
                // Si applyWeightedPrice está activo, calcular el precio ponderado
                // después de establecer los valores iniciales
                if(this.applyWeightedPrice && this.form.item.stock) {
                    this.$nextTick(() => {
                        this.calculateWeightedPrice()
                    })
                }
            },

            onChangeApplyWeighted(value) {
                if(value) {
                    // Si se activa el checkbox, calcular precio ponderado
                    this.calculateWeightedPrice()
                }
            },

            async clickAddItem() {

                if(this.form.item.lots_enabled){

                    if(!this.lot_code)
                        return this.$message.error('Código de lote es requerido');

                    if(!this.form.date_of_due)
                        return this.$message.error('Fecha de vencimiento es requerido si lotes esta habilitado.');

                }

                if(this.form.item.series_enabled)
                {

                    if(this.lots.length > this.form.quantity)
                        return this.$message.error('La cantidad de series registradas es superior al stock');

                    if(this.lots.length != this.form.quantity)
                        return this.$message.error('La cantidad de series registradas son diferentes al stock');
                }

                let date_of_due = this.form.date_of_due

                this.form.tax = _.find(this.taxes, {'id': this.form.tax_id})
                this.form.type_unit = this.form.item.type_unit

                this.form.item.unit_price = this.form.unit_price
                this.form.item.presentation = this.item_unit_type;

                // Solo incluir sale_unit_price si applyWeightedPrice está activo
                if (this.applyWeightedPrice) {
                    this.form.sale_unit_price = this.form.sale_unit_price;
                } else {
                    delete this.form.sale_unit_price;
                }

                this.form.lot_code = await this.lot_code
                this.form.lots = await this.lots

                this.form = this.changeWarehouse(this.form)

                this.form.date_of_due = date_of_due
                // console.log(this.form)

                if (this.recordItem)
                {
                    this.form.indexi = this.recordItem.indexi
                }

                if(this.form.discount_type == 'percentage') {
                    this.form.discount_percentage = this.form.discount
                }
                this.form.chart_of_account_code = this.form.chart_of_account_code || null;

                // this.initializeFields()
                this.$emit('add', this.form)
                this.initForm()
            },
            changeWarehouse(form){
                let warehouse = _.find(this.warehouses,{'id':this.form.warehouse_id})
                form.warehouse_id = warehouse.id
                form.warehouse_description = warehouse.description
                return form
            },
            reloadDataItems(item_id) {
                // Modificar para que incluya el nuevo item en la búsqueda
                this.loading_search = true
                this.$http.get(`/purchases/search-items?new_item_id=${item_id}`)
                    .then((response) => {
                        this.items = response.data
                        this.form.item_id = item_id
                        this.changeItem()
                        this.loading_search = false
                    })
                    .catch(error => {
                        console.log(error)
                        this.loading_search = false
                    })
            },
            calculateWeightedPrice() {
                if (!this.applyWeightedPrice || !this.form.item.stock || !this.form.quantity) {
                    return;
                }

                const currentStock = parseFloat(this.form.item.stock);
                const currentPrice = parseFloat(this.form.item.sale_unit_price);
                const newQuantity = parseFloat(this.form.quantity);
                const newPrice = parseFloat(this.form.unit_price);

                const weightedPrice = ((currentStock * currentPrice) + (newQuantity * newPrice)) / (currentStock + newQuantity);
                this.form.sale_unit_price = Number(weightedPrice.toFixed(2));
            },
            async remoteSearchItems(query) {
                if (query.length > 2) {
                    this.loading_search = true
                    await this.$http.get(`/purchases/search-items?search=${query}`)
                        .then(response => {
                            this.items = response.data
                            this.loading_search = false
                        })
                        .catch(error => {
                            console.log(error)
                            this.loading_search = false
                        })
                }
            },
        },
        watch: {
            'form.quantity': function(newVal, oldVal) {
                if(this.form.item.stock && newVal > 0) {
                    this.calculateWeightedPrice()
                }
            },
            'form.unit_price': function(newVal, oldVal) {
                if(this.form.item.stock && this.form.quantity > 0) {
                    this.calculateWeightedPrice()
                }
            }
        }
    }

</script>
