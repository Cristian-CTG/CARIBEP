<template>
    <div v-loading="loading">
        <div class="page-header pr-0">
            <h2><a href="/co-documents">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text" style="margin-top: -5px;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Listado de comprobantes</span> </li>
            </ol>
            <div class="right-wrapper pull-right" >
                <a :href="`/${resource}/create`" class="btn btn-custom btn-sm  mt-2 mr-2"><i class="fa fa-plus-circle"></i> Nuevo</a>
                <el-tooltip class="item" effect="dark" content="Importa las facturas con estado Aceptada en el API que no se encuentran registradas" placement="bottom">
                    <button class="btn btn-custom btn-sm  mt-2 mr-2" :loading="Sincronizing" @click.prevent="openSyncDialog"><i class="fas fa-sync-alt" ></i> Sincronizar Envios API</button>
                </el-tooltip>
                <button class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickImport()"><i class="fa fa-arrows-alt" ></i> Carga Masiva</button>
            </div>
        </div>
        <div class="card mb-0">
            <div class="data-table-visible-columns">
                <!-- <el-button class="submit" type="success" @click.prevent="clickDownloadReportPagos('excel')"><i class="fa fa-file-excel" ></i>  Descargar Pagos</el-button> -->
                <!-- <el-dropdown :hide-on-click="false">
                    <el-button type="primary">
                        Mostrar/Ocultar columnas<i class="el-icon-arrow-down el-icon--right"></i>
                    </el-button>
                    <el-dropdown-menu slot="dropdown">
                        <el-dropdown-item v-for="(column, index) in columns" :key="index">
                            <el-checkbox v-model="column.visible">{{ column.title }}</el-checkbox>
                        </el-dropdown-item>
                    </el-dropdown-menu>
                </el-dropdown> -->
            </div>
            <div class="card-body ">
                <data-table :resource="resource" :init-search="initSearch" :extra-filters="true">
                    <tr slot="heading">
                        <!-- <th>#</th> -->
                        <th class="text-center">Fecha Emisión</th>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th>Estado</th>
                        <!-- <th>Acuse recibido cliente</th> -->
                        <th class="text-center">Moneda</th>
                        <th class="text-right">T.Venta</th>
                        <th class="text-right">T.Descuentos</th>
                        <th class="text-right">T.Impuestos</th>
                        <th class="text-right">Subtotal</th>
                        <th class="text-right">Total</th>
                        <th class="text-center"></th>
                        <th class="text-right">Descargas</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                    <tr slot-scope="{ index, row }" >
                        <!-- <td>{{ index }}</td> -->
                        <td class="text-center">{{ row.date_of_issue }}</td>
                        <td>{{ row.customer_name }}<br/><small v-text="row.customer_number"></small></td>
                        <td>{{ row.number_full }}<br/>
                            <small v-text="row.type_document_name"></small><br/>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary text-white" :class="{'bg-secondary': (row.state_document_id === 1), 'bg-success': (row.state_document_id === 5), 'bg-danger': (row.state_document_id === 6)}">
                                {{ row.state_document_name }}
                            </span>
                        </td>
                        <!-- <td class="text-center">{{ row.acknowledgment_received }}</td> -->
                        <td class="text-center">{{ row.currency_name }}</td>

                        <td class="text-right">{{ row.sale | numberFormat }}</td>
                        <td class="text-right">{{ row.total_discount | numberFormat }}</td>
                        <td class="text-right">{{ row.total_tax | numberFormat }}</td>
                        <td class="text-right">{{ row.subtotal | numberFormat }}</td>
                        <td class="text-right">{{ row.total | numberFormat }}</td>

                        <td class="text-center">
                            <template v-if="row.type_document_name !== 'Nota crédito'">
                                <button type="button" style="min-width: 41px" class="btn waves-effect waves-light btn-xs btn-info m-1__2"
                                        @click.prevent="clickPayment(row.id)">Pagos</button>
                            </template>
                        </td>
                        <td class="text-right" >
                            <button type="button" style="min-width: 41px" class="btn waves-effect waves-light btn-xs btn-info m-1__2"
                                    @click.prevent="clickDownload(row.download_xml)"
                                   >XML</button>
                            <button type="button" style="min-width: 41px" class="btn waves-effect waves-light btn-xs btn-info m-1__2"
                                    @click.prevent="clickDownload(row.download_pdf)"
                                   >PDF</button>
                            <button v-if="row.has_coupon" type="button" style="min-width: 41px" class="btn waves-effect waves-light btn-xs btn-info m-1__2"
                                    @click.prevent="clickDownloadCoupon(row.id)"
                                   >Cupon</button>

                        </td>
                        <td class="text-right" >
                            <template v-if="row.btn_query">
                                <el-tooltip class="item" effect="dark" content="Consultar ZIPKEY a la DIAN" placement="top-start">
                                    <button type="button" class="btn waves-effect waves-light btn-xs btn-success" @click.prevent="clickQueryZipKey(row.id)">Consultar</button>
                                </el-tooltip>
                            </template>

                            <template v-if="(row.type_document_name=='Factura de Venta Nacional' || row.type_document_name=='Factura de Exportación' || row.type_document_name=='Factura de Contingencia' || row.type_document_name=='Factura electrónica de Venta - tipo 04') && (row.state_document_id==5 || row.state_document_id==1)">
                                <a :href="`/${resource}/note/${row.id}`" class="btn waves-effect waves-light btn-xs btn-warning m-1__2">Nota</a>
                            </template>

                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info m-1__2"
                                    @click.prevent="clickOptions(row.id)">Opciones</button>

                            <template v-if="(row.type_document_name=='Factura de Venta Nacional' || row.type_document_name=='Factura de Exportación' || row.type_document_name=='Factura de Contingencia' || row.type_document_name=='Factura electrónica de Venta - tipo 04') && row.state_document_id!=6">
                                <a :href="`/${resource}/duplicate-invoice/${row.id}`" class="btn waves-effect waves-light btn-xs btn-info m-1__2">Duplicar</a>
                            </template>

                            <template v-if="row.state_document_id==6">
                                <a :href="`/${resource}/edit-invoice/${row.id}`" class="btn waves-effect waves-light btn-xs btn-info m-1__2">Editar</a>
                            </template>
                        </td>
                    </tr>
                </data-table>
            </div>

            <document-import :showDialog.sync="showImportDialog"></document-import>

            <document-payments :showDialog.sync="showDialogPayments"
                               :documentId="recordId"></document-payments>

            <document-options :showDialog.sync="showDialogOptions"
                              :showDownload="false"
                              :recordId="recordId"
                              :showClose="true"></document-options>

            <!-- Modal para escoger tipo de sincronización -->
            <el-dialog
                :visible.sync="showSyncDialog"
                width="400px"
                :close-on-click-modal="false"
            >
                <div slot="title">
                    <span>
                        <i class="fas fa-sync-alt" style="color:#409EFF;margin-right:10px;"></i>
                        <b>Sincronizar documentos</b>
                    </span>
                </div>
                <el-alert
                    title="Importa facturas aceptadas en el API que no se encuentran registradas en el sistema."
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
                        :loading="Sincronizing"
                        @click="clickSincronize"
                        icon="el-icon-refresh"
                    >Sincronizar</el-button>
                </span>
            </el-dialog>
        </div>
    </div>
</template>

<script>
    import DataTable from '@components/DataTable.vue'
    import DocumentOptions from './partials/options.vue'
    import DocumentPayments from './partials/payments.vue'
    import DocumentImport from './partials/import.vue'
    import {functions} from '@mixins/functions';

    export default {
        components: {DataTable, DocumentOptions, DocumentPayments, DocumentImport},
        mixins: [functions],
        data() {
            return {
                showDialogReportPayment:false,
                showDialogVoided: false,
                showImportDialog: false,
                showDialogCDetraction: false,
                showImportSecondDialog: false,
                resource: 'co-documents',
                recordId: null,
                showDialogOptions: false,
                showDialogPayments: false,
                loading: false,
                Sincronizing: false,
                initSearch: {
                    column: 'date_of_issue',
                    value: this.getCurrentMonth()
                },
                showSyncDialog: false,
                syncForm: {
                    type: 'fecha',
                    desde: '',
                    hasta: '',
                    page: 1,
                },
            }
        },
        created() {
        },
        methods: {
            getCurrentMonth() {
                const date = new Date();
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                return `${year}-${month}`;
            },
            async clickQueryZipKey(recordId) {

                this.loading = true

                await this.$http.post(`/${this.resource}/query-zipkey`, {
                    id : recordId
                }).then(response => {
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
                    this.loading = false
                })
            },

            clickImport() {
                this.showImportDialog = true;
            },

            openSyncDialog() {
                this.showSyncDialog = true;
            },
            async clickSincronize() {
                this.Sincronizing = true;
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
                        this.Sincronizing = false
                        this.showSyncDialog = false
                    })
            },

            clickPayment(recordId) {
                this.recordId = recordId;
                this.showDialogPayments = true;
            },
            clickVoided(recordId = null) {
                this.recordId = recordId
                this.showDialogVoided = true
            },
            clickDownload(download) {
                console.log(download)
                console.log(this.downloadFilename(download))
                this.$http.get(`/${this.resource}/downloadFile/${this.downloadFilename(download)}`).then((response) => {

                    let res_data = response.data
                    if(!res_data.success) return this.$message.error(res_data.message)

                    var byteCharacters = atob(response.data.filebase64);
                    var byteNumbers = new Array(byteCharacters.length);
                    for (var i = 0; i < byteCharacters.length; i++) {
                        byteNumbers[i] = byteCharacters.charCodeAt(i);
                    }
                    var byteArray = new Uint8Array(byteNumbers);
                    if(download.indexOf("PDF") >= 0 || download.indexOf("pdf") >= 0)
                      var file = new Blob([byteArray], { type: 'application/pdf;base64' });
                    else
                      var file = new Blob([byteArray], { type: 'application/xml;base64' });
                    var fileURL = URL.createObjectURL(file);
                    window.open(fileURL, '_blank');
                })
//                window.open(download, '_blank');
            },
            clickOptions(recordId = null) {
                this.recordId = recordId
                this.showDialogOptions = true
            },
            downloadFilename(filename){
              c = ""
              for(var i = filename.length - 1; i >= 0; i--){
                  if(filename.substring(i, i + 1) != "/"){
                    c = c + filename.substring(i, i + 1)
                  }
                  else
                    return c.split('').reverse().join('');
              }
              return c.split('').reverse().join('');
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
