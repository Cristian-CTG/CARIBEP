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
                        <table class="table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Código de cuenta</th>
                                <th>Nombre de cuenta</th>
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
                                    <td>{{ row.debit }}</td>
                                    <td>{{ row.credit }}</td>
                                </template>
                            </tr>
                            </tbody>
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
