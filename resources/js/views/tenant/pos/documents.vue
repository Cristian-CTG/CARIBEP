<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/document-pos/index">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-cash-register" style="margin-top: -5px;"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M21 15h-2.5c-.398 0 -.779 .158 -1.061 .439c-.281 .281 -.439 .663 -.439 1.061c0 .398 .158 .779 .439 1.061c.281 .281 .663 .439 1.061 .439h1c.398 0 .779 .158 1.061 .439c.281 .281 .439 .663 .439 1.061c0 .398 -.158 .779 -.439 1.061c-.281 .281 -.663 .439 -1.061 .439h-2.5"></path><path d="M19 21v1m0 -8v1"></path><path d="M13 21h-7c-.53 0 -1.039 -.211 -1.414 -.586c-.375 -.375 -.586 -.884 -.586 -1.414v-10c0 -.53 .211 -1.039 .586 -1.414c.375 -.375 .884 -.586 1.414 -.586h2m12 3.12v-1.12c0 -.53 -.211 -1.039 -.586 -1.414c-.375 -.375 -.884 -.586 -1.414 -.586h-2"></path><path d="M16 10v-6c0 -.53 -.211 -1.039 -.586 -1.414c-.375 -.375 -.884 -.586 -1.414 -.586h-4c-.53 0 -1.039 .211 -1.414 .586c-.375 .375 -.586 .884 -.586 1.414v6m8 0h-8m8 0h1m-9 0h-1"></path><path d="M8 14v.01"></path><path d="M8 17v.01"></path><path d="M12 13.99v.01"></path><path d="M12 17v.01"></path></svg>
            </a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Documentos POS</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <el-tooltip class="item" effect="dark" content="Importa las facturas con estado Aceptada en el API que no se encuentran registradas" placement="bottom">
                    <el-button class="btn btn-custom btn-sm  mt-2 mr-2" :loading="sincronizing" @click.prevent="openSyncDialog"><i class="fas fa-sync-alt" ></i> Sincronizar Envios API</el-button>
                </el-tooltip>
            </div>
        </div>
        <div class="card mb-0">
            <div class="card-body pt-0">
                <div class="row p-2 justify-content-end">
                    <el-dropdown :hide-on-click="false">
                        <el-button type="primary">
                            Mostrar/Ocultar columnas<i class="el-icon-arrow-down el-icon--right"></i>
                        </el-button>
                        <el-dropdown-menu slot="dropdown">
                            <el-dropdown-item v-for="(column, index) in columns" :key="index">
                                <el-checkbox v-model="column.visible">{{ column.title }}</el-checkbox>
                            </el-dropdown-item>
                        </el-dropdown-menu>
                    </el-dropdown>
                </div>
                <data-table :resource="resource">
                    <tr slot="heading">
                        <!-- <th>#</th> -->
                        <th class="text-center">Fecha Emisión</th>
                        <th>Cliente</th>
                        <th>Nota de Venta</th>
                        <th>Estado</th>
                        <th class="text-center">Moneda</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">POS Electronico</th>
                        <th class="text-center" v-if="columns.total_paid.visible">Pagado</th>
                        <th class="text-center" v-if="columns.total_pending_paid.visible">Por pagar</th>
                        <th class="text-center">Estado pago</th>
                        <th class="text-center">Descarga</th>
                         <th class="text-center" v-if="columns.type_period.visible" >
                            Tipo Periodo
                        </th>
                        <th class="text-center" v-if="columns.quantity_period.visible" >
                            Cantidad Periodo
                        </th>
                        <th class="text-center" v-if="columns.paid.visible">
                            Estado de Pago
                        </th>
                        <th class="text-right">Acciones</th>
                    </tr>
                    <tr slot-scope="{ index, row }">
                        <!-- <td>{{ index }}</td> -->
                        <td class="text-center">{{ row.date_of_issue }}</td>
                        <td>{{ row.customer_name }}<br/><small v-text="row.customer_number"></small><br/></td>
                        <td>{{ row.full_number }}
                        </td>
                        <td>
                            <span class="badge bg-secondary text-white" :class="{'bg-danger': (row.state_type_id === '11'), 'bg-warning': (row.state_type_id === '13'), 'bg-secondary': (row.state_type_id === '01'), 'bg-info': (row.state_type_id === '03'), 'bg-success': (row.state_type_id === '05'), 'bg-secondary': (row.state_type_id === '07'), 'bg-dark': (row.state_type_id === '09')}">{{row.state_type_description}}</span>
                        </td>
                        <td class="text-center">{{ row.currency_type_id }}</td>
                        <td class="text-right">{{ row.total | numberFormat }}</td>
                        <td class="text-center">
                            <span class="badge text-white" :class="{ 'bg-success': (row.electronic), 'bg-primary' : (!row.electronic) }">{{ row.electronic ? 'Electronico' : 'Ticket Papel' }}</span>
                        </td>
                        <td class="text-center" v-if="columns.total_paid.visible">
                            {{row.total_paid | numberFormat }}
                        </td>
                        <td class="text-center" v-if="columns.total_pending_paid.visible">
                            {{row.total_pending_paid | numberFormat }}
                        </td>
                        <td class="text-center">
                            <span class="badge text-white" :class="{'bg-success': (row.paid), 'bg-warning': (!row.paid)}">{{row.paid ? 'Pagado':'Pendiente'}}</span>
                        </td>
                        <!--<td class="text-center">
                            <button type="button" style="min-width: 41px" class="btn waves-effect waves-light btn-xs btn-primary"
                                    @click.prevent="clickPayment(row.id)" ><i class="fas fa-money-bill-alt"></i></button>
                        </td>-->
                        <td class="text-center">
                            <!-- <button type="button" class="btn waves-effect waves-light btn-xs btn-info"
                                    @click.prevent="clickDownload(row.external_id)">PDF</button> -->
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info"
                                    @click.prevent="clickDownload(row.external_id)"><i class="fas fa-file-pdf"></i></button>
                        </td>
                        <td class="text-right" v-if="columns.type_period.visible">
                            {{ row.type_period | period}}
                        </td>
                        <td class="text-right" v-if="columns.quantity_period.visible">
                            {{row.quantity_period}}
                        </td>
                        <td class="text-right" v-if="columns.paid.visible" >
                            {{row.paid ? 'Pagado' : 'Pendiente'}}
                        </td>
                        <td class="text-right">
                            <!-- <button data-toggle="tooltip" data-placement="top" title="Anular" v-if="row.state_type_id != '11'" type="button" class="btn waves-effect waves-light btn-xs btn-danger"
                            @click.prevent="clickVoided(row.id)"><i class="fas fa-trash"></i></button>-->
                            <button  data-toggle="tooltip" data-placement="top" title="Imprimir" v-if="row.state_type_id != '11'"  type="button" class="btn waves-effect waves-light btn-xs btn-info"
                                    @click.prevent="clickOptions(row.id)"><i class="fas fa-print"></i></button>
                            <button v-if="row.state_type_id != '11'" type="button" class="btn waves-effect waves-light btn-xs btn-danger m-1__2"
                                    @click.prevent="clickVoided(row.id)"
                                    >Anular</button>
                            <button v-if="row.has_purchase_coupon" type="button" style="min-width: 41px" class="btn waves-effect waves-light btn-xs btn-info m-1__2" @click.prevent="clickDownloadCoupon(row.id)" title="Descargar cupón">
                            <i class="fas fa-ticket-alt"></i>
                            </button>
                        </td>
                    </tr>
                </data-table>
            </div>
        </div>


        <sale-notes-options :showDialog.sync="showDialogOptions"
                          :recordId="saleNotesNewId"
                          :showClose="true"></sale-notes-options>

        <!-- Modal para escoger tipo de sincronización POS -->
        <el-dialog
            :visible.sync="showSyncDialog"
            width="400px"
            :close-on-click-modal="false"
        >
            <div slot="title">
                <span>
                    <i class="fas fa-sync-alt" style="color:#409EFF;margin-right:10px;"></i>
                    <b>Sincronizar documentos POS</b>
                </span>
            </div>
            <el-alert
                title="Importa documentos POS aceptados en el API que no se encuentran registrados en el sistema."
                type="info"
                show-icon
                :closable="false"
                style="margin-bottom: 15px;"
            ></el-alert>
            <el-form :model="syncForm" label-width="140px" label-position="top">
                <el-form-item label="Tipo de sincronización">
                    <el-radio-group v-model="syncForm.type">
                        <el-radio label="fecha">
                            <i class="el-icon-date"></i> Por Fechas
                        </el-radio>
                        <el-radio label="pagina">
                            <i class="el-icon-document"></i> Por Página
                        </el-radio>
                    </el-radio-group>
                </el-form-item>
                <template v-if="syncForm.type === 'fecha'">
                    <el-form-item label="Desde">
                        <el-date-picker
                            v-model="syncForm.desde"
                            type="date"
                            placeholder="Fecha inicio"
                            style="width: 100%;"
                        ></el-date-picker>
                    </el-form-item>
                    <el-form-item label="Hasta">
                        <el-date-picker
                            v-model="syncForm.hasta"
                            type="date"
                            placeholder="Fecha fin"
                            style="width: 100%;"
                        ></el-date-picker>
                    </el-form-item>
                </template>
                <template v-else>
                    <el-form-item label="Página">
                        <el-input-number
                            v-model="syncForm.page"
                            :min="1"
                            style="width: 100%;"
                        ></el-input-number>
                        <div style="color: #909399; font-size: 0.9em; margin-top: 3px;">
                            Ejemplo: 1 = 10 documentos por pagina.
                        </div>
                    </el-form-item>
                </template>
            </el-form>
            <span slot="footer" class="dialog-footer">
                <el-button @click="showSyncDialog = false" icon="el-icon-close">Cancelar</el-button>
                <el-button
                    type="primary"
                    :loading="sincronizing"
                    @click="clickSincronizeModal"
                    icon="el-icon-refresh"
                >Sincronizar</el-button>
            </span>
        </el-dialog>
        <!-- Fin modal sincronización POS -->

    </div>
</template>

<style>
.custom-loading-class .el-loading-text {
    font-size: 20px; /* Tamaño de letra más grande */
    font-weight: bold; /* Opcional: letra en negrita */
}
.custom-loading-class .el-loading-mask {
    background-color: rgba(0, 0, 0, 0.7) !important; /* Fondo semi-transparente */
}

.custom-loading-class .el-loading-spinner .el-icon-loading {
    font-size: 50px !important; /* Tamaño del spinner */
    color: #007bff !important; /* Color del spinner */
}

.custom-loading-class .el-loading-text {
    font-size: 20px !important; /* Tamaño de letra más grande */
    font-weight: bold !important; /* Letra en negrita */
    color: #007bff !important; /* Color de la letra */
}

</style>

<script>

    import DataTable from '../../../components/DataTableDocumentsPos.vue'
    //import SaleNotePayments from './partials/payments.vue'
    import SaleNotesOptions from './partials/document_pos_options.vue'
    //import SaleNoteGenerate from './partials/option_documents'
    import {deletable} from '../../../mixins/deletable'

    export default {
        props: ['soapCompany'],
        mixins: [deletable],
        components: {DataTable, SaleNotesOptions},
        data() {
            return {
                resource: 'document-pos',
                plate_number: "",
                showDialogPayments: false,
                showDialogOptions: false,
                showDialogGenerate: false,
                saleNotesNewId: null,
                recordId: null,
                showDialogOptions: false,
                columns: {
                    paid: {
                        title: 'Estado de Pago',
                        visible: false
                    },
                    type_period: {
                        title: 'Tipo Periodo',
                        visible: false
                    },
                    quantity_period: {
                        title: 'Cantidad Periodo',
                        visible: false
                    },
                    license_plate:{
                        title: 'Placa',
                        visible: false
                    },
                    total_paid:{
                        title: 'Pagado',
                        visible: false
                    },
                    total_pending_paid:{
                        title: 'Por pagar',
                        visible: false
                    }
                },
                sincronizing: false,
                showSyncDialog: false,
                syncForm: {
                    type: 'fecha',
                    desde: '',
                    hasta: '',
                    page: 1,
                }
            }
        },
        created() {
        },
        filters:{
            period(name)
            {
                let res = ''
                switch(name)
                {
                    case 'month':
                        res = 'Mensual'
                        break
                    case 'year':
                        res = 'Anual'
                        break
                    default:

                        break;
                }

                return res
            }
        },

        async mounted(){
            this.cash_serial_number = localStorage.getItem('plate_number');
        },

        methods: {
            clickDownload(external_id) {
                window.open(`/document-pos/downloadExternal/${external_id}`, '_blank');
            },
            clickOptions(recordId) {
                this.saleNotesNewId = recordId
                this.showDialogOptions = true
            },
            clickGenerate(recordId) {
                this.recordId = recordId
                this.showDialogGenerate = true
            },
            clickPayment(recordId) {
                this.recordId = recordId;
                this.showDialogPayments = true;
            },
            clickCreate(id = '') {
                location.href = `/${this.resource}/create/${id}`
            },

            changeConcurrency(row) {

                // console.log(row)
                this.$http.post(`/${this.resource}/enabled-concurrency`, row).then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this.$eventHub.$emit('reloadData')
                    }
                    else {
                        this.$message.error(response.data.message);
                    }
                }).catch(error => {
                    if (error.response.status === 422) {
                        this.errors = error.response.data.errors;
                    }
                    else {
                        console.log(error);
                    }
                }).then(() => {
                });
            },
            clickVoided(id) {
                this.$confirm('¿Estás seguro de que deseas anular este documento?', 'Confirmar Anulación', {
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'No',
                    type: 'warning'
                }).then(() => {
                    this.$http.get(`/${this.resource}/voided/resolutions`)
                        .then(response => {
                            if (response.data.quantity > 0) {
                                this.anular(id);
                            } else {
                                this.$message.error('No posee resoluciones para anular el documento');
                            }
                        }).catch(error => {
                            console.log(error);
                        }).finally(() => {
                            this.$eventHub.$emit('reloadData');
                        });
                }).catch(() => {
                    this.$message.info('Anulación cancelada');
                });
            },
            anular(id) {
                const loadingInstance = this.$loading({
                    lock: true,
                    text: 'Procesando anulación...',
                    spinner: 'el-icon-loading',
                    background: 'rgba(0, 0, 0, 0.7)',
                    customClass: 'custom-loading-class'
                });

                this.$http.get(`/${this.resource}/anulate/${id}`)
                    .then(response => {
                        loadingInstance.close();
                        if (response.data.success) {
                            this.$message.success(response.data.message);
                            this.$eventHub.$emit('reloadData');
                        } else {
                            this.$message.error(response.data.message);
                        }
                    })
                    .catch(error => {
                        loadingInstance.close();
                        if (error.response) {
                            this.$message.error(error.response.data.message || 'Error al intentar anular');
                        } else {
                            this.$message.error('Error al intentar anular');
                        }
                    });
            },
            clickRefund(id)
            {
                location.href = `/${this.resource}/refund/${id}`
            },
            async clickSincronize() {
                this.sincronizing = true

                await this.$http.get(`/${this.resource}/sincronize`).then(response => {
                    // console.log(response)
                    if (response.data.success) {
                        this.$message.success(response.data.message)
                    }
                    else {
                        this.$message.error(response.data.message)
                    }
                    this.$eventHub.$emit('reloadData')
                }).catch(error => {
                    if (error.response.status === 422) {
                        this.errors = error.response.data
                    }
                    else {
                        this.$message.error(error.response.data.message)
                    }
                }).then(() => {
                    this.sincronizing = false
                })
            },
            openSyncDialog() {
                this.showSyncDialog = true;
            },
            async clickSincronizeModal() {
                this.sincronizing = true;
                let payload = {
                    type: this.syncForm.type,
                };
                if (this.syncForm.type === 'fecha') {
                    payload.desde = this.syncForm.desde;
                    payload.hasta = this.syncForm.hasta;
                } else {
                    payload.page = this.syncForm.page;
                }
                await this.$http.post(`/${this.resource}/sincronize`, payload)
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success(response.data.message)
                        } else {
                            this.$message.error(response.data.message)
                        }
                        this.$eventHub.$emit('reloadData')
                    }).catch(error => {
                        if (error.response && error.response.status === 422) {
                            this.errors = error.response.data
                        } else {
                            this.$message.error(error.response?.data?.message || 'Error en la sincronización')
                        }
                    }).finally(() => {
                        this.sincronizing = false
                        this.showSyncDialog = false
                    })
            },
            clickDownloadCoupon(id) {
                this.$http.get(`/${this.resource}/downloadFileCoupon/${id}`).then((response) => {
                    const res_data = response.data;

                    if (!res_data.success) return this.$message.error(res_data.message);

                    const byteCharacters = atob(res_data.filebase64);
                    const byteNumbers = new Array(byteCharacters.length);
                    for (let i = 0; i < byteCharacters.length; i++) {
                        byteNumbers[i] = byteCharacters.charCodeAt(i);
                    }
                    const byteArray = new Uint8Array(byteNumbers);

                    const file = new Blob([byteArray], { type: 'application/pdf' });
                    const fileURL = URL.createObjectURL(file);
                    window.open(fileURL, '_blank');
                }).catch((err) => {
                    this.$message.error('Error al descargar el cupón');
                    console.error(err);
                });
            }
        }
    }
</script>