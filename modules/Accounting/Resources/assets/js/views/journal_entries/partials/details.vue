<template>
    <el-dialog :title="title" :visible="showDialog" @close="close" @open="getData" width="65%">
        <div class="form-body">
            <div class="row">
                <div class="col-md-3">
                    <p><b>Fecha:</b> {{ journal.date }}</p>
                </div>
                <div class="col-md-3">
                    <p><b>Estado:</b> {{ (journal.status=='posted')?'Aprobado': ''}}</p>
                </div>
                <div class="col-md-6">
                    <p><b>Descripción:</b> {{ journal.description }}</p>
                </div>

                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>Código de cuenta</th>
                                    <th>Nombre de cuenta</th>
                                    <th>Tercer Implicado</th>
                                    <th v-if="showBankAndPayment">Banco/Caja</th>
                                    <th v-if="showBankAndPayment">Método de Pago</th>
                                    <th>Debe</th>
                                    <th>Haber</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, index) in records" :key="index">
                                    <template v-if="row.id">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ row.date }}</td>
                                        <td>{{ row.chart_account_code }}</td>
                                        <td>{{ row.chart_account_name }}</td>
                                        <td>
                                            <span v-if="row.third_party_name">
                                                {{ row.third_party_name }} <span class="text-muted">({{ getThirdPartyTypeName(row.third_party_type) }})</span>
                                            </span>
                                            <span v-else>-</span>
                                        </td>
                                        <td v-if="showBankAndPayment">
                                            <span v-if="row.bank_account_id">
                                                {{ row.bank_account_name ? row.bank_account_name : row.bank_account_id }}
                                                <span v-if="row.bank_account_number"> - {{ row.bank_account_number }}</span>
                                            </span>
                                            <span v-else-if="row.payment_method_name && row.chart_account_code === '110505'">
                                                Caja
                                            </span>
                                            <span v-else>-</span>
                                        </td>
                                        <td v-if="showBankAndPayment">
                                            <span v-if="row.payment_method_name">{{ row.payment_method_name }}</span>
                                            <span v-else>-</span>
                                        </td>
                                        <td>{{ row.debit | numberFormat }}</td>
                                        <td>{{ row.credit | numberFormat }}</td>
                                    </template>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td :colspan="showBankAndPayment ? 7 : 5" class="text-right"><b>Total</b></td>
                                    <td>
                                        <b>{{ records.reduce((sum, r) => sum + Number(r.debit || 0), 0).toFixed(2) | numberFormat }}</b>
                                    </td>
                                    <td>
                                        <b>{{ records.reduce((sum, r) => sum + Number(r.credit || 0), 0).toFixed(2) | numberFormat }}</b>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </el-dialog>

</template>

<script>

    export default {
        props: ['showDialog', 'recordId'],
        mixins: [],
        data() {
            return {
                title: null,
                resource: 'accounting/journal/entries',
                records: [],
                payment_destinations:  [],
                headers: headers_token,
                fileList: [],
                payment_method_types: [],
                showAddButton: true,
                document: {},
                journal:{},
                index_file: null,
            }
        },
        async created() {

        },
        computed: {
            showBankAndPayment() {
                return this.records.some(r => r.bank_account_id || r.payment_method_name);
            }
        },
        methods: {
            getBankName(bank_account_id) {
                const bank = this.$parent.banks?.find(b => b.id == bank_account_id);
                return bank ? bank.description + (bank.number ? ' - ' + bank.number : '') : bank_account_id;
            },
            getThirdPartyTypeName(type) {
                switch(type) {
                    case 'customers': return 'Cliente';
                    case 'suppliers': return 'Proveedor';
                    case 'employee': return 'Empleado';
                    case 'seller': return 'Vendedor';
                    default: return type || '-';
                }
            },
            async getData() {
                await this.$http.get(`/${this.resource}/${this.recordId}`)
                    .then(response => {
                        this.journal = response.data.data;
                        this.title = 'Detalle asiento contable: '+ this.journal.journal_prefix.prefix +' - '+this.journal.journal_prefix.description;
                    });
                await this.$http.get(`/${this.resource}/${this.recordId}/records-detail`)
                    .then(response => {
                        this.records = response.data.data
                    });

                this.$eventHub.$emit('reloadDataUnpaid')

            },
            close() {
                this.$emit('update:showDialog', false);
            }
        }
    }
</script>
