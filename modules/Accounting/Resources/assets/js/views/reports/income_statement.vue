<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/accounting/income-statement">
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
                    <span>Reporte de Estado de Resultados</span>
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
                <data-table-report title="Ingresos" :data="revenueAccounts" :columns="columns" :total="totals.revenue"/>
                <data-table-report title="Gastos" :data="expenseAccounts" :columns="columns" :total="totals.expense"/>
                <data-table-report title="Costos" :data="costAccounts" :columns="columns" :total="totals.cost"/>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <td class="text-bold">Utilidad Bruta</td>
                            <td class="text-right">{{ gross_profit | numberFormat }}</td>
                        </tr>
                        <tr>
                            <td class="text-bold">Utilidad Operativa</td>
                            <td class="text-right">{{ operating_profit | numberFormat }}</td>
                        </tr>
                        <tr>
                            <td class="text-bold">Resultado Neto</td>
                            <td class="text-right">{{ net_profit | numberFormat }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import DataTableReport from '../components/DataTableReport.vue';
import queryString from 'query-string';

export default {
    components: { DataTableReport },
    data() {
        return {
            revenueAccounts: [],
            costAccounts: [],
            expenseAccounts: [],
            totals: 0,
            gross_profit: 0,
            operating_profit: 0,
            net_profit: 0,
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
            const response = await this.$http.get('/accounting/income-statement/records', { params });
            this.revenueAccounts = response.data.revenues;
            this.costAccounts = response.data.costs;
            this.expenseAccounts = response.data.expenses;
            this.totals = response.data.totals;
            this.gross_profit = response.data.gross_profit;
            this.operating_profit = response.data.operating_profit;
            this.net_profit = response.data.net_profit;
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

            window.open(`/accounting/income-statement/export?${queryString.stringify(params)}`, '_blank');
        }
    },
};
</script>