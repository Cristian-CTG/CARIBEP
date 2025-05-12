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
                    <span>Reporte de Estado de Resultados</span>
                </li>
            </ol>
        </div>
        <data-table :data="accounts" :columns="columns" />
        <div class="net-result">
            <h3>Resultado Neto: {{ netResult | currency }}</h3>
        </div>
    </div>
</template>

<script>
import DataTable from '../components/DataTableReport.vue';

export default {
    components: { DataTable },
    data() {
        return {
            accounts: [],
            netResult: 0,
            columns: [
                { label: 'Código', field: 'code' },
                { label: 'Nombre', field: 'name' },
                { label: 'Tipo', field: 'type' },
                { label: 'Saldo', field: 'saldo', type: 'currency' },
            ],
        };
    },
    mounted() {
        this.fetchData();
    },
    methods: {
        async fetchData() {
            const response = await this.$http.get('/accounting/income-statement/records');
            this.accounts = response.data.accounts;
            this.netResult = response.data.net_result;
        },
    },
};
</script>