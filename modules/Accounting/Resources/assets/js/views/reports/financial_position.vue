<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/dashboard">
                    <i class="fas fa-tachometer-alt"></i>
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
                <div class="row">
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
                        <el-button type="primary" @click="ReportDownload('pdf')">Pdf</el-button>
                        <el-button type="success" @click="ReportDownload('excel')">Excel</el-button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="d-flex justify-content-between">
                            <h4>Activos</h4>
                            <h4 class="text-right">{{ accounts.totals.assets || 0.00 }}</h4>
                        </div>
                        <data-table :data="accounts.assets" :columns="columns" />
                    </div>
                    <div class="col-lg-6">
                        <div class="d-flex justify-content-between">
                            <h4>Pasivos</h4>
                            <h4 class="text-right">{{ accounts.totals.liabilities || 0.00 }}</h4>
                        </div>
                        <data-table :data="accounts.liabilities" :columns="columns" />
                        <hr>
                        <div class="d-flex justify-content-between">
                            <h4>Patrimonio</h4>
                            <h4 class="text-right">{{ accounts.totals.equity || 0.00 }}</h4>
                        </div>
                        <data-table :data="accounts.equity" :columns="columns" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 bg-light">
                        <div class="d-flex justify-content-between">
                            <h4>Activos</h4>
                            <h4 class="text-right">Total: {{ accounts.totals.assets || 0.00 }}</h4>
                        </div>
                    </div>
                    <div class="col-lg-6 bg-light">
                        <div class="d-flex justify-content-between">
                            <h4>Pasivos + Patrimonio</h4>
                            <h4 class="text-right">Total: {{ accounts.totals.liabilities + accounts.totals.equity || 0.00 }}</h4>
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