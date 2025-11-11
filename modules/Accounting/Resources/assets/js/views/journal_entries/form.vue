<template>
    <div>
    <el-dialog :title="titleDialog" :visible="showDialog" @close="close" @open="create" width="65%" :close-on-click-modal="false">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">Fecha</label>
                            <el-date-picker v-model="form.date" type="date" value-format="yyyy-MM-dd" format="yyyy-MM-dd"></el-date-picker>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">
                                Prefijo
                                <a href="#" @click.prevent="clickAddPrefix">[+ Nuevo]</a>
                            </label>
                            <el-select v-model="form.journal_prefix_id" placeholder="Seleccionar">
                                <el-option v-for="prefix in filteredJournalPrefixes" :key="prefix.id" :label="prefix.description+ ' - ' + prefix.prefix"
                                    :value="prefix.id"></el-option>
                            </el-select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label">Descripción</label>
                    <el-input v-model="form.description"></el-input>
                </div>

                <div class="mb-2">
                    <el-checkbox v-model="showBankAndPaymentColumn">
                        Agregar referencias de banco y método de pago
                    </el-checkbox>
                    <span class="text-muted ml-2">
                        (Recomendado para el libro de bancos)
                    </span>
                </div>

                <div>
                    <h4>Detalles del Asiento</h4>
                    <el-table :data="form.details" border show-summary :summary-method="getSummaries">
                        <el-table-column prop="account_id" >
                            <template slot="header">
                                Cuenta Contable <span class="text-danger">*</span>
                            </template>
                            <template slot-scope="{ row }">
                                <el-select v-model="row.chart_of_account_id"
                                    placeholder="Seleccionar cuenta"
                                    filterable
                                    clearable
                                    remote
                                    :remote-method="loadAccounts"
                                    :loading="loadingAccounts">
                                    <el-option v-for="account in accounts" :key="account.id" :label="account.code + ' - ' + account.name" :value="account.id"></el-option>
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column prop="third_party_type" >
                            <template slot="header">
                                Tipo de Tercer Implicado <span class='text-danger'>*</span>
                            </template>
                            <template slot-scope="{ row }">
                                <el-select v-model="row.third_party_type" placeholder="Tipo" style="width: 100%;" @change="onThirdPartyTypeChange(row)">
                                    <el-option label="Cliente" value="customers"></el-option>
                                    <el-option label="Proveedor" value="suppliers"></el-option>
                                    <el-option label="Empleado" value="employee"></el-option>
                                    <el-option label="Vendedor" value="seller"></el-option>
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column prop="third_party_id" >
                            <template slot="header">
                                Tercer Implicado <span class='text-danger'>*</span>
                            </template>
                            <template slot-scope="{ row }">
                                <el-select
                                    v-model="row.third_party_id"
                                    filterable
                                    remote
                                    clearable
                                    placeholder="Buscar tercero"
                                    :remote-method="query => fetchThirdParties(row, query)"
                                    :loading="row.loadingThirdParties"
                                    @scroll.native="onThirdPartyScroll(row)"
                                    style="width: 100%;"
                                >
                                    <el-option
                                        v-for="item in row.thirdParties"
                                        :key="item.id"
                                        :label="item.name"
                                        :value="item.id"
                                    ></el-option>
                                </el-select>
                            </template>
                        </el-table-column>

                        <el-table-column v-if="showBankAndPaymentColumn" prop="bank_account_id" label="Banco/Caja">
                            <template slot-scope="{ row }">
                                <el-select v-model="row.bank_account_id" placeholder="Banco/Caja" clearable filterable>
                                    <el-option v-for="bank in banks" :key="bank.id" :label="bank.description + (bank.number ? ' - ' + bank.number : '')" :value="bank.id"></el-option>
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column v-if="showBankAndPaymentColumn" prop="payment_method_name" label="Método de Pago">
                            <template slot-scope="{ row }">
                                <el-select v-model="row.payment_method_name" placeholder="Método de Pago" clearable filterable>
                                    <el-option v-for="method in paymentMethods" :key="method.id" :label="method.name" :value="method.name"></el-option>
                                </el-select>
                            </template>
                        </el-table-column>

                        <el-table-column prop="debit" >
                            <template slot="header">
                                Débito <span class='text-danger'>*</span>
                            </template>
                            <template slot-scope="{ row }">
                                <el-input type="number"
                                    v-model="row.debit"
                                    :disabled="row.credit > 0"
                                    step="0.01"
                                ></el-input>
                            </template>
                        </el-table-column>

                        <el-table-column prop="credit" >
                            <template slot="header">
                                Crédito <span class='text-danger'>*</span>
                            </template>
                            <template slot-scope="{ row }">
                                <el-input type="number"
                                    v-model="row.credit"
                                    :disabled="row.debit > 0"
                                    step="0.01"
                                ></el-input>
                            </template>
                        </el-table-column>

                        <el-table-column label="Acciones">
                            <template slot-scope="{ row, $index }">
                                <el-button type="danger" @click="removeDetail($index)">Eliminar</el-button>
                            </template>
                        </el-table-column>
                    </el-table>

                    <el-button type="success" @click="addDetail">Agregar Línea</el-button>
                </div>
            </div>

            <div class="form-actions text-right mt-4">
                <el-button @click.prevent="close()">Cancelar</el-button>
                <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
            </div>
        </form>
    </el-dialog>

    <journal-entry-prefix
        :showDialog.sync="showDialogPrefix"
        >
    </journal-entry-prefix>

    </div>

</template>

<script>

import JournalEntryPrefix from "./partials/prefix.vue";

export default {
    components: { JournalEntryPrefix },
    props: ["showDialog", "recordId", "journalPrefixes"],
    data() {
        return {
            loading_submit: false,
            titleDialog: null,
            resource: "accounting/journal/entries",
            form: {},
            accounts: [],
            showDialogPrefix: false,
            loadingAccounts: false,
            thirdParties: [],
            loadingThirdParties: false,
            banks: [],
            paymentMethods: [],
            showBankColumn: false,
            showPaymentMethodColumn: false,
            showBankAndPaymentColumn: false,
        };
    },
    created() {
        this.initForm();
        // this.loadPrefixes();
        this.loadAccounts();
        this.loadBanks();
        this.loadPaymentMethods();
    },
    computed: {
        filteredJournalPrefixes() {
            if (!this.journalPrefixes || !Array.isArray(this.journalPrefixes)) {
                return [];
            }
            return this.journalPrefixes.filter(p => p && p.modifiable === 1);
        }
    },
    methods: {
        initForm() {
            this.form = {
                date: new Date(),
                journal_prefix_id: null,
                description: "",
                details: [],
            };
        },
        async fetchThirdParties(row, query) {
            row.loadingThirdParties = true;
            row.thirdPartyPage = 1;
            row.thirdParties = [];
            await this.loadThirdParties(row, query, 1);
            row.loadingThirdParties = false;
        },
        async loadThirdParties(row, query, page) {
            await this.$http.get('/accounting/journal/thirds/third-parties', {
                params: {
                    type: row.third_party_type,
                    search: query,
                    page: page,
                    per_page: 50
                }
            }).then(response => {
                if (page === 1) {
                    row.thirdParties = response.data.data;
                } else {
                    row.thirdParties = row.thirdParties.concat(response.data.data);
                }
                row.thirdPartyTotal = response.data.total;
                row.thirdPartyPage = page;
            });
        },
        async onThirdPartyScroll(row, event) {
            const el = event.target;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 10) {
                // Si hay más resultados, carga la siguiente página
                if (row.thirdParties.length < row.thirdPartyTotal) {
                    row.loadingThirdParties = true;
                    await this.loadThirdParties(row, '', row.thirdPartyPage + 1);
                    row.loadingThirdParties = false;
                }
            }
        },
        async loadBanks() {
            await this.$http.get('/accounting/bank-book/records')
                .then(response => {
                    this.banks = [
                        { id: 'cash', description: 'Caja General', number: '' },
                        ...response.data
                    ];
                });
        },
        async loadPaymentMethods() {
            await this.$http.get('/accounting/payment-methods') // Ajusta el endpoint si es diferente
                .then(response => this.paymentMethods = response.data);
        },
        async onThirdPartyTypeChange(row) {
            row.third_party_id = null;
            row.thirdParties = [];
            row.thirdPartyPage = 1;
            row.thirdPartyTotal = 0;
            row.loadingThirdParties = true;

            await this.loadThirdParties(row, '', 1);
            row.loadingThirdParties = false;
        },
        // async loadPrefixes() {
        //     await this.$http.get("/accounting/journal/prefixes").then((response) => {
        //         this.prefixes = response.data;
        //     });
        // },
        async loadAccounts(query = '') {
            this.loadingAccounts = true;
            try {
                const res = await this.$http.get('/accounting/journal/search-accounts', {
                    params: { search: query, limit: 50 }
                });
                this.accounts = res.data.data;
            } finally {
                this.loadingAccounts = false;
            }
        },
        addDetail() {
            this.form.details.push({
                chart_of_account_id: null,
                debit: 0,
                credit: 0,
                third_party_type: null,
                third_party_id: null,
                thirdParties: [],
                loadingThirdParties: false,
                thirdPartyPage: 1,
                thirdPartyTotal: 0,
                bank_account_id: null,
                payment_method_name: null
            });
        },
        removeDetail(index) {
            this.form.details.splice(index, 1);
        },
        async create() {
            this.titleDialog = this.recordId ? "Editar Asiento" : "Nuevo Asiento";

            if (this.recordId) {
                const response = await this.$http.get(`/${this.resource}/${this.recordId}`);
                this.form = response.data.data;

                let hasBankOrPayment = false;

                for (const row of this.form.details) {
                    this.$set(row, 'thirdParties', []);
                    this.$set(row, 'loadingThirdParties', false);
                    this.$set(row, 'thirdPartyPage', 1);
                    this.$set(row, 'thirdPartyTotal', 0);

                    // Precarga la cuenta contable
                    if (row.chart_of_account_id && row.chart_of_account_label) {
                        const accountExists = this.accounts.some(a => a.id === row.chart_of_account_id);
                        if (!accountExists) {
                            this.accounts.push({
                                id: row.chart_of_account_id,
                                code: row.chart_of_account_label.split(' - ')[0],
                                name: row.chart_of_account_label.split(' - ')[1],
                            });
                        }
                    }

                    // Precarga el tercero actual
                    if (row.third_party_id && row.third_party_label) {
                        row.thirdParties.push({
                            id: row.third_party_id,
                            name: row.third_party_label,
                        });
                    }

                    // NUEVO: carga la lista completa del tipo existente
                    if (row.third_party_type) {
                        try {
                            const res = await this.$http.get('/accounting/journal/thirds/third-parties', {
                                params: {
                                    type: row.third_party_type,
                                    page: 1,
                                    per_page: 50,
                                },
                            });
                            // Mezcla el tercero actual con el listado
                            const fetched = res.data.data || [];
                            const existingIds = row.thirdParties.map(t => t.id);
                            const merged = [
                                ...row.thirdParties,
                                ...fetched.filter(t => !existingIds.includes(t.id))
                            ];
                            row.thirdParties = merged;
                            row.thirdPartyTotal = res.data.total;
                        } catch (err) {
                            console.error("Error cargando terceros:", err);
                        }
                    }

                    // Detectar si hay banco o método de pago
                    if (row.bank_account_id || row.payment_method_name) {
                        hasBankOrPayment = true;
                    }
                }

                this.showBankAndPaymentColumn = hasBankOrPayment;
            } else {
                this.initForm();
            }
        },
        getSummaries({ columns, data }) {
            const sums = ['TOTAL'];
            let totalDebit = 0;
            let totalCredit = 0;

            data.forEach(item => {
                totalDebit += Number(item.debit) || 0;
                totalCredit += Number(item.credit) || 0;
            });

            columns.forEach((column, index) => {
                if (column.property === 'debit') {
                    sums[index] = totalDebit.toFixed(2);
                } else if (column.property === 'credit') {
                    sums[index] = totalCredit.toFixed(2);
                } else if (index !== 0) {
                    sums[index] = '';
                }
            });

            return sums;
        },
        async submit() {


            if (!this.form.journal_prefix_id) {
                this.$message.error("El prefijo es requerido.");
                return;
            }

            if (!this.form.description || this.form.description.trim() === "") {
                this.$message.error("La descripción es requerida.");
                return;
            }

            if (this.form.details.length === 0) {
                this.$message.error("El asiento contable debe tener al menos una línea.");
                return;
            }

            // Validar que el asiento contable tenga al menos una línea
            if (this.form.details.length === 0) {
                this.$message.error("El asiento contable debe tener al menos una línea.");
                return;
            }

            // Validar cada línea del asiento contable
            for (const [index, detail] of this.form.details.entries()) {
                if (!detail.chart_of_account_id) {
                    this.$message.error(`La cuenta contable es requerida en la línea ${index + 1}.`);
                    return;
                }
                if (!detail.third_party_id) {
                    this.$message.error(`El tercer implicado es obligatorio en la línea ${index + 1}.`);
                    return;
                }
                if (this.showBankAndPaymentColumn) {
                    const bancoCaja = detail.bank_account_id;
                    const metodoPago = detail.payment_method_name;
                    if ((bancoCaja && !metodoPago) || (!bancoCaja && metodoPago)) {
                        this.$message.error(`Solo si eligió un banco/caja debe agregar un método de pago en la línea ${index + 1}.`);
                        return;
                    }
                }

                if (Number(detail.debit) === 0 && Number(detail.credit) === 0) {
                    this.$message.error(`Al menos uno de los valores (débito o crédito) debe ser mayor a cero en la línea ${index + 1}.`);
                    return;
                }
            }

            // Validar que los débitos y créditos sean iguales
            const totalDebit = this.form.details.reduce((sum, detail) => sum + Number(detail.debit), 0);
            const totalCredit = this.form.details.reduce((sum, detail) => sum + Number(detail.credit), 0);

            if (totalDebit !== totalCredit) {
                this.$message.error("Los débitos y créditos deben ser iguales.");
                return;
            }
            this.loading_submit = true;
            // Si el checkbox está desactivado, limpia los campos de banco/caja y método de pago
            if (!this.showBankAndPaymentColumn) {
                this.form.details = this.form.details.map(detail => ({
                    ...detail,
                    bank_account_id: null,
                    payment_method_name: null,
                    debit: Number(detail.debit) || 0,
                    credit: Number(detail.credit) || 0
                }));
            } else {
                this.form.details = this.form.details.map(detail => {
                    let bank_account_id = detail.bank_account_id;
                    if (bank_account_id === 'cash') {
                        bank_account_id = null;
                    }
                    return {
                        ...detail,
                        bank_account_id,
                        debit: Number(detail.debit) || 0,
                        credit: Number(detail.credit) || 0
                    };
                });
            }

            let payload = {
                ...this.form,
                date: moment(this.form.date, 'YYYY-MM-DD hh:mm:ss').format('YYYY-MM-DD'),
            };
            const method = this.recordId ? "put" : "post";
            const url = this.recordId ? `/${this.resource}/${this.recordId}` : `/${this.resource}`;
            await this.$http[method](url, payload)
                .then((response) => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this.close();
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .finally(() => (this.loading_submit = false));
        },
        close() {
            this.$eventHub.$emit("reloadData");
            this.$emit("update:showDialog", false);
            this.initForm();
        },
        clickAddPrefix() {
            this.showDialogPrefix = true;
        },
    },
};
</script>
