<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/accounting/financial-position">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chart-histogram" style="margin-top: -5px;">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M3 3v18h18"></path>
                        <path d="M20 18v3"></path>
                        <path d="M16 16v5"></path>
                        <path d="M12 13v8"></path>
                        <path d="M8 16v5"></path>
                        <path d="M3 11c6 0 5 -5 9 -5s3 5 9 5"></path>
                    </svg>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active">
                    <span>Reporte de Situación Financiera</span>
                </li>
            </ol>
        </div>
        <div class="card mb-0">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6">
                        <div class="filter-container">
                            <el-date-picker
                                v-model="dateRange"
                                type="daterange"
                                range-separator="a"
                                start-placeholder="Fecha inicio"
                                end-placeholder="Fecha fin"
                                @change="onDateChange"
                                format="yyyy-MM-dd"
                                value-format="yyyy-MM-dd"
                                class="date-picker"
                            />
                        </div>
                    </div>
                    <div class="col-6 text-right">
                        <div class="d-inline-block mb-2">
                            <el-button type="primary" @click="ReportDownload('pdf')">Pdf</el-button>
                        </div>
                        <div class="d-inline-block mb-2 ml-2">
                            <el-button type="success" @click="ReportDownload('excel')">Excel</el-button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <data-table
                            title="Activos"
                            :data="accounts.assets"
                            :columns="columns"
                            :total="accounts.totals.assets" />
                    </div>
                    <div class="col-lg-6">
                        <data-table
                            title="Pasivos"
                            :data="accounts.liabilities"
                            :columns="columns"
                            :total="accounts.totals.liabilities" />
                        <data-table
                            title="Patrimonio"
                            :data="accounts.equity"
                            :columns="columns"
                            :total="accounts.totals.equity"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 bg-light">
                        <div class="d-flex justify-content-between">
                            <h4>Activos</h4>
                            <h4 class="text-right">Total: {{ accounts.totals.assets || 0.00 | numberFormat }}</h4>
                        </div>
                    </div>
                    <div class="col-lg-6 bg-light">
                        <div class="d-flex justify-content-between">
                            <h4>Pasivos + Patrimonio</h4>
                            <h4 class="text-right">Total: {{ accounts.totals.liabilities + accounts.totals.equity || 0.00 | numberFormat }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import DataTable from '../components/DataTableReport.vue';
import queryString from 'query-string';

export default {
    components: { DataTable },
    data() {
        return {
            accounts: {
                assets: {},
                liabilities: {},
                equity: {},
                totals: {
                    assets: 0,
                    liabilities: 0,
                    equity: 0
                }
            },
            columns: [
                { label: 'Código', field: 'code' },
                { label: 'Nombre', field: 'name' },
                { label: 'Saldo', field: 'saldo', type: 'currency' },
            ],
            dateRange: [],
        };
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        async fetchData(params = {}) {
            const response = await this.$http.get('/accounting/financial-position/records', { params });
            this.accounts = response.data;
        },
        onDateChange() {
            let params = {
                date_start: this.dateRange[0],
                date_end: this.dateRange[1],
            };
            this.fetchData(params);
        },
        ReportDownload(type = 'pdf') {
            let params = {
                date_start: this.dateRange[0],
                date_end: this.dateRange[1],
                format: type,
            };

            window.open(`/accounting/financial-position/export?${queryString.stringify(params)}`, '_blank');
        }
    },
};
</script>