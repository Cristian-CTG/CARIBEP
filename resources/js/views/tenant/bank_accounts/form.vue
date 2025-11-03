<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @close="close" @open="create">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group" :class="{'has-danger': errors.bank_id}">
                            <label class="control-label">Banco <span style="color:red">*</span></label>
                            <el-select v-model="form.bank_id">
                                <el-option v-for="option in banks" :key="option.id" :value="option.id" :label="option.description"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.bank_id" v-text="errors.bank_id[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group" :class="{'has-danger': errors.description}">
                            <label class="control-label">Descripción <span style="color:red">*</span></label>
                            <el-input v-model="form.description"></el-input>
                            <small class="form-control-feedback" v-if="errors.description" v-text="errors.description[0]"></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group" :class="{'has-danger': errors.number}">
                            <label class="control-label">Número <span style="color:red">*</span></label>
                            <el-input v-model="form.number"></el-input>
                            <small class="form-control-feedback" v-if="errors.number" v-text="errors.number[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group" :class="{'has-danger': errors.currency_id}">
                            <label class="control-label">Moneda <span style="color:red">*</span></label>
                            <el-select v-model="form.currency_id" filterable>
                                <el-option v-for="option in currencies" :key="option.id" :value="option.id" :label="option.name"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.currency_id" v-text="errors.currency_id[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group" :class="{'has-danger': errors.chart_of_account_id}">
                            <label class="control-label">Cuenta contable <span style="color:red">*</span></label>
                            <el-select v-model="form.chart_of_account_id" filterable>
                                <el-option v-for="option in chart_of_accounts" :key="option.id" :value="option.id" :label="option.code + ' - ' + option.name"></el-option>
                            </el-select>
                            <small class="form-control-feedback" v-if="errors.chart_of_account_id" v-text="errors.chart_of_account_id[0]"></small>
                        </div>
                    </div>
                    <!-- <div class="col-md-8">
                        <div class="form-group" :class="{'has-danger': errors.cci}">
                            <label class="control-label">CCI</label>
                            <el-input v-model="form.cci"></el-input>
                            <small class="form-control-feedback" v-if="errors.cci" v-text="errors.cci[0]"></small>
                        </div>
                    </div> -->
                </div>
            </div>
            <div class="form-actions text-right mt-4">
                <el-button @click.prevent="close()">Cancelar</el-button>
                <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
            </div>
        </form>
    </el-dialog>

</template>

<script>

    import {EventBus} from '../../../helpers/bus'

    export default {
        props: ['showDialog', 'recordId'],
        data() {
            return {
                loading_submit: false,
                titleDialog: null,
                resource: 'bank_accounts',
                errors: {},
                form: {},
                banks: [],
                currencies: [],
                chart_of_accounts: [],
            }
        },
        async created() {
            this.initForm()
            await this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.banks = response.data.banks
                    this.currencies = response.data.currencies
                    this.chart_of_accounts = response.data.chart_of_accounts
                })
//            await this.$http.get(`/${this.resource}/record`)
//                .then(response => {
//                    if (response.data !== '') {
//                        this.form = response.data.data
//                    }
//                })
        },
        methods: {
            initForm() {
                this.errors = {}
                this.form = {
                    id: null,
                    bank_id: null,
                    description: null,
                    number: null,
                    currency_id: null,
                    cci: null,
                    chart_of_account_id: null
                }
            },
            create() {
                this.titleDialog = (this.recordId)? 'Editar Cuenta Bancaria':'Nueva Cuenta Bancaria'
                if (this.recordId) {
                    this.$http.get(`/${this.resource}/record/${this.recordId}`)
                        .then(response => {
                            this.form = response.data.data
                        })
                }
            },
            validateForm() {
                this.errors = {};
                if (!this.form.bank_id) {
                    this.errors.bank_id = ['El banco es obligatorio.'];
                }
                if (!this.form.description) {
                    this.errors.description = ['La descripción es obligatoria.'];
                }
                if (!this.form.number) {
                    this.errors.number = ['El número es obligatorio.'];
                }
                if (!this.form.currency_id) {
                    this.errors.currency_id = ['La moneda es obligatoria.'];
                }
                if (!this.form.chart_of_account_id) {
                    this.errors.chart_of_account_id = ['La cuenta contable es obligatoria.'];
                }
                // Si hay algún error, retorna false
                return Object.keys(this.errors).length === 0;
            },
            submit() {
                if (!this.validateForm()) {
                    this.$message.error('Por favor complete todos los campos obligatorios.');
                    return;
                }
                this.loading_submit = true
                this.$http.post(`/${this.resource}`, this.form)
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
            },
            close() {
                this.$emit('update:showDialog', false)
                this.initForm()
            },
        }
    }
</script>
