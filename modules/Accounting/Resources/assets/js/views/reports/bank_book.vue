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
        <!-- <div v-if="previewLoading" class="text-center my-3">
            <el-spinner></el-spinner> Cargando vista previa...
        </div> -->
        <div v-if="preview.length > 0">
            <h4 class="mt-4">Vista previa de movimientos</h4>
            <table class="table table-sm mb-0" style="width:100%; border-collapse:separate; border-spacing:0;">
                <tbody>
                    <tr style="font-weight:bold;">
                        <td style="width:100px"></td>
                        <td style="width:120px"></td>
                        <td style="width:120px"></td>
                        <td></td>
                        <td style="width:60px"></td>
                        <td style="width:100px; text-align:left;">Saldo inicial</td>
                        <td style="width:100px; text-align:left;">Débito</td>
                        <td style="width:100px; text-align:left;">Crédito</td>
                        <td style="width:100px; text-align:left;">Saldo final</td>
                    </tr>
                    <tr>
                        <td style="width:100px"></td>
                        <td style="width:120px"></td>
                        <td style="width:120px"></td>
                        <td></td>
                        <td style="width:60px; text-align:left; ">Totales:</td>
                        <td style="width:100px; text-align:left; ">{{ saldo_inicial }}</td>
                        <td style="width:100px; text-align:left; ">{{ debito_total }}</td>
                        <td style="width:100px; text-align:center; ">{{ credito_total }}</td>
                        <td style="width:100px; text-align:left; ">{{ saldo_final }}</td>
                    </tr>
                </tbody>
            </table>
            <el-table :data="preview" style="width: 100%" size="mini">
                <el-table-column prop="date" label="Fecha" width="100"/>
                <el-table-column prop="document" label="Documento" width="120"/>
                <el-table-column prop="payment_method" label="Método" width="120"/>
                <el-table-column prop="description" label="Concepto"/>
                <el-table-column prop="type" label="Tipo" width="60"/>
                <el-table-column prop="debit" label="Débito" width="100"/>
                <el-table-column prop="credit" label="Crédito" width="100"/>
                <el-table-column prop="balance" label="Saldo" width="100"/>
            </el-table>
            <el-pagination
                class="mt-2"
                background
                layout="prev, pager, next"
                :total="pagination.total"
                :page-size="pagination.per_page"
                :current-page.sync="pagination.current_page"
                @current-change="handlePageChange">
            </el-pagination>
            <!-- <div class="mt-2">
                <b>Saldo inicial:</b> {{ saldo_inicial }}<br>
                <b>Saldo final:</b> {{ saldo_final }}
            </div> -->
        </div>
        <div v-if="!previewLoading && preview.length === 0 && canExport" class="alert alert-info mt-3">
            No hay movimientos para los filtros seleccionados.
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
            preview: [],
            saldo_inicial: '0,00',
            saldo_final: '0,00',
            debito_total: '0,00',
            credito_total: '0,00',
            previewLoading: false,
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1,
                last_page: 1,
            }
        }
    },
    watch: {
        'form.month': function() {
            this.pagination.current_page = 1;
            this.fetchPreview();
        },
        'form.bank_account_id': function() {
            this.pagination.current_page = 1;
            this.fetchPreview();
        },
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
        this.fetchPreview()
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
        async fetchPreview() {
            if (!this.canExport) {
                this.preview = []
                this.saldo_inicial = '0,00'
                this.saldo_final = '0,00'
                this.debito_total = '0,00'
                this.credito_total = '0,00'
                this.pagination = { total: 0, per_page: 10, current_page: 1, last_page: 1 }
                return
            }
            this.previewLoading = true
            try {
                const params = { ...this.form, page: this.pagination.current_page, per_page: this.pagination.per_page }
                const response = await this.$http.get(`/accounting/bank-book/preview?${queryString.stringify(params)}`)
                this.preview = response.data.preview
                this.saldo_inicial = response.data.saldo_inicial
                this.saldo_final = response.data.saldo_final
                this.debito_total = response.data.total_debito
                this.credito_total = response.data.total_credito
                this.pagination = response.data.pagination
            } catch (e) {
                this.preview = []
                this.saldo_inicial = '0,00'
                this.saldo_final = '0,00'
                this.debito_total = '0,00'
                this.credito_total = '0,00'
                this.pagination = { total: 0, per_page: 10, current_page: 1, last_page: 1 }
            }
            this.previewLoading = false
        },
        handlePageChange(page) {
            this.pagination.current_page = page;
            this.fetchPreview();
        }
    }
}
</script>
<style>
.table-sm tr td {
    border: none !important;
    /* background: #f5f7fa !important; */
    vertical-align: middle;
}
</style>