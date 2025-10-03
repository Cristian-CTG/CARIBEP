<template>
<div>
    <div class="page-header pr-0">
        <h2>
            <a href="/finances/payment-method-types">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calculator" style="margin-top: -5px;">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M4 3m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z"></path>
                    <path d="M8 7m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v1a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z"></path>
                    <path d="M8 14l0 .01"></path>
                    <path d="M12 14l0 .01"></path>
                    <path d="M16 14l0 .01"></path>
                    <path d="M8 17l0 .01"></path>
                    <path d="M12 17l0 .01"></path>
                    <path d="M16 17l0 .01"></path>
                </svg>
            </a>
        </h2>
        <ol class="breadcrumbs">
            <li class="active">
                <span>Ingresos y egresos por métodos de pago</span>
            </li>
        </ol>
    </div>
    <div class="card mb-0 pt-2 pt-md-0">
        <!-- <div class="card-header bg-info">
            <h3 class="my-0">Ingresos y egresos por métodos de pago</h3>
        </div> -->
        <div class="card mb-0">
                <div class="card-body">
                    <data-table :resource="resource">
                        <tr slot="heading">
                            <!-- <th class="">#</th> -->
                            <th class=""><strong>Método de pago / Total pagos</strong></th>
                            <th class="text-center"><strong>Factura Electrónica</strong></th>
                            <th class="text-center"><strong>Remisión</strong></th>
                            <th class="text-center"><strong>Documento POS</strong></th>
                            <!-- <th class="text-center"><strong>N. Venta</strong></th> -->
                            <th class="text-center"><strong>Cotización</strong></th>
                            <!-- <th class="text-center"><strong>Contrato</strong></th> -->
                            <th class="text-center"><strong>Ingresos</strong></th>
                            <th class="text-center"><strong>Total Ingresos</strong></th>
                            <th class="text-center"><strong>Compras</strong></th>
                            <th class="text-center"><strong>Documento de Soporte</strong></th>
                            <th class="text-center"><strong>Gastos</strong></th>
                            <!-- <th class="text-center"> <strong>Saldo</strong></th> -->
                            <th class="text-center"><strong>Total Egresos</strong></th>
                        <tr>
                        <tr slot-scope="{ index, row }">
                            <!-- <td>{{ index }}</td>  -->
                            <td>{{row.description}}</td>
                            <td class="text-center">{{ row.document_payment | numberFormat }}</td>
                            <td class="text-center">{{ row.remission_payment | numberFormat }}</td>
                            <td class="text-center">{{ row.document_pos_payment | numberFormat }}</td>
                            <!-- <td class="text-center">{{ (row.sale_note_payment != '-') ? ''+row.sale_note_payment : row.sale_note_payment}}</td> -->
                            <td class="text-center">{{ row.quotation_payment | numberFormat }}</td>
                            <!-- <td class="text-center">{{ (row.contract_payment != '-') ? ''+row.contract_payment : row.contract_payment}}</td> -->
                            <td class="text-center">{{ row.income_payment | numberFormat }}</td>
                            <td class="text-center">{{ calculateTotalIncome(row) | numberFormat }}</td>
                            <td class="text-center">{{ row.purchase_payment | numberFormat }}</td>
                            <td class="text-center">{{ row.support_document_payment | numberFormat }}</td>
                            <td class="text-center">{{ row.expense_payment | numberFormat }}</td>
                            <!-- <td class="text-center">{{row.balance}}</td>  -->
                            <td class="text-center">{{ calculateTotalExpense(row) | numberFormat }}</td>
                        </tr>
                    </data-table>
                </div>
        </div>

    </div>
</div>
</template>

<script>

    import DataTable from '../../components/DataTableWithoutPaging.vue'

    export default {
        components: {DataTable},
        props: ['configuration'],
        data() {
            return {
                resource: 'finances/payment-method-types',
                form: {},

            }
        },
        async created() {
        },
        methods: {
            calculateTotalIncome(row) {
                const doc = row.document_payment !== '-' ? parseFloat(row.document_payment) : 0;
                const rem = row.remission_payment !== '-' ? parseFloat(row.remission_payment) : 0;
                const pos = row.document_pos_payment !== '-' ? parseFloat(row.document_pos_payment) : 0;
                const inc = row.income_payment !== '-' ? parseFloat(row.income_payment) : 0;
                return (doc + rem + pos + inc).toFixed(2);
            },
            calculateTotalExpense(row) {
                const purchase = row.purchase_payment !== '-' ? parseFloat(row.purchase_payment) : 0;
                const support = row.support_document_payment !== '-' ? parseFloat(row.support_document_payment) : 0;
                const expense = row.expense_payment !== '-' ? parseFloat(row.expense_payment) : 0;
                return (purchase + support + expense).toFixed(2);
            },
        }
    }
</script>
