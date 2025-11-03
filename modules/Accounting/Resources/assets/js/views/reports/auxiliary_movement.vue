<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/accounting/auxiliary-movement">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chart-histogram" style="margin-top: -5px;">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M3 3v18h18"></path>
                        <path d="M20 18v3"></path>
                        <path d="M16 16v5"></path>
                        <path d="M12 13v8"></path>
                        <path d="M8 16v5"></path>
                        <path d="M3 11c6 0 5 -5 9 -5s3 5 9 5"></path>
                    </svg>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active">
                    <span>Reporte de Movimientos Auxiliares</span>
                </li>
            </ol>
        </div>
        <div class="card mb-0">
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-3">
                        <div class="filter-container">
                            <el-date-picker
                                v-model="dateRange"
                                type="daterange"
                                range-separator="a"
                                start-placeholder="Fecha inicio"
                                end-placeholder="Fecha fin"
                                format="yyyy-MM-dd"
                                value-format="yyyy-MM-dd"
                                class="date-picker"
                            />
                        </div>
                    </div>
                    <div class="col-2">
                        <el-select v-model="account_code" placeholder="Cuenta contable" filterable clearable>
                            <el-option
                                v-for="item in accountsList"
                                :key="item.code"
                                :label="item.code + ' - ' + item.name"
                                :value="item.code"
                            />
                        </el-select>
                    </div>
                    <div class="col-2">
                        <el-select v-model="third_party_type" placeholder="Tipo de tercero" @change="fetchThirdParties">
                            <el-option label="Clientes" value="customers"/>
                            <el-option label="Proveedores" value="suppliers"/>
                            <el-option label="Empleados" value="employee"/>
                            <el-option label="Vendedores" value="seller"/>
                        </el-select>
                    </div>
                    <div class="col-2">
                        <el-select v-model="third_party_id" placeholder="Tercero" filterable clearable>
                            <el-option
                                v-for="item in thirdParties"
                                :key="item.id"
                                :label="item.name"
                                :value="item.id"
                            />
                        </el-select>
                    </div>
                    <div class="col-1 text-right">
                        <el-button type="primary" @click="onDateChange">Buscar</el-button>
                    </div>
                    <div class="col-1 text-right">
                        <el-button type="primary" @click="ReportDownload('pdf')">Pdf</el-button>
                    </div>
                    <div class="col-1 text-right">
                        <el-button type="success" @click="ReportDownload('excel')">Excel</el-button>
                    </div>
                </div>
                <div class="row">
                    <div v-if="loading" class="w-100 text-center my-3">
                        <el-spinner type="circle" /> Cargando...
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th class="font-weight-bold">Codigo</th>
                                <th class="font-weight-bold">Cuenta</th>
                                <th class="font-weight-bold">Comprobante</th>
                                <th class="font-weight-bold">Fecha</th>
                                <th class="font-weight-bold">Número de documento</th>
                                <th class="font-weight-bold">Nombre del tercero</th>
                                <th class="font-weight-bold">Descripción</th>
                                <th class="font-weight-bold">Saldo inicial</th>
                                <th class="font-weight-bold">Débito</th>
                                <th class="font-weight-bold">Crédito</th>
                                <th class="font-weight-bold">Saldo final</th>
                            </tr>
                            <tbody v-if="accounts.length">
                                <template v-for="group in accounts" >
                                    <tr class="bg-light">
                                        <td colspan="7">
                                            <span class="font-weight-bold">Cuenta contable:</span> {{ group.account_code }} {{ group.account_name }}
                                        </td>
                                        <td>{{ group.balance_initial | numberFormat }}</td>
                                        <td>{{ group.total_debit | numberFormat }}</td>
                                        <td>{{ group.total_credit | numberFormat }}</td>
                                        <td>{{ group.balance_final | numberFormat }}</td>
                                    </tr>
                                    <tr v-for="row in group.details" :key="row.id">
                                        <td>{{ row.account_code }}</td>
                                        <td>{{ row.account_name }}</td>
                                        <td>{{ row.document_info && row.document_info.number }}</td>
                                        <td>{{ row.date }}</td>
                                        <td>{{ row.third_party_number }}</td>
                                        <td>{{ row.third_party_name }}</td>
                                        <td>{{ row.description }}</td>
                                        <td class="text-right">0</td>
                                        <td class="text-right">{{ row.debit | numberFormat }}</td>
                                        <td class="text-right">{{ row.credit | numberFormat }}</td>
                                        <td class="text-right">0</td>
                                    </tr>
                                </template>
                                <tr>
                                    <td>TOTAL</td>
                                    <td colspan="7"></td>
                                    <td>{{ parseFloat(accounts.reduce((acc, group) => acc + group.total_debit, 0)).toFixed(2) | numberFormat }}</td>
                                    <td>{{ parseFloat(accounts.reduce((acc, group) => acc + group.total_credit, 0)).toFixed(2) | numberFormat }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="9" class="text-center">No se encontraron registros</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import DataTable from '../components/DataTableReport.vue';
import queryString from 'query-string';
import moment from 'moment';

export default {
    components: { DataTable },
    data() {
        return {
            dateRange: [
                moment().startOf('month').format('YYYY-MM-DD'),
                moment().endOf('month').format('YYYY-MM-DD')
            ],
            accounts: [],
            account_code: '',
            third_party_type: '',
            third_party_id: '',
            accountsList: [],
            thirdParties: [],
            loading: false,
        }
    },
    mounted() {
        this.fetchAccounts();
        this.fetchThirdParties();
        // this.onDateChange();
    },
    methods: {
        async fetchAccounts() {
            const res = await this.$http.get('/accounting/charts/all');
            this.accountsList = res.data.data || [];
        },
        async fetchThirdParties() {
            if (!this.third_party_type) {
                this.thirdParties = [];
                this.third_party_id = '';
                return;
            }
            const res = await this.$http.get('/accounting/journal/thirds/third-parties', {
                params: { type: this.third_party_type }
            });
            // Extrae el id de origen (puede ser null)
            this.thirdParties = (res.data.data || []).map(item => {
                let id = item.id;
                if (typeof id === 'string' && id.includes('_')) {
                    id = id.split('_')[1];
                }
                // Si el id es 'null' como string, pásalo a null
                if (id === 'null') id = null;
                return { ...item, id };
            });
            this.third_party_id = '';
        },
        async fetchData(params = {}) {
            this.loading = true;
            try {
                const response = await this.$http.get('/accounting/auxiliary-movement/records', { params });
                if(response.data.data.length > 0) {
                    this.accounts = response.data.data;
                } else {
                    this.accounts = [];
                }
            } finally {
                this.loading = false;
            }
        },
        onDateChange() {
            let params = {
                date_start: this.dateRange[0],
                date_end: this.dateRange[1],
            };
            if (this.account_code) params.account_code = this.account_code;
            if (this.third_party_type && this.third_party_id !== '') {
                params.third_party_type = this.third_party_type;
                params.third_party_origin_id = this.third_party_id; // Puede ser null
            }
            this.fetchData(params);
        },
        ReportDownload(type = 'pdf') {
            let params = {
                date_start: this.dateRange[0],
                date_end: this.dateRange[1],
                format: type,
            };
            if (this.account_code) params.account_code = this.account_code;
            if (this.third_party_type && this.third_party_id !== '') {
                params.third_party_type = this.third_party_type;
                params.third_party_origin_id = this.third_party_id; // Puede ser null
            }
            window.open(`/accounting/auxiliary-movement/export?${queryString.stringify(params)}`, '_blank');
        }
    }
}
</script>