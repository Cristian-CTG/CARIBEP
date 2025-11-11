<template>
    <div>
        <div class="page-header pr-0 ">
            <h2 >
                <a href="/accounting/trial-balance" class="mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round"
                        class="icon icon-tabler icon-tabler-scale">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M7 20h10" />
                        <path d="M6 6l6 -1l6 1" />
                        <path d="M12 3v17" />
                        <path d="M9 12l-3 6h6l-3 -6" />
                        <path d="M15 12l3 6h-6l3 -6" />
                    </svg>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active">
                    <span>Balance de Prueba</span>
                </li>
            </ol>
        </div>
        <div class="card mb-0">
            <div class="card-body">
                <div class="row mb-3 align-items-end">
                    <div class="col-md-4 mb-2">
                        <label>Rango de fechas</label>
                        <el-date-picker
                            v-model="dateRange"
                            type="daterange"
                            range-separator="a"
                            start-placeholder="Fecha inicio"
                            end-placeholder="Fecha fin"
                            format="yyyy-MM-dd"
                            value-format="yyyy-MM-dd"
                            class="w-100"
                        />
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Nivel contable</label>
                        <el-select v-model="filterLevel" placeholder="Nivel contable" clearable class="w-100">
                            <el-option v-for="n in [1,2,3,4,5,6]" :key="n" :label="'Nivel ' + n" :value="n" />
                        </el-select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Código desde</label>
                        <el-input v-model="filterCodeFrom" placeholder="Código desde" />
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Código hasta</label>
                        <el-input v-model="filterCodeTo" placeholder="Código hasta" />
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Tipo de cuenta</label>
                        <el-select v-model="filterType" placeholder="Tipo de cuenta" clearable class="w-100">
                            <el-option label="Activo" value="Asset" />
                            <el-option label="Pasivo" value="Liability" />
                            <el-option label="Patrimonio" value="Equity" />
                            <el-option label="Ingreso" value="Revenue" />
                            <el-option label="Gasto" value="Expense" />
                            <el-option label="Costo de Ventas" value="Cost" />
                            <el-option label="Costo de Producción o de Operación" value="ProductionCost" />
                            <el-option label="Cuentas de orden Deudoras" value="OrderDebit" />
                            <el-option label="Cuentas de orden Acreedoras" value="OrderCredit" />
                        </el-select>
                    </div>
                </div>

                <div class="row mb-3 align-items-center">
                    <div class="col-md-4">
                        <el-checkbox v-model="hideZeroMovements">Ocultar sin movimientos</el-checkbox>
                    </div>
                    <div class="col-md-8 text-right">
                        <div class="row justify-content-end">
                            <div class="col-auto mb-1">
                                <el-button type="primary" @click="fetchData">Buscar</el-button>
                            </div>
                            <div class="col-auto mb-1">
                                <el-button type="primary" @click="exportReport('pdf')">PDF</el-button>
                            </div>
                            <div class="col-auto mb-1">
                                <el-button type="success" @click="exportReport('excel')">Excel</el-button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div v-if="loading" class="w-100 text-center my-3">
                        <el-spinner type="circle" /> Cargando...
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Cuenta</th>
                                    <th class="text-right">Saldo inicial</th>
                                    <th class="text-right">Débitos</th>
                                    <th class="text-right">Créditos</th>
                                    <th class="text-right">Saldo final</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in filteredRows" :key="row.code">
                                    <td>{{ row.code }}</td>
                                    <td>{{ row.name }}</td>
                                    <td class="text-right">{{ row.saldo_inicial | numberFormat }}</td>
                                    <td class="text-right">{{ row.debitos | numberFormat }}</td>
                                    <td class="text-right">{{ row.creditos | numberFormat }}</td>
                                    <td class="text-right">{{ row.saldo_final | numberFormat }}</td>
                                </tr>
                                <tr v-if="!filteredRows.length">
                                    <td colspan="6" class="text-center">No se encontraron registros</td>
                                </tr>
                            </tbody>
                            <tfoot v-if="filteredRows.length">
                                <tr class="font-weight-bold bg-light">
                                    <td colspan="2" class="text-right">Totales:</td>
                                    <td class="text-right">{{ totalSaldoInicial | numberFormat }}</td>
                                    <td class="text-right">{{ totalDebitos | numberFormat }}</td>
                                    <td class="text-right">{{ totalCreditos | numberFormat }}</td>
                                    <td class="text-right">{{ totalSaldoFinal | numberFormat }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import moment from 'moment';

export default {
    data() {
        return {
            dateRange: [
                moment().startOf('month').format('YYYY-MM-DD'),
                moment().endOf('month').format('YYYY-MM-DD')
            ],
            rows: [],
            loading: false,
            hideZeroMovements: false,
            filterLevel: null,
            filterCodeFrom: '',
            filterCodeTo: '',
            filterType: null,
            // hideZeroFinal: false
        }
    },
    mounted() {
        this.fetchData();
    },
    computed: {
        filteredRows() {
            let rows = this.rows

            if (this.hideZeroMovements) {
                rows = rows.filter(r => (r.debitos > 0 || r.creditos > 0));
            }
            if (this.filterLevel) {
                rows = rows.filter(r => r.level === this.filterLevel);
            }
            if (this.filterCodeFrom && this.filterCodeTo) {
                rows = rows.filter(r => r.code >= this.filterCodeFrom && r.code <= this.filterCodeTo);
            }
            if (this.filterType) {
                rows = rows.filter(r => r.type === this.filterType);
            }
            // if (this.hideZeroFinal) {
            //     rows = rows.filter(r => r.saldo_final !== 0);
            // }
            return rows;
        },
        totalDebitos() {
            return this.filteredRows.reduce((sum, r) => sum + r.debitos, 0);
        },
        totalCreditos() {
            return this.filteredRows.reduce((sum, r) => sum + r.creditos, 0);
        },
        totalSaldoFinal() {
            return this.filteredRows.reduce((sum, r) => sum + r.saldo_final, 0);
        },
        totalSaldoInicial() {
            return this.filteredRows.reduce((sum, r) => sum + r.saldo_inicial, 0);
        }
    },
    methods: {
        async fetchData() {
            this.loading = true;
            try {
                const { data } = await axios.get('/accounting/trial-balance/records', {
                    params: {
                        date_start: this.dateRange[0],
                        date_end: this.dateRange[1],
                    }
                });
                this.rows = data.data;
            } catch (e) {
                this.$message.error('Error al cargar los datos');
            } finally {
                this.loading = false;
            }
        },
        exportReport(type) {
            const params = new URLSearchParams({
                date_start: this.dateRange[0],
                date_end: this.dateRange[1],
                format: type,
            });

            if (this.filterLevel) params.append('filterLevel', this.filterLevel)
            if (this.filterCodeFrom) params.append('filterCodeFrom', this.filterCodeFrom)
            if (this.filterCodeTo) params.append('filterCodeTo', this.filterCodeTo)
            if (this.filterType) params.append('filterType', this.filterType)
            if (this.hideZeroMovements) params.append('hideZeroMovements', true)
            // if (this.hideZeroFinal) params.append('hideZeroFinal', true)

            window.open(`/accounting/trial-balance/export?${params.toString()}`, '_blank');
        }
    }
}
</script>