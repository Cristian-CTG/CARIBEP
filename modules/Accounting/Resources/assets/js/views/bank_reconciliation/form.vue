<template>
    <div>
        <el-dialog :visible="showDialog" title="Crear Conciliación Bancaria" width="75%" @close="close">
            <el-form :model="form" label-width="150px">
                <div class="row">
                    <div class="col-md-4">
                        <el-form-item >
                            <template #label>
                                Fecha <span style="color:red">*</span>
                            </template>
                            <el-date-picker v-model="form.date" type="date" value-format="yyyy-MM-dd" style="width:100%"></el-date-picker>
                        </el-form-item>
                    </div>
                    <div class="col-md-4">
                        <el-form-item >
                            <template #label>
                                Mes <span style="color:red">*</span>
                            </template>
                            <el-date-picker
                                v-model="form.month"
                                type="month"
                                value-format="yyyy-MM"
                                style="width:100%"
                                @change="() => onMonthOrAccountChange(1, true)"
                            ></el-date-picker>
                        </el-form-item>
                    </div>
                    <div class="col-md-4">
                        <el-form-item >
                            <template #label>
                                Cuenta Bancaria <span style="color:red">*</span>
                            </template>
                            <el-select
                                v-model="form.bank_account_id"
                                placeholder="Seleccione"
                                style="width:100%"
                                @change="() => onMonthOrAccountChange(1, true)"
                            >
                                <el-option
                                    v-for="acc in bankAccounts"
                                    :key="acc.id"
                                    :label="acc.description + ' - ' + acc.number"
                                    :value="acc.id"
                                ></el-option>
                            </el-select>
                        </el-form-item>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <el-form-item >
                            <template #label>
                                Saldo Libro <span style="color:red">*</span>
                            </template>
                            <el-input :value="saldo_libro" readonly></el-input>
                        </el-form-item>
                    </div>
                    <div class="col-md-4">
                        <el-form-item >
                            <template #label>
                                Saldo Extracto <span style="color:red">*</span>
                            </template>
                            <el-input v-model="form.saldo_extracto" @input="updateDiferencia"></el-input>
                        </el-form-item>
                    </div>
                    <div class="col-md-4">
                        <el-form-item label="Diferencia">
                            <el-input v-model="form.diferencia" ></el-input>
                        </el-form-item>
                    </div>
                </div>
            </el-form>

            <div class="row mb-2">
                <div class="col-md-4">
                    <el-alert
                        :title="'Saldo Inicial: ' + saldo_inicial"
                        type="info"
                        show-icon
                        :closable="false"
                    ></el-alert>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4">
                    <el-alert
                        v-if="movimientos.length > 0"
                        title="Seleccione los registros que aparecen en el extracto bancario. Los registros no seleccionados se considerarán pendientes a conciliar."
                        type="warning"
                        show-icon
                        :closable="false"
                    ></el-alert>
                </div>
            </div>
            
            <el-table
                :data="movimientos"
                ref="movimientosTable"
                style="width: 100%; margin-bottom: 15px;"
                @select="handleRowSelect"
                @select-all="handleSelectAll"
                :row-key="getRowKey"
                border
                show-summary
                :summary-method="getSummary"
            >
                <el-table-column type="selection" width="55"></el-table-column>
                <el-table-column prop="documento" label="Documento" width="120"></el-table-column>
                <el-table-column prop="concepto" label="Concepto"></el-table-column>
                <el-table-column prop="documento_tercero" label="CC/NIT" width="120"></el-table-column>
                <el-table-column prop="nombre_tipo_tercero" label="Tercero"></el-table-column>
                <!-- <el-table-column label="N. de Cheque" width="120">
                    <template slot-scope="scope">
                        <el-input v-model="scope.row.numero_cheque" placeholder="N° Cheque"></el-input>
                    </template>
                </el-table-column> -->
                <el-table-column prop="debit" label="Débito" width="100"></el-table-column>
                <el-table-column prop="credit" label="Crédito" width="100"></el-table-column>
            </el-table>
            <el-pagination
                background
                layout="total, prev, pager, next"
                :total="movimientosPagination.total"
                :page-size="movimientosPagination.per_page"
                :current-page="movimientosPagination.current_page"
                @current-change="handleMovimientosPageChange"
                style="margin-bottom: 15px;"
            ></el-pagination>
            <div class="mb-2">
                <strong>Seleccionados en extracto:</strong>
                {{ selectedMovimientosIds.length }}
                <!-- <br>
                <small>
                    IDs seleccionados: [
                    {{ selectedMovimientosIds.join(', ') }}
                    ]
                </small>
                <br>
                <small>
                    IDs página actual: [
                    {{ movimientos.map(getRowKey).join(', ') }}
                    ]
                </small> -->
            </div>

            <div class="row mb-2">
                <div class="col-md-12">
                    <el-alert
                        :title="diferenciaSeleccionados"
                        type="success"
                        show-icon
                        :closable="false"
                    ></el-alert>
                </div>
            </div>
            
            <span slot="footer" class="dialog-footer">
                <el-button @click="close">Cancelar</el-button>
                <el-button type="primary" @click="submitForm">Ajustar Pendientes</el-button>
            </span>
        </el-dialog>
        <form-reconciliation
            ref="formReconciliation"
            :showDialog.sync="showDialogReconciliation"
            :saldo_libro="datosConciliacion.saldo_libro || ''"
            :saldo_extracto="datosConciliacion.saldo_extracto || ''"
            :diferencia_inicial="datosConciliacion.diferencia_inicial || ''"
            :movimientos="datosConciliacion.movimientos || []"
            :movimientosSeleccionados="datosConciliacion.movimientos_seleccionados || []"
            :detalles_mas="datosConciliacion.detalles_mas || []"
            :detalles_menos="datosConciliacion.detalles_menos || []"
            @closeAll="closeAllModals"
            @refreshTable="refreshTable"
        />
    </div>
</template>

<script>
import formReconciliation from "./partials/formReconciliation.vue";
import moment from "moment";
export default {
    components: { formReconciliation },
    props: {
        showDialog: { type: Boolean, required: true },
        bankAccounts: { type: Array, default: () => [] }
    },
    data() {
        return {
            form: {
                date: moment().format('YYYY-MM-DD'),
                month: '',
                bank_account_id: null,
                saldo_extracto: '',
                diferencia: '',
            },
            diferencia_inicial: '',
            saldo_inicial: '',
            saldo_final: '',
            saldo_libro: '',
            total_debit: 0,
            total_credit: 0,
            movimientos: [],
            movimientosPagination: {
                total: 0,
                per_page: 15,
                current_page: 1,
                last_page: 1,
            },
            showDialogReconciliation: false,
            datosConciliacion: {
                saldo_libro: '',
                saldo_extracto: '',
                diferencia_inicial: '',
                movimientos: [],
                movimientos_seleccionados: [],
                detalles_mas: [],
                detalles_menos: [],
            },
            loadingMovimientos: false,
            selectedMovimientosIds: [], // <-- Solo IDs seleccionados
            selectedMovimientos: [],
        }
    },
    computed: {
        diferenciaSeleccionados() {
            let totalDebito = 0;
            let totalCredito = 0;
            this.selectedMovimientos.forEach(mov => {
                totalDebito += parseFloat((mov.debit + '').replace(/\./g, '').replace(',', '.')) || 0;
                totalCredito += parseFloat((mov.credit + '').replace(/\./g, '').replace(',', '.')) || 0;
            });
            return `Total seleccionados - Débito: ${totalDebito.toLocaleString('es-CO', { minimumFractionDigits: 2 })} | Crédito: ${totalCredito.toLocaleString('es-CO', { minimumFractionDigits: 2 })} | Diferencia: ${(totalDebito - totalCredito).toLocaleString('es-CO', { minimumFractionDigits: 2 })}`;
        },
    },
    watch: {
        showDialog(val) {
            if (!val) this.resetForm();
        }
    },
    methods: {
        closeAllModals() {
            this.showDialogReconciliation = false;
            this.$emit('update:showDialog', false); // Si el padre controla el showDialog principal
        },
        refreshTable() {
            this.$emit('refreshTable');
        },
        async cargarConciliacion(data) { // EN PROCESO, NO SE USA AUN
            // 1. Carga los datos principales
            this.form = {
                date: data.date,
                month: data.month,
                bank_account_id: data.bank_account_id,
                saldo_extracto: data.saldo_extracto,
                diferencia: '', // se recalcula luego
            };
            this.selectedMovimientos = []; // Se llenará después
            this.selectedMovimientosIds = [];

            // 2. Obtén el saldo de libro automáticamente
            await this.fetchSaldosLibro();

            // 3. Obtén todos los movimientos del mes y cuenta
            const res = await this.$http.get('/accounting/bank-reconciliation/movements', {
                params: {
                    month: this.form.month,
                    bank_account_id: this.form.bank_account_id,
                    page: 1,
                    per_page: 10000 // trae todos
                }
            });
            const movimientos = (res.data.movements || []).map(mov => ({
                id: Number(mov.id),
                date: mov.date,
                documento: mov.document,
                concepto: mov.description,
                documento_tercero: mov.third_party_document || '',
                nombre_tipo_tercero: (
                    (mov.third_party_name ? mov.third_party_name : '') +
                    (mov.third_party_type ? ' (' + mov.third_party_type + ')' : '')
                ),
                nombre_tercero: (
                    (mov.third_party_document ? mov.third_party_document + ' - ' : '') +
                    (mov.third_party_name ? mov.third_party_name : '') +
                    (mov.third_party_type ? ' (' + mov.third_party_type + ')' : '')
                ),
                numero_cheque: mov.cheque_number || '',
                debit: mov.debit,
                credit: mov.credit,
                n_soporte: mov.document || '',
            }));

            // 4. Marca como seleccionados los movimientos que están en detalles_mas y detalles_menos
            const detallesSeleccionados = [
                ...(data.detalles_mas || []),
                ...(data.detalles_menos || [])
            ];
            this.selectedMovimientos = movimientos.filter(mov =>
                detallesSeleccionados.some(det => Number(det.journal_entry_detail_id) === Number(mov.id))
            );
            this.selectedMovimientosIds = this.selectedMovimientos.map(m => Number(m.id));

            // 5. Pasa todo al segundo form
            this.datosConciliacion = {
                saldo_libro: data.saldo_libro || this.saldo_libro,
                saldo_extracto: this.form.saldo_extracto,
                diferencia_inicial: data.diferencia_inicial || this.diferencia_inicial,
                movimientos: movimientos,
                movimientos_seleccionados: this.selectedMovimientos,
                detalles_mas: data.detalles_mas || [],
                detalles_menos: data.detalles_menos || [],
            };

            // 6. Calcula la diferencia correctamente
            this.updateDiferencia();

            // 7. Refresca las tablas del segundo form
            this.$nextTick(() => {
                if (this.$refs.formReconciliation && this.$refs.formReconciliation.procesarTablas) {
                    this.$refs.formReconciliation.procesarTablas();
                }
            });
        },
        parseNumber(val) {
            if (typeof val === 'number') return val;
            if (!val) return 0;
            // Si contiene ',' es formato europeo (300.000,00)
            if ((val + '').includes(',')) {
                return parseFloat((val + '').replace(/\./g, '').replace(',', '.')) || 0;
            }
            // Si no, asume formato estándar (300000.00)
            return parseFloat(val) || 0;
        },
        handleSave(form) {
            // Guarda los datos del form principal
            this.datosConciliacion = form;
            this.showDialogReconciliation = true;
            this.getRecords();
        },
        getSummary(param) {
            const { columns } = param;
            const sums = [];
            columns.forEach((column, index) => {
                if (index === 4) {
                    sums[index] = 'Total general';
                } else if (column.property === 'debit') {
                    const val = isNaN(this.total_debit) ? '0.00' : Number(this.total_debit).toLocaleString('es-CO', { minimumFractionDigits: 2 });
                    sums[index] = val;
                } else if (column.property === 'credit') {
                    const val = isNaN(this.total_credit) ? '0.00' : Number(this.total_credit).toLocaleString('es-CO', { minimumFractionDigits: 2 });
                    sums[index] = val;
                } else {
                    sums[index] = '';
                }
            });
            return sums;
        },
        handleRowSelect(selection, row) {
            const id = Number(row.id);
            // Si se seleccionó
            if (selection.some(r => Number(r.id) === id)) {
                if (!this.selectedMovimientos.some(m => Number(m.id) === id)) {
                    this.selectedMovimientos.push({ ...row });
                }
            } else {
                this.selectedMovimientos = this.selectedMovimientos.filter(m => Number(m.id) !== id);
            }
            // Mantén los IDs sincronizados si los usas en otros métodos
            this.selectedMovimientosIds = this.selectedMovimientos.map(m => Number(m.id));
        },
        async fetchSaldosLibro() {
            if (!this.form.month || !this.form.bank_account_id) {
                this.saldo_inicial = '';
                this.saldo_final = '';
                this.saldo_libro = '';
                return;
            }
            const res = await this.$http.get('/accounting/bank-book/preview', {
                params: {
                    month: this.form.month,
                    bank_account_id: this.form.bank_account_id
                }
            });
            this.saldo_inicial = res.data.saldo_inicial;
            this.saldo_final = res.data.saldo_final;
            this.saldo_libro = res.data.saldo_final; // Puedes ajustar según tu lógica
        },
        handleSelectAll(selection) {
            const pageIds = this.movimientos.map(m => Number(m.id));
            // Agrega los seleccionados de la página actual
            selection.forEach(row => {
                if (!this.selectedMovimientos.some(m => Number(m.id) === Number(row.id))) {
                    this.selectedMovimientos.push({ ...row });
                }
            });
            // Quita los deseleccionados de la página actual
            this.selectedMovimientos = this.selectedMovimientos.filter(
                m => !pageIds.includes(Number(m.id)) || selection.some(row => Number(row.id) === Number(m.id))
            );
            this.selectedMovimientosIds = this.selectedMovimientos.map(m => Number(m.id));
        },
        handleMovimientosPageChange(page) {
            this.movimientosPagination.current_page = page;
            this.onMonthOrAccountChange(page, false);
        },
        getRowKey(row) {
            return row.id;
        },
        restoreSelection() {
            this.$nextTick(() => {
                if (this.$refs.movimientosTable) {
                    this.$refs.movimientosTable.clearSelection();
                    this.movimientos.forEach(row => {
                        if (this.selectedMovimientos.some(m => Number(m.id) === Number(row.id))) {
                            this.$refs.movimientosTable.toggleRowSelection(row, true);
                        }
                    });
                }
            });
        },
        async onMonthOrAccountChange(page = 1, resetSelection = false) {
            await this.fetchSaldosLibro();
            if (resetSelection) {
                this.selectedMovimientosIds = [];
                this.selectedMovimientos = [];
            }
            if (page === undefined || page === null) page = 1;
            if (page === 1) this.movimientosPagination.current_page = 1;
            if (this.form.month && this.form.bank_account_id) {
                const res = await this.$http.get('/accounting/bank-reconciliation/movements', {
                    params: {
                        month: this.form.month,
                        bank_account_id: this.form.bank_account_id,
                        page: page,
                        per_page: this.movimientosPagination.per_page
                    }
                });

                this.movimientos = (res.data.movements || []).map(mov => ({
                    id: Number(mov.id),
                    date: mov.date,
                    documento: mov.document,
                    concepto: mov.description,
                    documento_tercero: mov.third_party_document || '',
                    nombre_tipo_tercero: (
                        (mov.third_party_name ? mov.third_party_name : '') +
                        (mov.third_party_type ? ' (' + mov.third_party_type + ')' : '')
                    ),
                    nombre_tercero: (
                        (mov.third_party_document ? mov.third_party_document + ' - ' : '') +
                        (mov.third_party_name ? mov.third_party_name : '') +
                        (mov.third_party_type ? ' (' + mov.third_party_type + ')' : '')
                    ),
                    numero_cheque: mov.cheque_number || '',
                    debit: mov.debit,
                    credit: mov.credit,
                    n_soporte: mov.document || '',
                }));

                this.movimientosPagination = res.data.pagination || {
                    total: 0,
                    per_page: 15,
                    current_page: 1,
                    last_page: 1,
                };
                // Guarda los totales globales
                this.total_debit = res.data.totals ? Number(res.data.totals.debit) : 0;
                this.total_credit = res.data.totals ? Number(res.data.totals.credit) : 0;
                this.loadingMovimientos = false;
            } else {
                this.movimientos = [];
                this.movimientosPagination = {
                    total: 0,
                    per_page: 15,
                    current_page: 1,
                    last_page: 1,
                };
                this.total_debit = 0;
                this.total_credit = 0;
            }
            this.restoreSelection();
        },
        updateDiferencia() {
            const libro = this.parseNumber(this.saldo_libro);
            const extracto = this.parseNumber(this.form.saldo_extracto);
            const diferencia = libro - extracto;
            this.form.diferencia = diferencia.toLocaleString('es-CO', { minimumFractionDigits: 2 });
            this.diferencia_inicial = diferencia;
        },
        async submitForm() {
            if (!this.form.date) {
                this.$message.error('Debe ingresar la Fecha de Creación.');
                return;
            }
            if (!this.form.month) {
                this.$message.error('Debe seleccionar el Mes de Conciliación.');
                return;
            }
            if (!this.form.bank_account_id) {
                this.$message.error('Debe seleccionar la Cuenta Bancaria.');
                return;
            }
            if (!this.saldo_libro || Number(this.saldo_libro) == 0) {
                this.$message.error('El Saldo de Libro no puede ser 0 ni estar vacío.');
                return;
            }
            if (!this.form.saldo_extracto || Number(this.form.saldo_extracto) == 0) {
                this.$message.error('El Saldo de Extracto no puede ser 0 ni estar vacío.');
                return;
            }
            // 1. Solicita todos los movimientos SIN paginación
            const res = await this.$http.get('/accounting/bank-reconciliation/movements', {
                params: {
                    month: this.form.month,
                    bank_account_id: this.form.bank_account_id,
                    page: 1,
                    per_page: 10000 // un número suficientemente grande para traer todos
                }
            });

            const todosLosMovimientos = (res.data.movements || []).map(mov => ({
                id: Number(mov.id),
                date: mov.date,
                documento: mov.document,
                concepto: mov.description,
                documento_tercero: mov.third_party_document || '',
                nombre_tipo_tercero: (
                    (mov.third_party_name ? mov.third_party_name : '') +
                    (mov.third_party_type ? ' (' + mov.third_party_type + ')' : '')
                ),
                nombre_tercero: (
                    (mov.third_party_document ? mov.third_party_document + ' - ' : '') +
                    (mov.third_party_name ? mov.third_party_name : '') +
                    (mov.third_party_type ? ' (' + mov.third_party_type + ')' : '')
                ),
                numero_cheque: mov.cheque_number || '',
                debit: mov.debit,
                credit: mov.credit,
                n_soporte: mov.document || '',
            }));

            // 2. Pasa todos los movimientos y los seleccionados al segundo form
            this.datosConciliacion = {
                saldo_libro: this.saldo_libro,
                saldo_extracto: this.form.saldo_extracto,
                diferencia_inicial: this.diferencia_inicial,
                movimientos: todosLosMovimientos,
                movimientos_seleccionados: this.selectedMovimientos
            };
            this.showDialogReconciliation = true;
        },
        close() {
            this.$emit('update:showDialog', false);
            this.resetForm();
        },
        resetForm() {
            this.form = {
                date: moment().format('YYYY-MM-DD'),
                month: '',
                bank_account_id: null,
                saldo_extracto: '',
                diferencia: '',
            };
            this.saldo_inicial = '';
            this.saldo_final = '';
            this.saldo_libro = '';
            this.movimientos = [];
            this.movimientosPagination = {
                total: 0,
                per_page: 15,
                current_page: 1,
                last_page: 1,
            };
            this.selectedMovimientosIds = [];
            this.selectedMovimientos = [];
        }
    }
}
</script>