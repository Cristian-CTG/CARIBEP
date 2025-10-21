<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/purchases">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-bag" style="margin-top: -5px;">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z"></path>
                    <path d="M9 11v-5a3 3 0 0 1 6 0v5"></path>
                </svg>
            </a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Compras</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <a :href="`/${resource}/create`" class="btn btn-custom btn-sm  mt-2 mr-2"><i class="fa fa-plus-circle"></i> Nuevo</a>
                <!-- <button   @click.prevent="clickImport()" type="button" class="btn btn-custom btn-sm  mt-2 mr-2" ><i class="fa fa-upload"></i> Importar</button> -->

            </div>
        </div>
        <div class="card mb-0">
            <div class="data-table-visible-columns">
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
            <div class="card-body">
                <data-table :resource="resource" :init-search="initSearch">
                    <tr slot="heading">
                        <!-- <th>#</th> -->
                        <th class="text-left">F. Emisión</th>
                        <th class="text-center" v-if="columns.date_of_due.visible" >F. Vencimiento</th>
                        <th>Proveedor</th>
                        <th>Estado</th>
                        <th>Estado de pago</th>
                        <th>Número</th>
                        <th v-if="columns.affected_document.visible">Documento Afectado</th>
                        <th>Productos</th>
                        <th>Pagos</th>
                        <!-- <th>F. Pago</th> -->
                        <!-- <th>Estado</th> -->
                        <th class="text-center">Moneda</th>
                        <!-- <th class="text-right">T.Exportación</th> -->
                        <th v-if="columns.total_perception.visible" >Percepcion</th>
                        <th class="text-right">Total</th>
                        <!-- <th class="text-center">Descargas</th> -->
                        <th class="text-right">Acciones</th>
                    </tr>
                    <tr slot-scope="{ index, row }">
                        <!-- <td>{{ index }}</td> -->
                        <td class="text-left">{{ row.date_of_issue }}</td>
                        <td v-if="columns.date_of_due.visible" class="text-center">{{ row.date_of_due }}</td>
                        <td>{{ row.supplier_name }}<br/><small v-text="row.supplier_number"></small></td>
                        <td>{{row.state_type_description}}</td>
                        <td>{{row.state_type_payment_description}}</td>
                        <td>{{ row.number }}<br/>
                            <small v-text="row.document_type_description"></small><br/>
                        </td>
                        <td v-if="columns.affected_document.visible">{{ row.affected_document }}</td>
                        <td>

                            <el-popover
                                placement="right"
                                width="400"
                                trigger="click">
                                <el-table :data="row.items">
                                    <el-table-column width="80" property="key" label="#"></el-table-column>
                                    <el-table-column width="220" property="name" label="Nombre"></el-table-column>
                                    <el-table-column width="90" property="quantity" label="Cantidad"></el-table-column>
                                </el-table>
                                <el-button slot="reference"> <i class="fa fa-eye"></i></el-button>
                            </el-popover>

                        </td>
                        <!-- <td>{{ row.payment_method_type_description }}</td> -->
                        <!-- <td>
                            <template v-for="(it,ind) in row.payments">
                                {{it.payment_method_type_description}} - {{it.payment}}
                            </template>
                        </td> -->
                        <!-- <td>{{ row.state_type_description }}</td> -->
                        <td class="text-right">
                            <button
                                v-if="row.state_type_id != '11' && String(row.document_type_id) !== '07'"
                                type="button"
                                style="min-width: 41px"
                                class="btn waves-effect waves-light btn-xs btn-info m-1__2"
                                @click.prevent="clickPurchasePayment(row.id)"
                            >Pagos</button>
                        </td>

                        <td class="text-center">{{ row.currency_type_id }}</td>
                        <!-- <td class="text-right">{{ row.total_exportation }}</td> -->
                        <td v-if="columns.total_perception.visible" class="text-right">{{ row.total_perception ? row.total_perception : 0 | numberFormat }}</td>
                        <td class="text-right">{{ row.total | numberFormat }}</td>
                        <td class="text-right">
                            <el-dropdown trigger="click" @command="handleDropdownCommand">
                                <el-button size="mini" type="secondary" class="btn btn-default btn-sm btn-dropdown-toggle">
                                    <i class="fas fa-ellipsis-h"></i>
                                </el-button>
                            
                                <el-dropdown-menu slot="dropdown">
                                
                                    <el-dropdown-item 
                                        v-if="row.state_type_id != '11'" 
                                        :command="{action: 'edit', id: row.id}">
                                        Editar
                                    </el-dropdown-item>
                                
                                    <el-dropdown-item 
                                        v-if="row.state_type_id != '11' && !['07', '08'].includes(String(row.document_type_id))" 
                                        :command="{action: 'note', id: row.id}">
                                        Nota
                                    </el-dropdown-item>
                                
                                    <el-dropdown-item 
                                        :command="{action: 'pdf', id: row.id}">
                                        PDF
                                    </el-dropdown-item>                            

                                    <el-dropdown-item :command="{action: 'labels', row: row}">
                                        <span class="dropdown-item-content">
                                            Etiquetas
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-printer text-muted"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="5" y="13" width="14" height="8" rx="2" /><polyline points="17 17 17 17.01" /><rect x="7" y="3" width="10" height="6" rx="2" /><path d="M17 7v4a2 2 0 0 1 2 2" /><path d="M7 7v4a2 2 0 0 0 -2 2" /></svg>
                                        </span>
                                    </el-dropdown-item>
                                
                                    <el-dropdown-item 
                                        v-if="row.state_type_id != '11'" 
                                        :command="{action: 'anulate', id: row.id}">
                                        Anular
                                    </el-dropdown-item>
                                
                                    <el-dropdown-item 
                                        v-if="row.state_type_id == '11'" 
                                        :command="{action: 'delete', id: row.id}"
                                        class="text-danger">
                                        Eliminar
                                    </el-dropdown-item>
                                
                                </el-dropdown-menu>
                            </el-dropdown>
                        </td>
                    </tr>
                </data-table>
            </div>

            <!-- <documents-voided :showDialog.sync="showDialogVoided"
                            :recordId="recordId"></documents-voided>

            <document-options :showDialog.sync="showDialogOptions"
                              :recordId="recordId"
                              :showClose="true"></document-options> -->

            <purchase-import :showDialog.sync="showImportDialog"></purchase-import>
        </div>
        <barcode-config
            :show.sync="showBarcodeConfig"
            :itemId="barcodeItemIds"
            :stock="barcodeItemQuantities"
            :fromPurchase="fromPurchase"
            @print-purchase-labels="handlePrintPurchaseLabels">
        </barcode-config>


        <purchase-payments
            :showDialog.sync="showDialogPurchasePayments"
            :purchaseId="recordId"
            :external="true"
            ></purchase-payments>
    </div>
</template>
<script>

    // import DocumentsVoided from './partials/voided.vue'
    // import DocumentOptions from './partials/options.vue'
    import DataTable from '../../../components/DataTable.vue'
    import {deletable} from '../../../mixins/deletable'
    import PurchaseImport from './import.vue'
    import PurchasePayments from '@viewsModulePurchase/purchase_payments/payments.vue'
    import BarcodeConfig from '../items/barcode-config.vue'


    export default {
        mixins: [deletable],
        // components: {DocumentsVoided, DocumentOptions, DataTable},
        components: {DataTable, PurchaseImport, PurchasePayments, BarcodeConfig},
        data() {
            return {
                showDialogVoided: false,
                resource: 'purchases',
                recordId: null,
                showDialogOptions: false,
                showDialogPurchasePayments: false,
                showImportDialog: false,
                showBarcodeConfig: false,
                barcodeItemQuantities: [],
                fromPurchase: false,
                barcodeItemIds: [],
                initSearch: {
                    column: 'date_of_issue',
                    value: this.getCurrentMonth()
                },
                columns: {
                    date_of_due: {
                        title: 'F. Vencimiento',
                        visible: false
                    },
                    // total_free: {
                    //     title: 'T.Gratuita',
                    //     visible: false
                    // },
                    // total_unaffected: {
                    //     title: 'T.Inafecta',
                    //     visible: false
                    // },
                    // total_exonerated: {
                    //     title: 'T.Exonerado',
                    //     visible: false
                    // },
                    // total_taxed: {
                    //     title: 'T.Gravado',
                    //     visible: false
                    // },
                    // total_igv: {
                    //     title: 'T.Igv',
                    //     visible: false
                    // },
                    total_perception:{
                        title: 'Percepcion',
                        visible: false
                    },
                    affected_document: {
                        title: 'Documento Afectado',
                        visible: false
                    }

                }
            }
        },
        created() {
        },
        methods: {
            printPurchaseLabels(row) {
                const items = (row.items || []).filter(it => it.item_id && it.quantity > 0);
                if (items.length === 0) {
                    this.$message.warning('No hay productos con cantidad mayor a 0 en esta compra.');
                    return;
                }
                this.barcodeItemIds = items.map(it => it.item_id);
                this.barcodeItemQuantities = items.map(it => parseInt(it.quantity) || 1);
                this.fromPurchase = true;
                this.showBarcodeConfig = true;
            },
            handlePrintPurchaseLabels({ width, height, pageWidth, columns, gapX, fields }) {
                // Construye la URL y abre la impresión
                const ids = this.barcodeItemIds;
                const repeat = this.barcodeItemQuantities.join(',');
                const params = new URLSearchParams({
                    width,
                    height,
                    pageWidth,
                    columns,
                    gapX,
                    repeat,
                    ...fields,
                }).toString();
                const url = `/items/barcodes?ids=${ids.join(',')}&${params}`;
                window.open(url, '_blank');
                this.showBarcodeConfig = false;
            },
            openBarcodeConfigForPurchase(row) {
                    console.log(row.items);
                    // Extrae los IDs de los productos de la compra
                    this.barcodeItemIds = (row.items || []).map(it => it.item_id).filter(Boolean);
                    if (this.barcodeItemIds.length === 0) {
                        this.$message.warning('No hay productos en esta compra.');
                        return;
                    }
                    this.showBarcodeConfig = true;
                },
            clickPurchasePayment(recordId) {
                this.recordId = recordId;
                this.showDialogPurchasePayments = true
            },
            clickVoided(recordId = null) {
                this.recordId = recordId
                this.showDialogVoided = true
            },
            clickDownload(download) {
                window.open(download, '_blank');
            },
            clickOptions(recordId = null) {
                this.recordId = recordId
                this.showDialogOptions = true
            },
            clickAnulate(id)
            {
                this.anular(`/${this.resource}/anular/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            clickDelete(id)
            {
                this.delete(`/${this.resource}/delete/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
             clickImport() {
                this.showImportDialog = true
            },
            handleDropdownCommand(command) {
                switch (command.action) {
                    case 'edit':
                        window.location.href = `/${this.resource}/edit/${command.id}`;
                        break;
                    case 'note':
                        window.location.href = `/${this.resource}/note/${command.id}`;
                        break;
                    case 'pdf':
                        window.open(`/${this.resource}/pdf/${command.id}`, '_blank');
                        break;
                    case 'anulate':
                        this.clickAnulate(command.id);
                        break;
                    case 'delete':
                        this.clickDelete(command.id);
                        break;
                    case 'labels':
                        this.printPurchaseLabels(command.row);
                        break;
                    default:
                        break;
                }
            },
            getCurrentMonth() {
                const date = new Date();
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                return `${year}-${month}`;
            }
        }
    }
</script>
