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
                                        <td>{{ row.debit | numberFormat }}</td>
                                        <td>{{ row.credit | numberFormat }}</td>
                                    </template>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-right"><b>Total</b></td>
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
        methods: {
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
