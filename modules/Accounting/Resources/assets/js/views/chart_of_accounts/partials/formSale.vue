<template>
    <el-dialog width="65%" :title="titleDialog" :visible="showDialog" :close-on-click-modal="false" @close="close" @open="create" append-to-body top="7vh">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-4">
                          <div class="form-group">
                              <label class="control-label">Ingresos</label>
                              <el-select v-model="form.income_account" filterable >
                                  <el-option v-for="option in chart_accounts_sales" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group">
                              <label class="control-label">	Devoluciones en ventas</label>
                              <el-select v-model="form.sales_returns_account" filterable >
                                  <el-option v-for="option in chart_accounts_purchases" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group">
                              <label class="control-label">	Clasificación contable</label>
                              <el-input v-model="form.accounting_clasification"/>
                          </div>
                      </div>
                    
                </div>
            </div>
            <div class="form-actions text-right pt-2">
                <el-button @click.prevent="close()">Cancelar</el-button>
                <el-button type="primary" native-type="submit" :loading="loading_submit" >Guardar</el-button>
            </div>
        </form>
    </el-dialog>
</template>

<script>
    import moment from 'moment'

    export default {
        props: ['showDialog', 'recordId'],
        data() {
            return {
                showDialogLots:false,
                loading_submit: false,
                titleDialog: 'Nueva Clasificación Contable',
                resource: 'accounting/clasification-sale',
                errors: {},
                headers: headers_token,
                form: {
                },
                chart_accounts_sales: [],
                chart_accounts_purchases: [],
            }
        },

        async created() {
            await this.initForm()
        },

        computed: {
            urlData() {
                return (this.form.id) ? {method: 'put', url: `/${this.form.id}`} : {method: 'post', url: '/'};
            }
        },

        methods: {
            initForm() {
                this.loading_submit = false;
                this.errors = {
                };
                this.form = {
                    id: null,
                    income_account: null,
                    sales_returns_account: null,
                    accounting_clasification: null
                }
            },
            resetForm() {
                this.initForm()
            },

            async create() {
                this.titleDialog = (this.recordId)? 'Editar Clasificación Contable':'Nueva Clasificación Contable'
                await this.$http.get(`/${this.resource}/tables`)
                    .then(response => {
                        this.chart_accounts_sales = response.data.chart_accounts_sales
                        this.chart_accounts_purchases = response.data.chart_accounts_purchases
                    })
                    
                if (this.recordId) {
                    this.$http.get(`/${this.resource}/record/${this.recordId}`)
                        .then(response => {
                            this.form = response.data
                        })
                }
            },

            async submit() {
                this.loading_submit = true
                if(this.form.id){
                    await this.$http.put(`/${this.resource}/${this.form.id}`, this.form)
                        .then(response => {
                            if (response.data.success) {
                                this.$message.success(response.data.message)
                                this.$eventHub.$emit('reloadData')
                                this.close()
                            } else {
                                this.$message.error(response.data.message)
                            }
                        })
                        .catch(error => {
                            if (error.response.status === 422) {
                                this.errors = error.response.data
                            } else {
                                console.log(error)
                            }
                        })
                        .then(() => {
                            this.loading_submit = false
                        })
                }else{
                    await this.$http.post(`/${this.resource}`, this.form)
                        .then(response => {
                            if (response.data.success) {
                                this.$message.success(response.data.message)
                                this.$eventHub.$emit('reloadData')
                                this.close()
                            } else {
                                this.$message.error(response.data.message)
                            }
                        })
                        .catch(error => {
                            if (error.response.status === 422) {
                                this.errors = error.response.data
                            } else {
                                console.log(error)
                            }
                        })
                        .then(() => {
                            this.loading_submit = false
                        })
                }
            },
            close() {
                this.$emit('update:showDialog', false)
                this.resetForm()
            }
        }
    }
</script>
