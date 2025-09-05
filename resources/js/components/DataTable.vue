<template>
    <div>
        <div class="row">
            <div class="col-md-12 col-lg-12 col-xl-12">
                <!-- Botón para mostrar/ocultar todos los filtros -->
                <template v-if="extraFilters">
                    <div class="d-flex mb-2" style="align-items: flex-start;">
                        <el-button 
                            @click="toggleFilters" 
                            type="primary" 
                            plain 
                            icon="el-icon-setting"
                            size="mini"
                            style="margin-right: 10px;">
                            {{ showFilters ? 'Ocultar filtros' : 'Mostrar filtros' }}
                        </el-button>
                    </div>
                </template>
                <!-- Todos los filtros se muestran/ocultan con showFilters -->
                <div v-if="showFilters">
                    <div class="row" v-if="applyFilter">
                        <div class="col-lg-3 col-md-3 col-sm-12 pb-2">
                            <div class="d-flex">
                                <div style="width:100px">
                                    Filtrar por:
                                </div>
                                <el-select v-model="search.column" placeholder="Select" @change="changeClearInput">
                                    <el-option v-for="(label, key) in columns" :key="key" :value="key" :label="label"></el-option>
                                </el-select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12 pb-2">
                            <template v-if="search.column=='date_of_issue'">
                                <div class="d-flex">
                                    <el-select 
                                        v-model="filterType" 
                                        placeholder="Tipo de filtro" 
                                        style="width: 50%; margin-right: 8px;">
                                        <el-option label="Por mes" value="month"></el-option>
                                        <el-option label="Por fecha" value="date"></el-option>
                                        <el-option label="Entre fechas" value="range" v-if="extraFilters"></el-option>
                                    </el-select>
                                    
                                    <template v-if="filterType === 'month'">
                                        <el-date-picker
                                            v-model="search.value"
                                            type="month"
                                            style="width: calc(100% - 130px)"
                                            placeholder="Seleccione mes"
                                            value-format="yyyy-MM"
                                            @change="getRecords">
                                        </el-date-picker>
                                    </template>
                                    <template v-else-if="filterType === 'date'">
                                        <el-date-picker
                                            v-model="search.value"
                                            type="date"
                                            style="width: calc(100% - 130px)"
                                            placeholder="Seleccione fecha"
                                            value-format="yyyy-MM-dd"
                                            :clearable="true"
                                            :editable="false"
                                            @change="onDateFieldChange('value', $event)">
                                        </el-date-picker>
                                    </template>
                                    <template v-else-if="filterType === 'range'">
                                        <template v-if="extraFilters">
                                            <el-date-picker
                                                v-model="search.fecha_inicio"
                                                type="date"
                                                style="width: calc(50% - 65px);margin-right: 8px;"
                                                placeholder="Fecha inicio"
                                                value-format="yyyy-MM-dd"
                                                :clearable="true"
                                                :editable="false"
                                                @change="onDateFieldChange('fecha_inicio', $event)">
                                            </el-date-picker>
                                            <el-date-picker
                                                v-model="search.fecha_fin"
                                                type="date"
                                                style="width: calc(50% - 65px)"
                                                placeholder="Fecha fin"
                                                value-format="yyyy-MM-dd"
                                                :clearable="true"
                                                :editable="false"
                                                @change="onDateFieldChange('fecha_fin', $event)">
                                            </el-date-picker>
                                        </template>
                                    </template>
                                </div>
                            </template>
                            <template v-else>
                                <el-input 
                                    placeholder="Buscar"
                                    v-model="search.value"
                                    style="width: 100%;"
                                    prefix-icon="el-icon-search"
                                    @input="getRecords">
                                </el-input>
                            </template>
                        </div>
                    </div>
                    <template v-if="search.column=='date_of_issue' && extraFilters">
                        <div class="row" v-if="applyFilter">
                            <div class="col-lg-3 col-md-3 col-sm-12 pb-2">
                                <div class="d-flex">
                                    <div style="width:100px">
                                        Resolucion:
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <el-select 
                                        v-model="selectedResolution" 
                                        placeholder="Selector de resoluciones activas" 
                                        clearable 
                                        @change="getRecords">
                                        <el-option
                                            v-for="res in resolutions"
                                            :key="res.id"
                                            :label="res.description"
                                            :value="res.id">
                                        </el-option>
                                        <template v-if="resolutions.length === 0">
                                            <el-option disabled label="No hay resoluciones activas" value=""></el-option>
                                        </template>
                                    </el-select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-12 pb-2">
                                <div class="d-flex">
                                    <div style="width:100px">
                                        Cliente:
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <el-select
                                        v-model="selectedCustomer"
                                        filterable
                                        clearable
                                        placeholder="Seleccione cliente"
                                        style="width:100%;"
                                        @change="onCustomerChange"
                                    >
                                        <el-option
                                            v-for="customer in customers"
                                            :key="customer.id"
                                            :label="customer.name"
                                            :value="customer.id"
                                        ></el-option>
                                    </el-select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-12 pb-2">
                                <div class="d-flex">
                                    <div style="width:100px">
                                        Estado:
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <el-select
                                        v-model="selectedState"
                                        placeholder="Seleccione estado"
                                        clearable
                                        @change="getRecords"
                                        style="width:100%;">
                                        <el-option
                                            v-for="state in states"
                                            :key="state.id"
                                            :label="state.name"
                                            :value="state.id"
                                        ></el-option>
                                    </el-select>
                                </div>
                            </div>
                        </div>
                        <div class="row" v-if="applyFilter">
                            <!-- <div class="col-lg-4 col-md-6 col-sm-12 pb-2">
                                <div class="d-flex">
                                    <div style="width:150px">
                                        N.º comprobante
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <el-input
                                        v-model="comprobanteSearch"
                                        placeholder="(Ej: SETP-990008515 o SETP990008515)"
                                        clearable
                                        @input="onComprobanteSearch"
                                        prefix-icon="el-icon-search"
                                    ></el-input>
                                </div>                            
                            </div> -->
                            <!-- <div class="col-lg-4 col-md-6 col-sm-12 pb-2">
                                <div class="d-flex">
                                    <div style="width:100px">
                                        Estado:
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <el-select
                                        v-model="selectedState"
                                        placeholder="Seleccione estado"
                                        clearable
                                        @change="getRecords"
                                        style="width:100%;">
                                        <el-option
                                            v-for="state in states"
                                            :key="state.id"                                            :label="state.name"
                                            :value="state.id"
                                        ></el-option>
                                    </el-select>
                                </div>
                            </div> -->
                        </div>
                    </template>
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

<script>
    import moment from 'moment'
    import queryString from 'query-string'

    export default {
        props: {
            resource: String,
            applyFilter:{
                type: Boolean,
                default: true,
                required: false  
            },
            initSearch: {
                type: Object,
                default: null
            },
            extraFilters: {
                type: Boolean,
                default: false
            }
        },
        data () {
            return {
                filterType: 'month',
                search: {
                    column: null,
                    value: null,
                    fecha_inicio: null,
                    fecha_fin: null
                },
                columns: [],
                records: [],
                pagination: {},
                resolutions: [],
                selectedResolution: null,
                comprobanteSearch: '',
                customers: [],
                selectedCustomer: null,
                states: [],
                selectedState: null,
                showFilters: true
            }
        },
        computed: {
        },
        created() {
            this.$eventHub.$on('reloadData', () => {
                this.getRecords()
            })
        },
        async mounted () {
            let column_resource = _.split(this.resource, '/')
           // console.log(column_resource)
            await this.$http.get(`/${_.head(column_resource)}/columns`).then((response) => {
                this.columns = response.data
                if (this.initSearch) {
                    this.search = {
                        ...this.search,
                        ...this.initSearch
                    }
                } else {
                    this.search.column = _.head(Object.keys(this.columns))
                    if (this.search.column === 'date_of_issue') {
                        const date = new Date();
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        this.filterType = 'month';
                        this.search.value = `${year}-${month}`;
                    }
                }
            });
            if (this.extraFilters) {
                await this.fetchResolutions();
                await this.fetchCustomers();
                await this.fetchStates();
            }
            await this.getRecords()

        },
        methods: {
            toggleFilters() {
                this.showFilters = !this.showFilters;
                // if (!this.showFilters) {
                //     // Limpiar filtros al ocultar
                //     this.selectedResolution = null;
                //     this.selectedCustomer = null;
                //     this.comprobanteSearch = '';
                //     this.selectedState = null;
                //     this.search.fecha_inicio = '';
                //     this.search.fecha_fin = '';
                //     this.filterType = 'month';
                //     // Establecer mes actual
                //     const date = new Date();
                //     const year = date.getFullYear();
                //     const month = String(date.getMonth() + 1).padStart(2, '0');
                //     this.search.value = `${year}-${month}`;
                //     this.getRecords();
                // }
            },
            onComprobanteSearch() {
                this.getRecords();
            },
            async fetchCustomers() {
                const res = await this.$http.get(`/${this.resource}/customers-list`);
                this.customers = res.data;
            },
            async fetchResolutions() {
                const res = await this.$http.get(`/${this.resource}/active-resolutions`);
                this.resolutions = res.data;
            },
            async fetchStates() {
                const res = await this.$http.get(`/${this.resource}/states-list`);
                this.states = res.data;
            },
            onCustomerChange() {
                this.getRecords();
            },
            customIndex(index) {
                return (this.pagination.per_page * (this.pagination.current_page - 1)) + index + 1
            },
            getRecords() {
                return this.$http.get(`/${this.resource}/records?${this.getQueryParameters()}`).then((response) => {
                    this.records = response.data.data
                    this.pagination = response.data.meta
                    this.pagination.per_page = parseInt(response.data.meta.per_page)
                });
            },
            getQueryParameters() {
                let params = {
                    page: this.pagination.current_page,
                    limit: this.limit,
                    ...this.search
                };
                // Si el filtro es por rango, solo envía fecha_inicio y fecha_fin
                if (this.filterType == 'range') {
                    params.value = null;
                } else {
                    params.fecha_inicio = null;
                    params.fecha_fin = null;
                }
                if (this.selectedResolution) {
                    params.resolution_id = this.selectedResolution;
                }
                if (this.comprobanteSearch) {
                    params.comprobante = this.comprobanteSearch;
                }
                if (this.selectedCustomer) {
                    params.customer_id = this.selectedCustomer;
                }
                if (this.selectedState) {
                    params.state_document_id = this.selectedState;
                }
                return queryString.stringify(params);
            },
            changeClearInput(){
                this.search.value = '';
                this.search.fecha_fin = '';
                this.search.fecha_inicio = '';
                if (this.search.column === 'date_of_issue') {
                    this.filterType = 'month';
                } else {
                    this.filterType = '';
                }
                this.getRecords();
            },
            onDateFieldChange(field, date) {
                this.search[field] = date ? moment(date).format('YYYY-MM-DD') : '';
                this.getRecords();
            },
        },
        watch: {
            filterType(newValue) {
                this.search.value = '';
                this.search.fecha_inicio = '';
                this.search.fecha_fin = '';
                if (newValue === 'month') {
                    // Establecer la fecha actual en formato mes
                    const date = new Date();
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    this.search.value = `${year}-${month}`;
                }
                this.getRecords();
            }
        }
    }
</script>

<style scoped>
.d-flex {
    display: flex;
    align-items: center;
    width: 100%;
}
</style>
