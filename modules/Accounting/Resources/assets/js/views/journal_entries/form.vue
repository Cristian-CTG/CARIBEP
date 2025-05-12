<template>
    <div>
    <el-dialog :title="titleDialog" :visible="showDialog" @close="close" @open="create" width="65%">
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
                                <el-option v-for="prefix in prefixes" :key="prefix.id" :label="prefix.description+ ' - ' + prefix.prefix"
                                    :value="prefix.id"></el-option>
                            </el-select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label">Descripción</label>
                    <el-input v-model="form.description"></el-input>
                </div>

                <div>
                    <h4>Detalles del Asiento</h4>
                    <el-table :data="form.details" border show-summary :summary-method="getSummaries">
                        <el-table-column prop="account_id" label="Cuenta Contable">
                            <template slot-scope="{ row }">
                                <el-select v-model="row.chart_of_account_id	" placeholder="Seleccionar cuenta">
                                    <el-option v-for="account in accounts" :key="account.id" :label="account.code + ' - ' + account.name" :value="account.id"></el-option>
                                </el-select>
                            </template>
                        </el-table-column>

                        <el-table-column prop="debit" label="Débito">
                            <template slot-scope="{ row }">
                                <el-input type="number" 
                                    v-model="row.debit"
                                    :disabled="row.credit > 0"
                                    step="0.01"
                                ></el-input>
                            </template>
                        </el-table-column>

                        <el-table-column prop="credit" label="Crédito">
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
    props: ["showDialog", "recordId"],
    data() {
        return {
            loading_submit: false,
            titleDialog: null,
            resource: "accounting/journal/entries",
            form: {},
            prefixes: [],
            accounts: [],
            showDialogPrefix: false,
        };
    },
    created() {
        this.initForm();
        this.loadPrefixes();
        this.loadAccounts();
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
        async loadPrefixes() {
            await this.$http.get("/accounting/journal/prefixes").then((response) => {
                this.prefixes = response.data;
            });
        },
        async loadAccounts() {
            await this.$http.get("/accounting/charts/records?column=level&value=4").then((response) => {
                this.accounts = response.data.data;
            });
        },
        addDetail() {
            this.form.details.push({ chart_of_account_id: null, debit: 0, credit: 0 });
        },
        removeDetail(index) {
            this.form.details.splice(index, 1);
        },
        create() {
            this.titleDialog = this.recordId ? "Editar Asiento" : "Nuevo Asiento";
            if (this.recordId) {
                this.$http.get(`/${this.resource}/${this.recordId}`).then((response) => {
                    this.form = response.data.data;
                });
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
