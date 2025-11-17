<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/accounting/general-ledger" class="mr-2">
                    <i class="fa fa-book"></i>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active">
                    <span>Libro Mayor y Balance</span>
                </li>
            </ol>
        </div>

        <div class="card mb-0">
            <div class="card-body">
                <!-- Filtros -->
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
                    <div class="col-md-3 mb-2">
                        <label>Cuenta contable</label>
                        <el-select 
                            v-model="filterAccount"
                            filterable
                            remote
                            :remote-method="searchAccounts"
                            clearable
                            placeholder="Seleccione cuenta"
                            class="w-100"
                        >
                            <el-option
                                v-for="acc in accounts"
                                :key="acc.code"
                                :label="acc.code + ' - ' + acc.name"
                                :value="acc.code"
                            />
                        </el-select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Nivel contable</label>
                        <el-select 
                            v-model="filterLevel"
                            clearable
                            placeholder="Nivel"
                            class="w-100"
                        >
                            <el-option v-for="n in [1,2,3,4,5,6]" :key="n" :label="'Nivel ' + n" :value="n"/>
                        </el-select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label>Tipo de cuenta</label>
                        <el-select v-model="filterType" clearable placeholder="Tipo" class="w-100">
                            <el-option label="Activo" value="Asset" />
                            <el-option label="Pasivo" value="Liability" />
                            <el-option label="Patrimonio" value="Equity" />
                            <el-option label="Ingreso" value="Revenue" />
                            <el-option label="Gasto" value="Expense" />
                            <el-option label="Costo de Ventas" value="Cost" />
                            <el-option label="Costo de Producción / Operación" value="ProductionCost" />
                            <el-option label="Ctas Orden Deudoras" value="OrderDebit" />
                            <el-option label="Ctas Orden Acreedoras" value="OrderCredit" />
                        </el-select>
                    </div>
                </div>
                <div class="row mb-3 align-items-end">
                    <div class="col-md-2 mb-2">
                        <label>Código desde</label>
                        <el-input v-model="filterCodeFrom" placeholder="Código"/>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Código hasta</label>
                        <el-input v-model="filterCodeTo" placeholder="Código"/>
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-center">
                        <el-checkbox v-model="hideEmpty">Ocultar sin movimientos</el-checkbox>
                    </div>
                    <div class="col-md-6 mb-2 text-right">
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

                <!-- Resultados -->
                <div v-if="loading" class="text-center my-3">
                    <el-spinner type="circle"/> Cargando...
                </div>

                <div v-else>
                    <div v-if="rows.length === 0" class="text-center my-4">
                        No se encontraron datos
                    </div>

                    <!-- LISTA DE CUENTAS -->
                    <div v-for="acc in filteredResults" :key="acc.code" class="mb-4">
                        <div class="row mb-1">
                            <div class="col-md-6">
                                <h5 class="mb-1"><b>{{ acc.code }} - {{ acc.name }}</b></h5>
                            </div>
                            <div class="col-md-6 text-right">
                                <span class="mr-3"><b>Saldo inicial:</b> {{ acc.saldo_inicial | numberFormat }}</span>
                                <span><b>Saldo final:</b> {{ acc.saldo_final | numberFormat }}</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Documento</th>
                                        <th>Detalle</th>
                                        <th class="text-right">Débito</th>
                                        <th class="text-right">Crédito</th>
                                        <th class="text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="acc.movements.length === 0">
                                        <td colspan="6" class="text-center">Sin movimientos</td>
                                    </tr>
                                    <tr v-for="m in acc.movements" :key="m.id">
                                        <td>{{ m.date }}</td>
                                        <td>{{ m.document }}</td>
                                        <td>{{ m.detail }}</td>
                                        <td class="text-right">{{ m.debit | numberFormat }}</td>
                                        <td class="text-right">{{ m.credit | numberFormat }}</td>
                                        <td class="text-right">{{ m.balance | numberFormat }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>


<script>
import moment from "moment";
export default {
    data() {
        return {
            dateRange: [
                moment().startOf("month").format("YYYY-MM-DD"),
                moment().endOf("month").format("YYYY-MM-DD")
            ],

            rows: [],
            loading: false,
            hideEmpty: false,
            filterAccount: null,
            filterLevel: null,
            filterCodeFrom: "",
            filterCodeTo: "",
            filterType: null,

            accounts: []
        };
    },

    mounted() {
        this.fetchData();
        this.fetchAccounts();
    },

    computed: {
        filteredResults() {
            if (!this.hideEmpty) return this.rows;
            return this.rows.filter(acc => acc.movements.length > 0);
        }
    },

    methods: {

        async fetchData() {
            this.loading = true;
            try {
                const { data } = await this.$http.get("/accounting/general-ledger/records", {
                    params: {
                        date_start: this.dateRange[0],
                        date_end: this.dateRange[1],
                        filterAccount: this.filterAccount,
                        filterLevel: this.filterLevel,
                        filterCodeFrom: this.filterCodeFrom,
                        filterCodeTo: this.filterCodeTo,
                        filterType: this.filterType
                    }
                });

                this.rows = data.data;

            } catch (e) {
                this.$message.error("Error al cargar datos");
            }
            this.loading = false;
        },

        async fetchAccounts() {
            try {
                const { data } = await this.$http.get('/accounting/general-ledger/accounts');
                this.accounts = data;
            } catch (e) {
                this.$message.error("Error al cargar cuentas contables");
            }
        },

        async searchAccounts(query) {
            if (query !== '') {
                const { data } = await this.$http.get('/accounting/general-ledger/accounts', {
                    params: { search: query }
                });
                this.accounts = data;
            } else {
                this.accounts = [];
            }
        },

        exportReport(type) {
            const params = new URLSearchParams({
                date_start: this.dateRange[0],
                date_end: this.dateRange[1],
                format: type,
            });

            if (this.hideEmpty) params.append("hideEmpty", 1);
            if (this.filterAccount) params.append("filterAccount", this.filterAccount);
            if (this.filterLevel) params.append("filterLevel", this.filterLevel);
            if (this.filterCodeFrom) params.append("filterCodeFrom", this.filterCodeFrom);
            if (this.filterCodeTo) params.append("filterCodeTo", this.filterCodeTo);
            if (this.filterType) params.append("filterType", this.filterType);

            window.open(`/accounting/general-ledger/export?${params.toString()}`, "_blank");
        }
    }
};
</script>
