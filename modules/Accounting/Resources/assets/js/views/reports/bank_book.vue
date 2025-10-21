<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <span>Libro de Bancos</span>
            </h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Exportar reporte</span></li>
            </ol>
        </div>
        <div class="card mb-0 pt-2 pt-md-0">
            <div class="card-body">
                <div class="row mt-2">
                    <div class="col-md-4 pb-1">
                        <div class="form-group">
                            <label>Mes</label>
                            <el-date-picker
                                v-model="form.month"
                                type="month"
                                value-format="yyyy-MM"
                                format="MM/yyyy"
                                :clearable="false"
                            ></el-date-picker>
                        </div>
                    </div>
                    <div class="col-md-5 pb-1">
                        <div class="form-group">
                            <label>Cuenta Bancaria / Caja</label>
                            <el-select v-model="form.bank_account_id" placeholder="Seleccione" @change="onBankAccountChange">
                                <el-option
                                    key="cash"
                                    label="Caja"
                                    :value="'cash'">
                                </el-option>
                                <el-option
                                    v-for="account in bankAccounts"
                                    :key="account.id"
                                    :label="account.description"
                                    :value="account.id">
                                </el-option>
                            </el-select>
                        </div>
                    </div>
                    <div class="col-lg-12 mt-3">
                        <el-button
                            type="danger"
                            icon="el-icon-tickets"
                            @click.prevent="clickDownload('pdf')"
                            :disabled="!canExport"
                        >Exportar PDF</el-button>
                        <el-button
                            type="warning"
                            icon="el-icon-tickets"
                            @click.prevent="clickDownload('excel')"
                            :disabled="!canExport"
                        >Exportar Excel</el-button>
                    </div>
                    <div v-if="!hasAccounts" class="alert alert-warning mt-2">
                        No hay cuentas bancarias ni cajas abiertas. Por favor, cree una antes de continuar.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import queryString from 'query-string'

export default {
    data() {
        return {
            form: {
                month: '',
                bank_account_id: null,
                auxiliar: '', // Se asigna automáticamente
            },
            bankAccounts: [],
        }
    },
    computed: {
        hasAccounts() {
            return this.bankAccounts && this.bankAccounts.length > 0;
        },
        canExport() {
            return this.hasAccounts && !!this.form.bank_account_id;
        }
    },
    async created() {
        await this.fetchBankAccounts()
        this.initForm()
    },
    methods: {
        initForm() {
            const today = new Date()
            this.form.month = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0')
        },
        async fetchBankAccounts() {
            const response = await this.$http.get('/accounting/bank-book/records');
            this.bankAccounts = response.data;
            // Si hay cuentas, selecciona la primera por defecto
            if (this.bankAccounts.length > 0) {
                this.form.bank_account_id = 'cash'
                this.onBankAccountChange('cash')
            }
        },
        onBankAccountChange(accountId) {
            if (accountId === 'cash') {
                this.form.auxiliar = '110505'
            } else {
                const selected = this.bankAccounts.find(acc => acc.id === accountId)
                this.form.auxiliar = selected && selected.chart_of_account ? selected.chart_of_account.code : ''
            }
        },
        clickDownload(type) {
            const params = { ...this.form, type }
            window.open(`/accounting/bank-book/export?${queryString.stringify(params)}`, '_blank')
        },
    }
}
</script>