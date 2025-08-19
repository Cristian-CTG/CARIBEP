<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/co-radian-events/manage">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-check" style="margin-top: -5px;"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M11.5 21h-5.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v6"></path><path d="M16 3v4"></path><path d="M8 3v4"></path><path d="M4 11h16"></path><path d="M15 19l2 2l4 -4"></path></svg>
            </a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Documentos recibidos</span></li>
            </ol>
            <div class="right-wrapper pull-right">
            </div>
        </div>
        <div class="card mb-0" v-loading="loading">
            <!-- <div class="card-header bg-info">
                <h3 class="my-0">Eventos RADIAN - Documentos recibidos</h3>
            </div> -->
            <div class="card-body">
                <data-table :resource="resource">
                    <tr slot="heading">
                        <!-- <th>#</th> -->
                        <th>Tipo Documento</th>
                        <th>Fecha</th>
                        <th>Nit Empresa</th>
                        <th>Nombre</th>
                        <th>Prefijo</th>
                        <th>Numero</th>
                        <th>Impuestos</th>
                        <th>Vr. Documento</th>
                        <th>Attached Document</th>
                        <th>PDF</th>
                        <th>Acuse Recibo</th>
                        <th>Recepcion Bienes</th>
                        <th>Aceptacion Expresa</th>
                        <th>Rechazo</th>
                        <th>Estado Actual</th>
                        <th>Historial</th>
                    </tr>
                    <tr slot-scope="{ index, row }">
                        <!-- <td>{{ index }}</td> -->

                        <td>{{ row.type_document_name }}</td>

                        <td>{{ row.date_issue }}</td>
                        <td>{{ row.identification_number }}</td>
                        <td>{{ row.name_seller }}</td>
                        <td>{{ row.prefix }}</td>
                        <td>{{ row.number }}</td>
                        <td>{{ row.total_tax }}</td>
                        <td>{{ row.total }}</td>
                        <td>
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-success" @click.prevent="clickDownload(row.xml)">
                                <i class="fas fa-download"></i>
                            </button>
                        </td>
                        <td>
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickDownload(row.pdf)">
                                <i class="fas fa-download"></i>
                            </button>
                        </td>

                        <td>
                            <!-- Acuse Recibo -->

                            <el-tooltip class="item" effect="dark" content="Documento electrónico por el cual el Adquiriente manifiesta que ha recibido la factura electrónica, de conformidad con el artículo 774 del Código de Comercio." placement="bottom-end">
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-primary" @click.prevent="clickSendEvent(row.id, '1')" :disabled="row.acu_recibo == 1">
                                    <i class="fas fa-file-import"></i>
                                </button>
                            </el-tooltip>

                            <!-- Acuse Recibo -->

                        </td>
                        <td>

                            <!-- Recepcion Bienes -->

                            <el-tooltip class="item" effect="dark" content="Documento electrónico por el cual el Adquiriente informa del recibo de los bienes o servicios adquiridos, de conformidad con el artículo 773 del Código de Comercio y en concordancia con el parágrafo 1 del artículo 2.2.2.53.4. del Decreto 1074 de 2015 Único Reglamentario del Sector Comercio, Industria y Turismo." placement="bottom-end">
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-primary" @click.prevent="clickSendEvent(row.id, '3')" :disabled="row.rec_bienes == 1">
                                    <i class="fas fa-file-import"></i>
                                </button>
                            </el-tooltip>

                            <!-- Recepcion Bienes -->

                        </td>
                        <td>
                            <!-- Aceptacion Expresa -->

                            <el-tooltip class="item" effect="dark" content="Documento electrónico por el cual el Adquiriente informa al Emisor que acepta expresamente el Documento Electrónico que origina este tipo de ApplicationResponse de conformidad con el artículo 773 del Código de Comercio y en concordancia con el numeral 1 del artículo 2.2.2.53.4. del Decreto 1074 de 2015, Único Reglamentario del Sector Comercio, Industria y Turismo." placement="bottom-end">
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-primary" @click.prevent="clickSendEvent(row.id, '4')" :disabled="row.aceptacion == 1">
                                    <i class="fas fa-file-import"></i>
                                </button>
                            </el-tooltip>

                            <!-- Aceptacion Expresa -->
                        </td>
                        <td>
                            <!-- Rechazo -->

                            <el-tooltip class="item" effect="dark" content="Documento electrónico mediante el cual el Adquiriente manifiesta que no acepta el documento de conformidad con el artículo 773 del Código de Comercio y en concordancia con el artículo 2.2.2.53.4. del Decreto 1074 de 2015, Único Reglamentario del Sector Comercio, Industria y Turismo. Este documento es para desaveniencias de tipo comercial, dado que el documento sobre el cual manifiesta el desacuerdo fue efectivamente Validado por la DIAN, en el sistema de Validación Previa, Nota: Se debe solicitar una nota contable al emisor." placement="bottom-end">
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-primary" @click.prevent="clickRejected(row)" :disabled="row.rechazo == 1">
                                    <i class="fas fa-file-import"></i>
                                </button>
                            </el-tooltip>

                            <!-- Rechazo -->
                        </td>
                        <td>
                            <el-tooltip effect="dark" placement="top">
                                <div slot="content">
                                    <template v-if="row.aceptacion == 1">Aceptación expresa</template>
                                    <template v-else-if="row.rechazo == 1">Rechazado</template>
                                    <template v-else-if="row.rec_bienes == 1">Recepción de bienes</template>
                                    <template v-else-if="row.acu_recibo == 1">Acuse de recibo</template>
                                    <template v-else>Sin acciones</template>
                                </div>
                                <template v-if="row.aceptacion == 1">
                                    <i class="fa fa-circle" style="color: green"></i>
                                </template>
                                <template v-else>
                                    <template v-if="row.rechazo == 1">
                                        <i class="fa fa-circle" style="color: red"></i>
                                    </template>
                                    <template v-else>
                                        <template v-if="row.rec_bienes == 1">
                                            <i class="fa fa-circle" style="color: yellow"></i>
                                        </template>
                                        <template v-else>
                                            <template v-if="row.acu_recibo == 1">
                                                <i class="fa fa-circle" style="color: blue"></i>
                                            </template>
                                            <template v-else>
                                                <i class="fa fa-circle" style="color: black"></i>
                                            </template>
                                        </template>
                                    </template>
                                </template>
                            </el-tooltip>
                        </td>
                        <td>
                            <template v-if="row.aceptacion == 1 || row.rechazo == 1 || row.rec_bienes == 1 || row.acu_recibo == 1">
                                <el-button type="text" @click="showHistory(row)">
                                    <i class="fas fa-history"></i>
                                </el-button>
                            </template>
                        </td>
                    </tr>
                </data-table>

            </div>
        </div>

        <rejected-form :showDialog.sync="showDialogRejected"
                            :record="record"></rejected-form>
        <!-- Nuevo componente para el historial -->
        <el-dialog
            title="Historial de Acciones"
            :visible.sync="showHistoryDialog"
            width="70%">
            <div v-if="selectedRecord">
                <el-timeline>
                    <el-timeline-item v-if="selectedRecord.acu_recibo" 
                        timestamp="Acuse de Recibo" 
                        type="primary">
                        <p>CUDE: {{ getCudeFromResponse('response_acu_recibo') }}</p>
                    </el-timeline-item>
                    
                    <el-timeline-item v-if="selectedRecord.rec_bienes"
                        timestamp="Recepción de Bienes"
                        type="warning">
                        <p>CUDE: {{ getCudeFromResponse('response_rec_bienes') }}</p>
                    </el-timeline-item>

                    <el-timeline-item v-if="selectedRecord.aceptacion"
                        timestamp="Aceptación Expresa"
                        type="success">
                        <p>CUDE: {{ getCudeFromResponse('response_aceptacion') }}</p>
                    </el-timeline-item>

                    <el-timeline-item v-if="selectedRecord.rechazo"
                        timestamp="Rechazo"
                        type="danger">
                        <p>CUDE: {{ getCudeFromResponse('response_rechazo') }}</p>
                    </el-timeline-item>
                </el-timeline>
            </div>
        </el-dialog>
    </div>
</template>
<script>

    import DataTable from '@components/DataTable.vue'
    import RejectedForm from './partials/rejected.vue'

    export default {
        components: {DataTable, RejectedForm},
        data() {
            return {
                resource: 'co-radian-events',
                loading: false,
                showDialogRejected: false,
                record: null,
                showHistoryDialog: false,
                selectedRecord: null,
            }
        },
        created() {
        },
        methods: {
            clickDownload(filename) {
                window.open(`/radian/public-download/${filename}`, '_blank');
            },
            clickRejected(record)
            {
                this.record = record
                this.showDialogRejected = true
            },
            clickSendEvent(id, event_code)
            {
                this.loading = true

                const form = {
                    id: id,
                    event_code: event_code,
                }

                this.$http.post(`/${this.resource}/run-event`, form)
                    .then(response => {
                        console.log(response)
                        if (response.data.success) {
                            this.$message.success(response.data.message)
                            this.$eventHub.$emit('reloadData')
                        } else {
                            this.$message.error(response.data.message)
                        }
                    })
                    .catch(error => {
                        // if (error.response.status === 422) {
                        //     this.errors = error.response.data
                        // } else {
                            console.log(error)
                        // }
                    })
                    .then(() => {
                        this.loading = false
                    })
            },
            showHistory(record) {
                this.selectedRecord = record
                this.showHistoryDialog = true
            },
            getCudeFromResponse(responseString) {
                const record = this.selectedRecord;
                switch(true) {
                    case responseString === 'response_acu_recibo':
                        return record.cude_acu_recibo || 'No disponible';
                    case responseString === 'response_rec_bienes':
                        return record.cude_rec_bienes || 'No disponible';
                    case responseString === 'response_aceptacion':
                        return record.cude_aceptacion || 'No disponible';
                    case responseString === 'response_rechazo':
                        return record.cude_rechazo || 'No disponible';
                    default:
                        return 'No disponible';
                }
            }
        }
    }
</script>
