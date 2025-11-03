<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <span>Conciliaciones Bancarias</span>
            </h2>
            <div class="right-wrapper pull-right">
                <button class="btn btn-custom btn-sm mt-2 mr-2" @click="openDialog">
                    <i class="fa fa-plus-circle"></i> Crear conciliación bancaria
                </button>
            </div>
        </div>
        <div class="card mb-0 pt-2 pt-md-0">
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Fecha de Creación</label>
                        <el-date-picker
                            v-model="filters.daterange"
                            type="daterange"
                            range-separator="a"
                            start-placeholder="Desde"
                            end-placeholder="Hasta"
                            value-format="yyyy-MM-dd"
                            @change="onFilterChange"
                            style="width: 100%;"
                        ></el-date-picker>
                    </div>
                    <div class="col-md-3">
                        <label>Mes</label>
                        <el-date-picker
                            v-model="filters.month"
                            type="month"
                            placeholder="Seleccionar mes"
                            value-format="yyyy-MM"
                            @change="onFilterChange"
                            style="width: 100%;"
                        ></el-date-picker>
                    </div>
                    <div class="col-md-4">
                        <label>Cuenta Bancaria</label>
                        <el-select
                            v-model="filters.bank_account_id"
                            filterable
                            clearable
                            placeholder="Seleccione"
                            @change="onFilterChange"
                            style="width: 100%;"
                        >
                            <el-option
                                v-for="acc in bankAccounts"
                                :key="acc.id"
                                :label="acc.description + ' - ' + acc.number"
                                :value="acc.id"
                            ></el-option>
                        </el-select>
                    </div>
                </div>
                <!-- Tabla -->
                <data-table :resource="resource" ref="dataTable" :applyFilter="false" :customFilters="customFilters">
                    <tr slot="heading">
                        <th>Fecha de Creación</th>
                        <th>Mes de Conciliación</th>
                        <th>Cuenta Bancaria</th>
                        <th>Acciones</th>
                    </tr>
                    <tr slot-scope="{ row }">
                        <td>{{ row.date | dateFormat }}</td>
                        <td>{{ row.month }}</td>
                        <td>
                            <span v-if="row.bank_account">
                                {{ row.bank_account.description }}<br>
                                <small>{{ row.bank_account.number }}</small>
                            </span>
                        </td>
                        <td>
                            <div class="d-inline-block">
                                <el-button
                                    v-if="row.status === 'draft'"
                                    size="mini"
                                    @click="editarConciliacion(row)"
                                >
                                    Editar
                                </el-button>
                            </div>
                            <div class="d-inline-block ml-1">
                                <el-button
                                    size="mini"
                                    type="info"
                                    @click="verPdf(row)"
                                >
                                    PDF
                                </el-button>
                            </div>
                            <div class="d-inline-block ml-1">
                                <el-button
                                    size="mini"
                                    type="success"
                                    @click="exportarExcelFila(row)"
                                >
                                    Excel
                                </el-button>
                            </div>
                            <div class="d-inline-block ml-1">
                                <el-button
                                    size="mini"
                                    type="danger"
                                    @click="eliminarConciliacion(row)"
                                >
                                    Eliminar
                                </el-button>
                            </div>
                        </td>
                    </tr>
                </data-table>
            </div>
        </div>
        <bank-reconciliation-form
            ref="bankReconciliationForm"
            :showDialog.sync="showDialog"
            :bankAccounts="bankAccounts"
            @save="handleSave"
            @refreshTable="getRecords"
        ></bank-reconciliation-form>
    </div>
</template>

<script>
import BankReconciliationForm from "./form.vue";
import DataTable from "../components/DataTable.vue";
import moment from "moment";

export default {
    components: { DataTable, BankReconciliationForm },
    data() {
        return {
            resource: "accounting/bank-reconciliation",
            filters: {
                daterange: null,
                month: null,
                bank_account_id: null,
            },
            bankAccounts: [],
            showDialog: false,
        };
    },
    computed: {
        customFilters() {
            let filters = {};
            // Rango de fechas
            if (this.filters.daterange && this.filters.daterange.length === 2) {
                filters.column = 'daterange';
                filters.value = this.filters.daterange.join('_');
            }
            // Mes
            filters.month = this.filters.month || '';
            // Cuenta bancaria
            filters.bank_account_id = this.filters.bank_account_id || '';
            return filters;
        }
    },
    filters: {
        dateFormat(value) {
            if (!value) return "";
            return moment(value).format("YYYY-MM-DD");
        }
    },
    methods: {
        exportarExcelFila(row) {
            if (!row.month || !row.bank_account_id) {
                this.$message.warning('No se puede exportar: faltan datos de mes o cuenta bancaria.');
                return;
            }
            const query = `month=${encodeURIComponent(row.month)}&bank_account_id=${encodeURIComponent(row.bank_account_id)}`;
            window.open(`/accounting/bank-reconciliation/export-excel?${query}`, '_blank');
        },
        eliminarConciliacion(row) {
            this.$confirm('¿Desea eliminar la conciliación y todos sus registros?', 'Eliminar', {
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
                type: 'warning'
            }).then(async () => {
                try {
                    const res = await this.$http.delete(`/accounting/bank-reconciliation/${row.id}`);
                    if (res.data.success) {
                        this.$message.success(res.data.message);
                        this.getRecords();
                    } else {
                        this.$message.error(res.data.message);
                    }
                } catch (e) {
                    this.$message.error('Error al eliminar la conciliación.');
                }
            }).catch(() => {});
        },
        async onFilterChange() {
            await this.$nextTick();
            this.getRecords();
        },
        async editarConciliacion(row) {
            const res = await this.$http.get(`/accounting/bank-reconciliation/${row.id}/edit`);
            this.showDialog = true;
            this.$nextTick(() => {
                if (this.$refs.bankReconciliationForm && this.$refs.bankReconciliationForm.cargarConciliacion) {
                    this.$refs.bankReconciliationForm.cargarConciliacion(res.data);
                }
            });
        },
        openDialog() {
            this.showDialog = true;
        },
        handleSave(form) {
            // Aquí iría la lógica para guardar en backend.
            // Por ahora solo cierra el modal y refresca la tabla.
            this.showDialog = false;
            this.getRecords();
        },
        async getBankAccounts() {
            const res = await this.$http.get('/accounting/bank-reconciliation/bank-accounts');
            this.bankAccounts = res.data;
        },
        getRecords() {
            this.$refs.dataTable.getRecords();
        },
        verPdf(row) {
            window.open(`/accounting/bank-reconciliation/pdf/${row.id}`, '_blank');
        },
    },
    async mounted() {
        await this.getBankAccounts();
    }
};
</script>