<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/cash">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-cash-register" style="margin-top: -5px;"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M21 15h-2.5c-.398 0 -.779 .158 -1.061 .439c-.281 .281 -.439 .663 -.439 1.061c0 .398 .158 .779 .439 1.061c.281 .281 .663 .439 1.061 .439h1c.398 0 .779 .158 1.061 .439c.281 .281 .439 .663 .439 1.061c0 .398 -.158 .779 -.439 1.061c-.281 .281 -.663 .439 -1.061 .439h-2.5"></path><path d="M19 21v1m0 -8v1"></path><path d="M13 21h-7c-.53 0 -1.039 -.211 -1.414 -.586c-.375 -.375 -.586 -.884 -.586 -1.414v-10c0 -.53 .211 -1.039 .586 -1.414c.375 -.375 .884 -.586 1.414 -.586h2m12 3.12v-1.12c0 -.53 -.211 -1.039 -.586 -1.414c-.375 -.375 -.884 -.586 -1.414 -.586h-2"></path><path d="M16 10v-6c0 -.53 -.211 -1.039 -.586 -1.414c-.375 -.375 -.884 -.586 -1.414 -.586h-4c-.53 0 -1.039 .211 -1.414 .586c-.375 .375 -.586 .884 -.586 1.414v6m8 0h-8m8 0h1m-9 0h-1"></path><path d="M8 14v.01"></path><path d="M8 17v.01"></path><path d="M12 13.99v.01"></path><path d="M12 17v.01"></path></svg>
            </a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Cajas</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <template  v-if="open_cash">
                    <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickDownloadGeneral()"><i class="fas fa-shopping-cart"></i> Reporte general (Cajas del Día)</button>

                    <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickCreate()"><i class="fas fa-shopping-cart"></i> Aperturar Caja</button>
                </template>
                <!-- <template v-else>                 -->
                    <!-- <button type="button" class="btn btn-success btn-sm  mt-2 mr-2" @click.prevent="clickOpenPos()"><i class="fas fa-shopping-cart" ></i> Aperturar punto de venta</button> -->
                <!-- </template> -->
            </div>
        </div>
        <div class="card mb-0">
            <!-- <div class="card-header bg-info">
                <h3 class="my-0">Listado de cajas</h3>
            </div> -->
            <div class="card-body">
                <data-table :resource="resource" :cashFilters="true">
                    <tr slot="heading">
                        <!-- <th>#</th> -->
                        <th># Referencia</th>
                        <th>Vendedor</th>
                        <th class="text-center">Apertura</th>
                        <th class="text-center">Cierre</th>
                        <th class="text-right">Saldo inicial</th>
                        <th class="text-right">Saldo final</th>
                        <!-- <th>Ingreso</th> -->
                        <!-- <th>Egreso</th> -->
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                    <tr slot-scope="{ index, row }">
                        <!-- <td>{{ index }}</td> -->
                        <td>{{ row.reference_number }}</td>
                        <td>{{ row.user }}</td>
                        <td class="text-center">{{ row.opening }}</td>
                        <td class="text-center">{{ row.closed }}</td>
                        <td class="text-right">{{ row.beginning_balance | numberFormat }}</td>
                        <td class="text-right">{{ row.final_balance | numberFormat }}</td>
                        <!-- <td>{{ row.income }}</td>
                        <td>{{ row.expense }}</td> -->
                        <td>{{ row.state_description }}</td>
                        <td class="text-center">
                            <el-dropdown trigger="click">
                                <el-button size="mini" type="secondary" class="btn btn-default btn-sm btn-dropdown-toggle">
                                    <i class="fas fa-ellipsis-h"></i>
                                </el-button>
                            
                                <el-dropdown-menu slot="dropdown">
                                
                                    <el-dropdown-item @click.native="showReportModal(row.id)">
                                        Reporte
                                    </el-dropdown-item>
                                
                                    <el-dropdown-item @click.native="clickDownload(row.id, 'resumido')">
                                        Reporte Resumen
                                    </el-dropdown-item>
                                
                                    <el-dropdown-item @click.native="showArqueoModal(row.id)">
                                        Arqueo
                                    </el-dropdown-item>
                                
                                    <el-dropdown-item divided></el-dropdown-item>
                                
                                    <template v-if="row.state">
                                        <el-dropdown-item @click.native="clickCloseCash(row.id)">
                                            Cerrar caja
                                        </el-dropdown-item>
                                    
                                        <el-dropdown-item v-if="typeUser === 'admin'" @click.native="clickCreate(row.id)">
                                            Editar
                                            <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-edit text-muted" style="float: right;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                                        </el-dropdown-item>
                                    
                                        <el-dropdown-item v-if="typeUser === 'admin'" @click.native="clickDelete(row.id)" class="text-danger">
                                            Eliminar
                                            <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash text-danger" style="float: right;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                        </el-dropdown-item>
                                    </template>
                                </el-dropdown-menu>
                            </el-dropdown>
                        </td>
                    </tr>
                </data-table>
            </div>

        </div>
        <cash-form :showDialog.sync="showDialog" :typeUser="typeUser"
                            :recordId="recordId"></cash-form>

        <!-- Modal para tipo de reporte -->
        <el-dialog title="Seleccionar Tipo de Reporte" :visible.sync="showReportDialog" append-to-body>
            <div class="row">
                <div class="col-md-6">
                    <el-select v-model="selectedReportType" placeholder="Seleccione tipo de reporte">
                        <el-option label="Todos" value="all"></el-option>
                        <el-option label="Electrónico" value="1"></el-option>
                        <el-option label="No Electrónico" value="0"></el-option>
                    </el-select>
                </div>
                <div class="col-md-6">
                    <el-select v-model="selectedReportFormat" placeholder="Formato">
                        <el-option label="A4" value="a4"></el-option>
                        <el-option label="Tirilla" value="ticket"></el-option>
                    </el-select>
                </div>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="showReportDialog = false">Cancelar</el-button>
                <el-button type="primary" @click="generateReport">Generar</el-button>
            </span>
        </el-dialog>

        <!-- Modal para tipo de arqueo -->
        <el-dialog title="Seleccionar Tipo de Arqueo" :visible.sync="showArqueoDialog" append-to-body>
            <div class="row">
                <div class="col-md-12">
                    <el-select v-model="selectedArqueoType" placeholder="Seleccione tipo de llenado">
                        <el-option label="Automatico" value="complete"></el-option>
                        <el-option label="Manual" value="simple"></el-option>
                    </el-select>
                </div>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="showArqueoDialog = false">Cancelar</el-button>
                <el-button type="primary" @click="generateArqueo">Generar</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>

    import DataTable from '../../../components/DataTable.vue'
    import {deletable} from '../../../mixins/deletable'
    import CashForm from './form.vue'
    import {functions} from '@mixins/functions'

    export default {
        mixins: [deletable, functions],
        components: { DataTable, CashForm},
        props: ['typeUser'],
        data() {
            return {
                showDialog: false,
                showReportDialog: false,
                showArqueoDialog: false,
                selectedReportType: 'all',
                selectedReportFormat: 'a4', // Nuevo: formato por defecto
                selectedArqueoType: 'complete',
                selectedCashId: null,
                open_cash: true,
                resource: 'cash',
                recordId: null,
                cash:null,
            }
        },
        async created() {

            /*await this.$http.get(`/${this.resource}/opening_cash`)
                .then(response => {
                    this.cash = response.data.cash
                    this.open_cash = (this.cash) ? false : true
                })*/

            /*this.$eventHub.$on('openCash', () => {
                this.open_cash = false
            })*/

        },
        methods: {
            showReportModal(id) {
                this.selectedCashId = id;
                this.showReportDialog = true;
            },
            generateReport() {
                let url = '';
                if (this.selectedReportFormat === 'ticket') {
                    url = `/${this.resource}/report-ticket/${this.selectedCashId}/${this.selectedReportType}?format=ticket&electronic_type=${this.selectedReportType}`;
                } else {
                    url = `/${this.resource}/report/${this.selectedCashId}/${this.selectedReportType}`;
                }
                window.open(url, '_blank');
                this.showReportDialog = false;
            },
            showArqueoModal(id) {
                this.selectedCashId = id;
                this.showArqueoDialog = true;
            },
            generateArqueo() {
                window.open(`/${this.resource}/report-ticket/${this.selectedCashId}/${this.selectedArqueoType}`, '_blank');
                this.showArqueoDialog = false;
            },
            clickDownload(id, only_head = '') {
                window.open(`/${this.resource}/report/${id}/${only_head}`, '_blank');
            },
            clickDownloadArqueo(id) {
                window.open(`/${this.resource}/report-ticket/${id}`, '_blank');
            },
            clickDownloadIncomeSummary(id) {
                window.open(`/${this.resource}/report/income-summary/${id}`, '_blank');
            },
            clickCreate(recordId = null) {
                this.recordId = recordId
                this.showDialog = true
            },
            clickCloseCash(recordId) {

                this.recordId = recordId
                const h = this.$createElement;
                this.$msgbox({
                    title: 'Cerrar Caja',
                    type: 'warning',
                    message: h('p', null, [
                        h('p', { style: 'text-align: justify; font-size:15px' }, '¿Está seguro de cerrar la caja?'),
                    ]),

                    showCancelButton: true,
                    confirmButtonText: 'Cerrar',
                    cancelButtonText: 'Cancelar',
                    beforeClose: (action, instance, done) => {
                        if (action === 'confirm') {
                            this.createRegister(instance, done)
                        } else {
                            done();
                        }
                    }
                    })
                    .then(action => {
                        })
                    .catch(action => {
                    });



            },
            createRegister(instance, done){

                instance.confirmButtonLoading = true;
                instance.confirmButtonText = 'Cerrando caja...';

                this.$http.get(`/${this.resource}/close/${this.recordId}`)
                    .then(response => {
                        if(response.data.success){
                            this.$eventHub.$emit('reloadData')
                            this.open_cash = true
                            this.$message.success(response.data.message)
                        }else{
                            console.log(response)
                        }
                    })
                    .catch(error => {
                        console.log(error)
                    })
                    .then(() => {
                        instance.confirmButtonLoading = false
                        instance.confirmButtonText = 'Iniciar prueba'
                        done()
                    })

            },
            clickOpenPos() {
                window.open('/pos')
            },
            clickDelete(id) {
                this.destroy(`/${this.resource}/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            clickDownloadGeneral()
            {
                window.open(`/${this.resource}/report`, '_blank');
            },
            clickDownloadProducts(id)
            {
                window.open(`/${this.resource}/report/products/${id}`, '_blank');

            }
        }
    }
</script>
