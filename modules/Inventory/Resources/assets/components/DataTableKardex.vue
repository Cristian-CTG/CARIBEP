<template>
    <div>
        <div class="row">

            <div class="col-md-12 col-lg-12 col-xl-12 ">
                  
                <div class="row mt-2">  
                        <div class="col-12 col-md-6 col-lg-6">
                            <label class="control-label">Almacén</label>
                            <el-select
                                v-model="form.warehouse_id"
                                @change="onWarehouseChange"
                            >
                                <el-option
                                    v-for="warehouse in warehouses"
                                    :key="warehouse.id"
                                    :value="warehouse.id"
                                    :label="warehouse.name"
                                ></el-option>
                            </el-select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-6">
                            <label class="control-label">Producto</label>
                            <el-select
                                v-model="form.item_id"
                                filterable
                                clearable
                                remote
                                :remote-method="searchItems"
                                :loading="loading_items"
                            >
                                <el-option
                                    v-for="option in items"
                                    :key="option.id"
                                    :value="option.id"
                                    :label="option.full_description"
                                ></el-option>
                            </el-select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3" v-if="!hideMovementFilters" >
                            <label class="control-label">Tipo de movimiento</label>
                            <el-select v-model="form.movement_type" clearable>
                                <el-option label="Todos" :value="null"></el-option>
                                <el-option label="Venta" value="App\Models\Tenant\Document"></el-option>
                                <el-option label="Compra" value="App\Models\Tenant\Purchase"></el-option>
                                <el-option label="Nota de venta" value="App\Models\Tenant\SaleNote"></el-option>
                                <el-option label="Movimiento de inventario" value="Modules\Inventory\Models\Inventory"></el-option>
                                <el-option label="Pedido" value="Modules\Order\Models\OrderNote"></el-option>
                                <el-option label="Venta POS" value="App\Models\Tenant\DocumentPos"></el-option>
                                <el-option label="Remisión" value="Modules\Sale\Models\Remission"></el-option>
                                <!-- Más opciones según tu lógica -->
                            </el-select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3" v-if="showStatusFilter">
                            <label class="control-label">Estado</label>
                            <el-select v-model="form.status" clearable placeholder="Todos">
                                <el-option label="Todos" :value="null"></el-option>
                                <el-option label="Disponible" value="disponible"></el-option>
                                <el-option label="Vendido" value="vendido"></el-option>
                                <el-option label="No disponible" value="no_disponible"></el-option>
                            </el-select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="control-label">Fecha inicio</label>
                            <el-date-picker v-model="form.date_start" type="date"
                                            @change="changeDisabledDates"
                                            value-format="yyyy-MM-dd" format="dd/MM/yyyy" :clearable="true"></el-date-picker>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="control-label">Fecha término</label>
                            <el-date-picker v-model="form.date_end" type="date"
                                            :picker-options="pickerOptionsDates"
                                            value-format="yyyy-MM-dd" format="dd/MM/yyyy" :clearable="true"></el-date-picker>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3 d-flex align-items-center" v-if="!hideMovementFilters">
                            <div class="w-100">
                                <el-checkbox v-model="form.today" class="align-checkbox checkbox-responsive-text">
                                    Solo movimientos de hoy
                                </el-checkbox>
                            </div>
                        </div>

                        <div class="col-12 col-md-12 col-lg-12" style="margin-top:29px">
                            <div class="d-inline-block mb-2">
                                <el-button class="submit" type="primary" @click.prevent="getRecordsByFilter" :loading="loading_submit" icon="el-icon-search">
                                    Buscar
                                </el-button>
                            </div>
                            <template v-if="records.length>0">
                                <div class="d-inline-block mb-2 ml-2">
                                    <el-button class="submit" type="danger" icon="el-icon-tickets" @click.prevent="clickDownload('pdf')">
                                        Exportar PDF
                                    </el-button>
                                </div>
                                <div class="d-inline-block mb-2 ml-2">
                                    <el-button class="submit" type="success" @click.prevent="clickDownload('excel')">
                                        <i class="fa fa-file-excel"></i> Exportar Excel
                                    </el-button>
                                </div>
                            </template>
                        </div>
                    
                </div>
                <div class="row mt-1 mb-4">
                    
                </div> 
            </div>


            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <slot name="heading"></slot>
                        </thead>
                        <tbody>
                            <slot v-for="(row, index) in records" :row="row" :index="customIndex(index)"></slot>
                        </tbody> 
                    </table>
                    <div>
                        <el-pagination
                                @current-change="getRecords"
                                layout="total, prev, pager, next"
                                :total="pagination.total"
                                :current-page.sync="pagination.current_page"
                                :page-size="pagination.per_page">
                        </el-pagination>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
<style>
.font-custom{
    font-size:15px !important
}
.input-full {
    width: 100%;
}
.align-checkbox {
    margin-top: 12px;
}
@media (max-width: 767.98px) {
    .el-select, .el-date-picker, .el-checkbox {
        width: 100% !important;
    }
}
.checkbox-responsive-text {
    white-space: nowrap;
}
@media (max-width: 991.98px) {
    .checkbox-responsive-text {
        font-size: 13px;
        white-space: normal;
        text-align: center;
        display: block;
    }
}
</style>
<script>

    import moment from 'moment'
    import queryString from 'query-string'

    export default { 
        props: {
            resource: String,
            hideMovementFilters: {
                type: Boolean,
                default: false
            },
            showStatusFilter: {
                type: Boolean,
                default: false
            },
            requireProduct: { // NUEVO
                type: Boolean,
                default: false
            }
        },
        data () {
            return {
                warehouses: [],
                loading_submit:false,
                columns: [],
                records: [],
                headers: headers_token,
                document_types: [],
                pagination: {}, 
                search: {}, 
                totals: {}, 
                establishment: null,
                items: [],
                loading_items: false, // nuevo estado para loading
                filterTodayActive: false,
                form: {}, 
                pickerOptionsDates: {
                    disabledDate: (time) => {
                        time = moment(time).format('YYYY-MM-DD')
                        return this.form.date_start > time
                    }
                },
            }
        },
        computed: {
        },
        created() {
            this.initForm()
            this.$eventHub.$on('reloadData', () => {
                this.getRecords()
            })
        },
        async mounted () { 

            await this.$http.get(`/${this.resource}/filter`)
                .then(response => {
                    this.items = response.data.items;
                });
            await this.$http.get(`/warehouses/all`).then((response) => {
                this.warehouses = response.data.warehouses; // Lista de todos los almacenes
                const currentWarehouse = response.data.current_warehouse; // Almacén actual del usuario
                this.form.warehouse_id = currentWarehouse ? currentWarehouse.id : this.warehouses[0]?.id; // Selecciona el almacén actual o el primero disponible
                this.onWarehouseChange(); // Carga los productos del almacén seleccionado
            });


            // await this.getRecords()

        },
        methods: {
            async filterToday() {
                this.loading_submit = true;
                this.filterTodayActive = true; // NUEVO
                // Limpiar filtros
                this.form.item_id = null;
                this.form.date_start = null;
                this.form.date_end = null;
                this.form.today = true; // NUEVO
                this.$eventHub.$emit('emitItemID', null); 
                const response = await this.$http.get(`/reports/kardex_today`, {
                    params: { warehouse_id: this.form.warehouse_id },
                });
                this.records = response.data.data; // Actualiza los registros con los movimientos de hoy
                this.pagination = response.data.meta; // Actualiza la paginación
                this.pagination.per_page = Number(this.pagination.per_page);
                this.loading_submit = false;
            },
            async onWarehouseChange() {
                // Actualizar la lista de productos al cambiar el almacén
                this.form.item_id = null;
                const response = await this.$http.get(`/reports/kardex/filter?warehouse_id=${this.form.warehouse_id}`);
                this.items = response.data.items;
            },  
            changeDisabledDates() {
                if (this.form.date_end < this.form.date_start) {
                    this.form.date_end = this.form.date_start
                }
                // this.loadAll();
            },
            clickDownload(type) {        
                if(this.requireProduct && !this.form.item_id){
                    this.$message.error('Debe seleccionar un producto para exportar.');
                    return;
                }         
                let query = queryString.stringify({
                    ...this.form,
                });
                window.open(`/${this.resource}/${type}/?${query}`, '_blank');
            },
            initForm(){
 
                this.form = {
                    item_id:null,
                    date_start:null,
                    date_end:null,
                    movement_type: null,
                    warehouse_id: null,
                    today: false,
                    status: null,
                }

            },  
            customIndex(index) {
                return (this.pagination.per_page * (this.pagination.current_page - 1)) + index + 1
            }, 
            async getRecordsByFilter(){

                if(this.requireProduct && !this.form.item_id){
                    this.$message.error('Debe seleccionar un producto para buscar.');
                    return;
                }
                this.loading_submit =  true
                await this.getRecords()
                this.loading_submit =  false

            },
            getRecords() {
                this.$eventHub.$emit('emitItemID', this.form.item_id)

                return this.$http.get(`/${this.resource}/records?${this.getQueryParameters()}`).then((response) => {
                    this.records = response.data.data
                    this.pagination = response.data.meta
                    this.pagination.per_page = Number(this.pagination.per_page);
                    this.loading_submit = false
                });


            },
            getQueryParameters() {
                return queryString.stringify({
                    page: this.pagination.current_page,
                    limit: this.limit,
                    ...this.form,
                })
            },
            async searchItems(query) {
                if (!query) {
                    await this.onWarehouseChange(); // Cargar productos del almacén seleccionado
                    return;
                }
                this.loading_items = true;
                await this.$http.get(`/inventory/search-items`, { params: { query } })
                    .then(response => {
                        this.items = response.data.map(item => ({
                            ...item,
                            full_description: item.description
                        }));
                    })
                    .finally(() => {
                        this.loading_items = false;
                    });
            },
             
        }
    }
</script>