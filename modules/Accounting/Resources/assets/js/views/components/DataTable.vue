<template>
    <div>
        <div class="row ">
            <div class="col-md-12 col-lg-12 col-xl-12 ">
                <div class="row" v-if="applyFilter">
                    <div class="col-lg-4 col-md-4 col-sm-12 pb-2">
                        <label class="control-label">Filtrar por:</label>
                        <el-select v-model="search.column"  placeholder="Select" @change="changeClearInput">
                            <el-option v-for="(label, key) in columns" :key="key" :value="key" :label="label"></el-option>
                        </el-select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-12 pb-2">
                        <label class="control-label">{{columns[search.column]}}:</label>
                        <template v-if="search.column=='date' || search.column=='daterange'">
                            <el-date-picker
                                v-model="search.value"
                                :type="search.column"
                                style="width: 100%;"
                                placeholder="Buscar"
                                value-format="yyyy-MM-dd"
                                range-separator="-"
                                @change="getRecords">
                            </el-date-picker>
                        </template>
                        <template v-else>
                            <el-input placeholder="Buscar"
                                v-model="search.value"
                                style="width: 100%;"
                                prefix-icon="el-icon-search"
                                @input="getRecords">
                            </el-input>
                        </template>
                    </div>
                    <div class="col-lg-4 col-md-4 ">
                        <div class="form-group">
                            <label class="control-label">Tipo comprobante</label>
                            <el-select v-model="search.journal_prefix_id" @change="getRecords" popper-class="el-select-journal_prefix" filterable clearable>
                                <el-option v-for="option in journalPrefixes" :key="option.id" :value="option.id" :label="option.description"></el-option>
                            </el-select>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 ">
                        <div class="form-group">
                            <label class="control-label">Estado</label>
                            <el-select v-model="search.status" @change="getRecords" popper-class="el-select-journal_prefix" filterable clearable>
                                <el-option v-for="option in statuses" :key="option.id" :value="option.id" :label="option.name"></el-option>
                            </el-select>
                        </div>
                    </div>
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
            resource: { type: String, required: true },
            applyFilter:{
                type: Boolean,
                default: true,
                required: false
            },
            journalPrefixes: {
                type: Array,
                default: () => [],
                required: false
            },
        },
        data () {
            return {
                search: {
                    column: null,
                    value: null,
                    journal_prefix_id: null,
                },
                columns: [],
                records: [],
                pagination: {},
                statuses: [
                    { id: 'rejected', name: 'Rechazado' },
                    { id: 'draft', name: 'Borrador' },
                    { id: 'posted', name: 'Aprobado' },
                ]
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
            await this.$http.get(`/${this.resource}/columns`).then((response) => {
                this.columns = response.data
                this.search.column = _.head(Object.keys(this.columns))
            });
            await this.getRecords()

        },
        methods: {
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
                let searchParams = { ...this.search };
                // Si es un rango de fechas, convierte el array en string
                if (searchParams.column === 'daterange' && Array.isArray(searchParams.value)) {
                    searchParams.value = searchParams.value.join('_');
                }
                return queryString.stringify({
                    page: this.pagination.current_page,
                    limit: this.limit,
                    ...searchParams
                });
            },
            changeClearInput(){
                this.search.value = ''
                this.getRecords()
            },
        }
    }
</script>
