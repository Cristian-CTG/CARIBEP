<template>
    <el-dialog :visible="showDialog" title="Pendientes a conciliar" width="75%" @close="close">
        <el-form label-width="150px" class="form-body">
            <el-card class="mb-3" shadow="never">
                <div class="row">
                    <div class="col-md-4">
                        <div><strong>Saldo Libro:</strong> {{ saldo_libro }}</div>
                    </div>
                    <div class="col-md-4">
                        <div><strong>Saldo Extracto:</strong> {{ saldo_extracto }}</div>
                    </div>
                    <div class="col-md-4">
                        <div><strong>Diferencia:</strong> {{ diferenciaFinal }}</div>
                    </div>
                </div>
            </el-card>
        </el-form>

        <!-- ENTRADAS (CREDITOS) -->
        <div class="section mb-4">
            <el-divider content-position="left">Entradas (creditos)</el-divider>
            <el-card class="mb-4" shadow="never">
                <el-form :inline="true" :model="formMas" size="mini" class="mb-2">
                    <el-form-item >
                        <template #label>
                            Fecha <span style="color:red">*</span>
                        </template>
                        <el-date-picker v-model="formMas.date" type="date" value-format="yyyy-MM-dd"></el-date-picker>
                    </el-form-item>
                    <el-form-item >
                        <template #label>
                            Nombre del tercero <span style="color:red">*</span>
                        </template>
                        <el-select
                            v-model="formMas.nombre_tercero"
                            filterable
                            allow-create
                            default-first-option
                            remote
                            reserve-keyword
                            placeholder="Buscar o escribir tercero"
                            :remote-method="buscarTerceros"
                            :loading="loadingTerceros"
                            style="width: 220px"
                        >
                            <el-option
                                v-for="item in listaTerceros"
                                :key="item.id"
                                :label="item.name"
                                :value="item.name"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="Origen">
                        <el-input v-model="formMas.origen"></el-input>
                    </el-form-item>
                    <el-form-item label="N° Soporte">
                        <el-input v-model="formMas.n_soporte"></el-input>
                    </el-form-item>
                    <el-form-item label="Cheque">
                        <el-input v-model="formMas.cheque"></el-input>
                    </el-form-item>
                    <el-form-item >
                        <template #label>
                            Concepto <span style="color:red">*</span>
                        </template>
                        <el-input v-model="formMas.concepto"></el-input>
                    </el-form-item>
                    <el-form-item >
                        <template #label>
                            Valor <span style="color:red">*</span>
                        </template>
                        <el-input v-model="formMas.credit" type="number"></el-input>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="agregarDetalleMas">
                            {{ editIndexMas === null ? 'Agregar' : 'Actualizar' }}
                        </el-button>
                        <el-button
                            v-if="editIndexMas !== null"
                            @click="cancelarEdicionMas"
                            type="default"
                        >Cancelar</el-button>
                    </el-form-item>
                </el-form>
            </el-card>
            <el-table :data="tablaMas" border style="width: 100%">
                <el-table-column prop="date" label="Fecha" width="110"></el-table-column>
                <el-table-column prop="nombre_tercero" label="Nombre del tercero"></el-table-column>
                <el-table-column prop="origen" label="Origen"></el-table-column>
                <el-table-column prop="n_soporte" label="N° Soporte"></el-table-column>
                <el-table-column prop="cheque" label="Cheque"></el-table-column>
                <el-table-column prop="concepto" label="Concepto"></el-table-column>
                <el-table-column
                    prop="valor"
                    label="Valor"
                    width="120"
                    :formatter="row => formatValor(row.credit)"
                ></el-table-column>
                <el-table-column label="Acciones" width="100">
                    <template slot-scope="scope">
                        <el-button type="text" size="mini" icon="el-icon-edit" @click="editarDetalleMas(scope.$index)"></el-button>
                        <el-button type="text" size="mini" icon="el-icon-delete" @click="eliminarDetalleMas(scope.$index)"></el-button>
                    </template>
                </el-table-column>
                <template #empty>
                    <div style="text-align:center; color:#888;">No hay registros pendientes para conciliar.</div>
                </template>
            </el-table>
            <div class="text-right mt-2">
                <strong style="color: #409EFF;">Total Más: {{ totalMas | currency }}</strong>
            </div>
        </div>

        <!-- SALIDAS (DEBITOS) -->
        <div class="section">
            <el-divider content-position="left">Salidas (debitos)</el-divider>
            <el-card shadow="never">
                <el-form :inline="true" :model="formMenos" size="mini" class="mb-2">
                    <el-form-item >
                        <template #label>
                            Fecha <span style="color:red">*</span>
                        </template>
                        <el-date-picker v-model="formMenos.date" type="date" value-format="yyyy-MM-dd"></el-date-picker>
                    </el-form-item>
                    <el-form-item >
                        <template #label>
                            Nombre del tercero <span style="color:red">*</span>
                        </template>
                        <el-select
                            v-model="formMenos.nombre_tercero"
                            filterable
                            allow-create
                            default-first-option
                            remote
                            reserve-keyword
                            placeholder="Buscar o escribir tercero"
                            :remote-method="buscarTerceros"
                            :loading="loadingTerceros"
                            style="width: 220px"
                        >
                            <el-option
                                v-for="item in listaTerceros"
                                :key="item.id"
                                :label="item.name"
                                :value="item.name"
                            />
                        </el-select>
                    </el-form-item>
                    <el-form-item label="Origen">
                        <el-input v-model="formMenos.origen"></el-input>
                    </el-form-item>
                    <el-form-item label="N° Soporte">
                        <el-input v-model="formMenos.n_soporte"></el-input>
                    </el-form-item>
                    <el-form-item label="Cheque">
                        <el-input v-model="formMenos.cheque"></el-input>
                    </el-form-item>
                    <el-form-item >
                        <template #label>
                            Concepto <span style="color:red">*</span>
                        </template>
                        <el-input v-model="formMenos.concepto"></el-input>
                    </el-form-item>
                    <el-form-item >
                        <template #label>
                            Valor <span style="color:red">*</span>
                        </template>
                        <el-input v-model="formMenos.debit" type="number"></el-input>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="agregarDetalleMenos">
                            {{ editIndexMenos === null ? 'Agregar' : 'Actualizar' }}
                        </el-button>
                        <el-button
                            v-if="editIndexMenos !== null"
                            @click="cancelarEdicionMenos"
                            type="default"
                        >Cancelar</el-button>
                    </el-form-item>
                </el-form>
            </el-card>
            <el-table :data="tablaMenos" border style="width: 100%">
                <el-table-column prop="date" label="Fecha" width="110"></el-table-column>
                <el-table-column prop="nombre_tercero" label="Nombre del tercero"></el-table-column>
                <el-table-column prop="origen" label="Origen"></el-table-column>
                <el-table-column prop="n_soporte" label="N° Soporte"></el-table-column>
                <el-table-column prop="cheque" label="Cheque"></el-table-column>
                <el-table-column prop="concepto" label="Concepto"></el-table-column>
                <el-table-column
                    prop="valor"
                    label="Valor"
                    width="120"
                    :formatter="row => formatValor(row.debit)"
                ></el-table-column>
                <el-table-column label="Acciones" width="100">
                    <template slot-scope="scope">
                        <el-button type="text" size="mini" icon="el-icon-edit" @click="editarDetalleMenos(scope.$index)"></el-button>
                        <el-button type="text" size="mini" icon="el-icon-delete" @click="eliminarDetalleMenos(scope.$index)"></el-button>
                    </template>
                </el-table-column>
                <template #empty>
                    <div style="text-align:center; color:#888;">No hay registros pendientes para conciliar.</div>
                </template>
            </el-table>
            <div class="text-right mt-2">
                <strong style="color: #F56C6C;">Total Menos: {{ totalMenos | currency }}</strong>
            </div>
        </div>

        <span slot="footer" class="dialog-footer" style="text-align: right;">
            <el-button @click="close">Cancelar</el-button>
            <el-button type="primary" @click="crearConciliacion">Crear conciliación</el-button>
        </span>
    </el-dialog>
</template>

<script>
export default {
    props: {
        showDialog: { type: Boolean, required: true },
        saldo_libro: { type: [String, Number], required: true },
        saldo_extracto: { type: [String, Number], required: true },
        diferencia_inicial: { type: [String, Number], required: true },
        movimientos: { type: Array, required: true },
        movimientosSeleccionados: { type: Array, required: true },
    },
    data() {
        return {
            tablaMas: [],
            tablaMenos: [],
            formMas: { date: '', nombre_tercero: '', origen: '', n_soporte: '', cheque: '', concepto: '', credit: '' },
            editIndexMas: null,
            formMenos: { date: '', nombre_tercero: '', origen: '', n_soporte: '', cheque: '', concepto: '', debit: '' },
            editIndexMenos: null,
            listaTerceros: [],
            loadingTerceros: false,
        }
    },
    computed: {
        totalMas() {
            return this.tablaMas.reduce((sum, m) => sum + this.parseNumber(m.credit), 0);
        },
        totalMenos() {
            return this.tablaMenos.reduce((sum, m) => sum + this.parseNumber(m.debit), 0);
        },
        diferenciaFinal() {
            // Diferencia original + totalMas - totalMenos
            const libro = this.parseNumber(this.saldo_libro);
            const extracto = this.parseNumber(this.saldo_extracto);
            const diferenciaBase = libro - extracto;
            const diferencia = diferenciaBase + this.totalMas - this.totalMenos;
            return diferencia.toLocaleString('es-CO', { minimumFractionDigits: 2 });
        }
    },
    watch: {
        showDialog(val) {
            if (val) this.procesarTablas();
        },
        movimientos(val) {
            if (Array.isArray(val)) this.procesarTablas();
        },
        movimientosSeleccionados(val) {
            if (Array.isArray(val)) this.procesarTablas();
        }
    },
    methods: {
        async buscarTerceros(query) {
            this.loadingTerceros = true;
            try {
                const res = await this.$http.get('/accounting/journal/thirds/all-third-parties', {
                    params: { search: query || '' }
                });
                this.listaTerceros = res.data.data;
            } catch (e) {
                this.listaTerceros = [];
            }
            this.loadingTerceros = false;
        },
        // ENTRADAS (MÁS)
        agregarDetalleMas() {
            // Validaciones
            if (!this.formMas.date) {
                this.$message.error('La fecha es obligatoria.');
                return;
            }
            if (!this.formMas.nombre_tercero || !this.formMas.nombre_tercero.trim()) {
                this.$message.error('El campo tercero es obligatorio.');
                return;
            }
            if (!this.formMas.concepto || !this.formMas.concepto.trim()) {
                this.$message.error('El campo concepto es obligatorio.');
                return;
            }
            if (!this.formMas.credit || Number(this.formMas.credit) === 0) {
                this.$message.error('El valor no puede ser 0.');
                return;
            }

            if (this.editIndexMas === null) {
                this.tablaMas.push({ ...this.formMas, debit: 0 });
            } else {
                this.$set(this.tablaMas, this.editIndexMas, { ...this.formMas, debit: 0 });
            }
            this.resetFormMas();
        },
        editarDetalleMas(index) {
            this.editIndexMas = index;
            // Convierte el valor a formato válido para el input number
            const detalle = { ...this.tablaMas[index] };
            detalle.credit = this.parseNumber(detalle.credit);
            this.formMas = detalle;
        },
        cancelarEdicionMas() {
            this.resetFormMas();
        },
        resetFormMas() {
            this.formMas = { date: '', nombre_tercero: '', origen: '', n_soporte: '', cheque: '', concepto: '', credit: '' };
            this.editIndexMas = null;
        },
        eliminarDetalleMas(index) {
            this.tablaMas.splice(index, 1);
            this.resetFormMas();
        },

        // SALIDAS (MENOS)
        agregarDetalleMenos() {
            // Validaciones
            if (!this.formMenos.date) {
                this.$message.error('La fecha es obligatoria.');
                return;
            }
            if (!this.formMenos.nombre_tercero || !this.formMenos.nombre_tercero.trim()) {
                this.$message.error('El campo tercero es obligatorio.');
                return;
            }
            if (!this.formMenos.concepto || !this.formMenos.concepto.trim()) {
                this.$message.error('El campo concepto es obligatorio.');
                return;
            }
            if (!this.formMenos.debit || Number(this.formMenos.debit) === 0) {
                this.$message.error('El valor no puede ser 0.');
                return;
            }

            if (this.editIndexMenos === null) {
                this.tablaMenos.push({ ...this.formMenos, credit: 0 });
            } else {
                this.$set(this.tablaMenos, this.editIndexMenos, { ...this.formMenos, credit: 0 });
            }
            this.resetFormMenos();
        },
        editarDetalleMenos(index) {
            this.editIndexMenos = index;
            const detalle = { ...this.tablaMenos[index] };
            detalle.debit = this.parseNumber(detalle.debit);
            this.formMenos = detalle;
        },
        cancelarEdicionMenos() {
            this.resetFormMenos();
        },
        resetFormMenos() {
            this.formMenos = { date: '', nombre_tercero: '', origen: '', n_soporte: '', cheque: '', concepto: '', debit: '' };
            this.editIndexMenos = null;
        },
        eliminarDetalleMenos(index) {
            this.tablaMenos.splice(index, 1);
            this.resetFormMenos();
        },

        async guardarProgreso() { // EN PROCESO, NO SE USA AUN
            // Convierte los valores a formato decimal con punto
            const tablaMas = this.tablaMas.map(item => ({
                ...item,
                credit: this.parseNumber(item.credit)
            }));
            const tablaMenos = this.tablaMenos.map(item => ({
                ...item,
                debit: this.parseNumber(item.debit)
            }));
            // Aquí puedes agregar validaciones si lo deseas
            const payload = {
                bank_account_id: this.$parent.form.bank_account_id,
                month: this.$parent.form.month,
                date: this.$parent.form.date,
                saldo_libro: this.saldo_libro, // <-- agrega esto
                diferencia: this.diferencia_inicial,
                saldo_extracto: this.saldo_extracto,
                detalles_mas: tablaMas,
                detalles_menos: tablaMenos,
            };
            try {
                await this.$http.post('/accounting/bank-reconciliation/store-draft', payload);
                this.$message.success('Progreso guardado correctamente.');
                this.close();
            } catch (e) {
                this.$message.error('Error al guardar el progreso.');
            }
        },
        procesarTablas() {
            if (!Array.isArray(this.movimientos) || !Array.isArray(this.movimientosSeleccionados)) {
                this.tablaMas = [];
                this.tablaMenos = [];
                return;
            }
            const seleccionadosIds = this.movimientosSeleccionados.map(m => Number(m.id));
            // Filtra los NO seleccionados
            const noSeleccionados = this.movimientos.filter(m => !seleccionadosIds.includes(Number(m.id)));
            // Mapea los campos requeridos para las tablas
            this.tablaMas = noSeleccionados
                .filter(m =>
                    this.parseNumber(m.debit) === 0 &&
                    this.parseNumber(m.credit) > 0
                )
                .map(m => ({
                    ...m,
                    origen: 'Libros',
                    n_soporte: m.n_soporte || m.documento || '',
                    cheque: '',
                    concepto: m.concepto || m.description || '',
                    nombre_tercero: m.nombre_tercero || m.third_party_name || '',
                    date: m.date || '',
                }));
            this.tablaMenos = noSeleccionados
                .filter(m =>
                    this.parseNumber(m.credit) === 0 &&
                    this.parseNumber(m.debit) > 0
                )
                .map(m => ({
                    ...m,
                    origen: 'Libros',
                    n_soporte: m.n_soporte || m.documento || '',
                    cheque: '',
                    concepto: m.concepto || m.description || '',
                    nombre_tercero: m.nombre_tercero || m.third_party_name || '',
                    date: m.date || '',
                }));
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
        formatValor(val) {
            return this.parseNumber(val).toLocaleString('es-CO', { minimumFractionDigits: 2 });
        },
        close() {
            this.$emit('update:showDialog', false);
        },
        async crearConciliacion() {
            try {
                // 1. Validar que la diferencia sea 0
                const diferencia = Number(this.diferenciaFinal.replace(/\./g, '').replace(',', '.'));
                if (Math.abs(diferencia) > 0.009) { // Permite un pequeño margen por decimales
                    this.$message.error('La diferencia debe ser exactamente 0 para crear la conciliación.');
                    return;
                }

                // 2. Preparar detalles "más" (entradas)
                const detalles_mas = this.tablaMas.map(item => {
                    // Si tiene id de asiento contable, es automático
                    if (item.id) {
                        return {
                            journal_entry_detail_id: item.id,
                            date: item.date,
                            nombre_tercero: item.nombre_tercero,
                            origen: item.origen,
                            n_soporte: item.n_soporte,
                            cheque: item.cheque,
                            concepto: item.concepto,
                            credit: this.parseNumber(item.credit),
                        };
                    }
                    // Manual
                    return {
                        date: item.date,
                        nombre_tercero: item.nombre_tercero,
                        origen: item.origen,
                        n_soporte: item.n_soporte,
                        cheque: item.cheque,
                        concepto: item.concepto,
                        credit: this.parseNumber(item.credit),
                    };
                });

                // 3. Preparar detalles "menos" (salidas)
                const detalles_menos = this.tablaMenos.map(item => {
                    if (item.id) {
                        return {
                            journal_entry_detail_id: item.id,
                            date: item.date,
                            nombre_tercero: item.nombre_tercero,
                            origen: item.origen,
                            n_soporte: item.n_soporte,
                            cheque: item.cheque,
                            concepto: item.concepto,
                            debit: this.parseNumber(item.debit),
                        };
                    }
                    return {
                        date: item.date,
                        nombre_tercero: item.nombre_tercero,
                        origen: item.origen,
                        n_soporte: item.n_soporte,
                        cheque: item.cheque,
                        concepto: item.concepto,
                        debit: this.parseNumber(item.debit),
                    };
                });

                // 4. Construir el payload
                const payload = {
                    bank_account_id: this.$parent.form.bank_account_id,
                    month: this.$parent.form.month,
                    date: this.$parent.form.date,
                    saldo_extracto: this.saldo_extracto,
                    saldo_libro: this.saldo_libro,
                    diferencia: this.diferencia_inicial,
                    detalles_mas,
                    detalles_menos,
                };

                // 5. Enviar al backend
                const res = await this.$http.post('/accounting/bank-reconciliation/store', payload);
                this.$message.success('Conciliación creada correctamente.');
                this.$emit('refreshTable');
                this.close();
                this.$emit('closeAll');
                // Opcional: refresca la tabla principal
                if (this.$parent.getRecords) this.$parent.getRecords();
            } catch (e) {
                if (e.response && e.response.data && e.response.data.message) {
                    this.$message.error(e.response.data.message);
                } else {
                    this.$message.error('Error al crear la conciliación.');
                }
            }
        },
    },
    filters: {
        currency(val) {
                const num = Number(val);
                if (isNaN(num)) return '0.00';
                return num.toLocaleString('es-CO', { minimumFractionDigits: 2 });
            }
    }
}
</script>
<style scoped>
.section-title {
    margin-bottom: 10px;
    font-weight: bold;
}
.section {
    margin-bottom: 30px;
}
.form-body {
    margin-bottom: 20px;
}
</style>