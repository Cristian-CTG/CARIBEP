<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <span>Conciliaciones Bancarias</span>
            </h2>
            <div class="right-wrapper pull-right">
                <button class="btn btn-custom btn-sm mt-2 mr-2" @click="showDialog = true">
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
                            @change="getRecords"
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
                            @change="getRecords"
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
                            @change="getRecords"
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
                <data-table :resource="resource" ref="dataTable" :applyFilter="false">
                    <tr slot="heading">
                        <th>Fecha de Creación</th>
                        <th>Mes de Conciliación</th>
                        <th>Cuenta Bancaria</th>
                    </tr>
                    <tr slot-scope="{ row }">
                        <td>{{ row.created_at | dateFormat }}</td>
                        <td>{{ row.month }}</td>
                        <td>
                            <span v-if="row.bank_account">
                                {{ row.bank_account.description }}<br>
                                <small>{{ row.bank_account.number }}</small>
                            </span>
                        </td>
                    </tr>
                </data-table>
            </div>
        </div>
    </div>
</template>

<script>
import DataTable from "../components/DataTable.vue";
import moment from "moment";

export default {
    components: { DataTable },
    data() {
        return {
            resource: "accounting/bank-reconciliation",
            filters: {
                daterange: null,
                month: null,
                bank_account_id: null,
            },
            bankAccounts: [],
        };
    },
    computed: {
        customFilters() {
            let filters = {};
            if (this.filters.daterange && this.filters.daterange.length === 2) {
                filters.column = 'daterange';
                filters.value = this.filters.daterange.join('_');
            } else if (this.filters.month) {
                filters.column = 'month';
                filters.value = this.filters.month;
            }
            if (this.filters.bank_account_id) {
                filters.bank_account_id = this.filters.bank_account_id;
            }
            return filters;
        }
    },
    filters: {
        dateFormat(value) {
            if (!value) return "";
            return moment(value).format("YYYY-MM-DD HH:mm");
        }
    },
    methods: {
        async getBankAccounts() {
            const res = await this.$http.get('/accounting/bank-reconciliation/bank-accounts');
            this.bankAccounts = res.data;
        },
        getRecords() {
            this.$refs.dataTable.getRecords();
        }
    },
    async mounted() {
        await this.getBankAccounts();
    }
};
</script>