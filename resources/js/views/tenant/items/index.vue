<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/items">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-category-2" style="margin-top: -5px;">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M14 4h6v6h-6z"></path>
                    <path d="M4 14h6v6h-6z"></path>
                    <path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                    <path d="M7 7m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                </svg>
            </a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Productos</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <template v-if="typeUser === 'admin'">
                    <!-- <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickImportListPrice()"><i class="fa fa-upload"></i> Importar L. Precios</button> -->
                    <button type="button" class="btn btn-danger btn-sm  mt-2 mr-2" @click.prevent="clickDeleteAll()"><i class="fa fa-trash"></i> Eliminar</button>
                    <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickExport()"><i class="fa fa-upload"></i> Exportar</button>
                    <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickImport()"><i class="fa fa-download"></i> Importar</button>
                    <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickCreate()"><i class="fa fa-plus-circle"></i> Nuevo</button>
                </template>
            </div>
        </div>
        <div class="card mb-0">
            <!-- <div class="card-header bg-info">
                <h3 class="my-0">Listado de productos</h3>
            </div> -->
            <div class="card-body">
                <data-table :resource="resource" ref="dataTable">
                    <tr slot="heading" width="100%">
                        <!-- <th>ID</th> -->
                        <th v-if="selectingBarcodes">
                            <el-checkbox
                                class="hide-label-checkbox"
                                v-model="selectAll"
                                @change="toggleSelectAll"
                                :indeterminate="isIndeterminate"
                            ></el-checkbox>
                        </th>
                        <th>#</th>
                        <th>Cód. Interno</th>
                        <th>Unidad</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <!-- <th>Cód. SUNAT</th> -->
                        <th class="text-left">Stock (Actual)</th> 
                        <th  class="text-left">Stock (Total)</th>
                        <th  class="text-right">P.Unitario (Venta)</th>
                        <th v-if="typeUser != 'seller'" class="text-right">P.Unitario (Compra)</th>
                        <!-- <th class="text-center">Tiene Igv</th> -->
                        <th class="text-right">Acciones</th>
                        </tr>
                        <template slot-scope="{ index, row }">
                          <el-tooltip
                            class="row-tooltip"
                            effect="dark"
                            :content="`Stock actual: ${row.stock}`"
                            placement="top"
                            :open-delay="200"
                          >
                        <tr :class="{ disable_color : !row.active }">
                            <td v-if="selectingBarcodes">
                                <el-checkbox
                                v-model="selectedItems"
                                :label="row.id"
                                class="hide-label-checkbox"
                                ></el-checkbox>
                            </td>
                            <td>{{ row.id }}</td>                            
                            <td>{{ row.internal_id }}</td>
                            <td>{{ row.unit_type_id }}</td>
                            <td>{{ row.name }}</td>
                            <td>{{ row.description }}</td>
                        <!-- <td>{{ row.item_code }}</td> -->
                            <td>
                                {{ formatStock(row.stock) }}
                            </td>
                            <td>
                                <div v-if="config.product_only_location == true">
                                    {{ row.stock }}
                                </div>
                                <div v-else>
                                    <template v-if="typeUser=='seller' && row.unit_type_id !='ZZ'">{{ row.stock }}</template>
                                    <template v-else-if="typeUser!='seller' && row.unit_type_id !='ZZ'">
                                        <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickWarehouseDetail(row.warehouses)"><i class="fa fa-search"></i></button>
                                    </template>
                                </div>
                            </td>
                            <td class="text-right">{{ row.sale_unit_price | numberFormat }}</td>
                            <td class="text-right" v-if="typeUser != 'seller'">{{ row.purchase_unit_price | numberFormat }}</td>
                            <td class="text-right">
                                <template v-if="typeUser === 'admin'">
                                    <el-dropdown trigger="click">
                                        <el-button type="secondary" size="mini" class="btn btn-default btn-sm btn-dropdown-toggle">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </el-button>
                                        <el-dropdown-menu slot="dropdown" class="dropdown-actions">
                                            <el-dropdown-item @click.native="clickCreate(row.id)">
                                                <span class="dropdown-item-content">
                                                    Editar
                                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-edit text-muted"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                                                </span>
                                            </el-dropdown-item>                                        
                                            <el-dropdown-item @click.native="duplicate(row.id)">
                                                <span class="dropdown-item-content">
                                                    Duplicar
                                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-copy text-muted"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg>
                                                </span>
                                            </el-dropdown-item>
                                            <el-dropdown-item v-if="row.active" @click.native="clickDisable(row.id)">
                                                <span class="dropdown-item-content">
                                                    Inhabilitar
                                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-ban text-muted"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M5.7 5.7l12.6 12.6" /></svg>
                                                </span>
                                            </el-dropdown-item>
                                            <el-dropdown-item v-else @click.native="clickEnable(row.id)">
                                                <span class="dropdown-item-content">
                                                    Habilitar
                                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-check text-muted"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                                </span>
                                            </el-dropdown-item>
                                            <el-dropdown-item @click.native="clickBarcode(row)">
                                                <span class="dropdown-item-content">
                                                    Cod. barras
                                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-barcode text-muted"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7v-1a2 2 0 0 1 2 -2h2" /><path d="M4 17v1a2 2 0 0 0 2 2h2" /><path d="M16 4h2a2 2 0 0 1 2 2v1" /><path d="M16 20h2a2 2 0 0 0 2 -2v-1" /><path d="M5 11h1v2h-1z" /><path d="M10 11l0 2" /><path d="M14 11h1v2h-1z" /><path d="M19 11l0 2" /></svg>
                                                </span>
                                            </el-dropdown-item>
                                            <el-dropdown-item @click.native="clickBarcodeConfig(row)">
                                                <span class="dropdown-item-content">
                                                    Etiquetas
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-printer text-muted"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="5" y="13" width="14" height="8" rx="2" /><polyline points="17 17 17 17.01" /><rect x="7" y="3" width="10" height="6" rx="2" /><path d="M17 7v4a2 2 0 0 1 2 2" /><path d="M7 7v4a2 2 0 0 0 -2 2" /></svg>
                                                </span>
                                            </el-dropdown-item>

                                            <el-dropdown-item divided></el-dropdown-item>

                                            <el-dropdown-item @click.native="clickDelete(row.id)">
                                                <span class="dropdown-item-content text-danger">
                                                    Eliminar
                                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash text-danger"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                                </span>
                                            </el-dropdown-item>
                                        </el-dropdown-menu>
                                    </el-dropdown>
                                </template>
                            </td>
                        </tr>
                      </el-tooltip>
                    </template>
                </data-table>
                <div class="mt-3 text-right">
                    <button
                        v-if="!selectingBarcodes"
                        type="button"
                        class="btn btn-primary btn-sm"
                        @click.prevent="startSelectingBarcodes"
                    >
                        <i class="fa fa-barcode"></i> Seleccionar para imprimir etiquetas
                    </button>
                    <button
                        v-else
                        type="button"
                        class="btn btn-danger btn-sm"
                        @click.prevent="cancelSelectingBarcodes"
                    >
                        <i class="fa fa-times"></i> Cancelar selección
                    </button>
                    <button
                        v-if="selectingBarcodes"
                        type="button"
                        class="btn btn-success btn-sm ml-2"
                        :disabled="selectedItems.length === 0"
                        @click.prevent="openBarcodeConfigModal"
                    >
                        <i class="fa fa-cog"></i> Configurar e imprimir etiquetas Seleccionadas ({{ selectedItems.length }})
                    </button>
                </div>
            </div>
            <barcode-config
                :show.sync="showBarcodeConfig"
                :itemId="barcodeItemId"
                :stock="selectedStock">
            </barcode-config>

            <items-form :showDialog.sync="showDialog"
                        :recordId="recordId"></items-form>

            <items-import :showDialog.sync="showImportDialog"></items-import>

            <warehouses-detail
                :showDialog.sync="showWarehousesDetail"
                :warehouses="warehousesDetail">
            </warehouses-detail>

            <items-import-list-price :showDialog.sync="showImportListPriceDialog"></items-import-list-price>

        </div>
    </div>
</template>
<style>
.dropdown-item-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    min-width: 120px;
}
.row-tooltip > .el-tooltip__rel { display: contents; }
</style>
<script>

    import ItemsForm from './form.vue'
    import WarehousesDetail from './partials/warehouses.vue'
    import ItemsImport from './import.vue'
    import ItemsImportListPrice from './partials/import_list_price.vue'
    import DataTable from '../../../components/DataTable.vue'
    import {deletable} from '../../../mixins/deletable'
    import {functions} from '@mixins/functions'
    import BarcodeConfig from './barcode-config.vue';

    export default {
        props:['typeUser'],
        mixins: [deletable, functions],
        components: {ItemsForm, ItemsImport, DataTable, WarehousesDetail, ItemsImportListPrice, BarcodeConfig},
        data() {
            return {
                showDialog: false,
                showImportDialog: false,
                showImportListPriceDialog: false,
                showWarehousesDetail: false,
                resource: 'items',
                recordId: null,
                warehousesDetail:[],
                config: {},
                selectedItems: [],
                selectingBarcodes: false,
                selectAll: false,
                isIndeterminate: false,
                items: [],
                showBarcodeConfig: false,
                barcodeItemId: null,
                selectedStock: null,
            }
        },
        created() {
            this.$http.get(`/configurations/record`) .then(response => {
                this.config = response.data.data
            })
        },
        watch: {
            selectedItems(newVal) {
                const visibleRecords = this.$refs.dataTable.records || [];
                if (newVal.length === 0) {
                    this.selectAll = false;
                    this.isIndeterminate = false;
                } else if (newVal.length === visibleRecords.length) {
                    this.selectAll = true;
                    this.isIndeterminate = false;
                } else {
                    this.selectAll = false;
                    this.isIndeterminate = true;
                }
            }
        },
        methods: {
            formatStock(value) {
                if (value == null) return '0.00'
                return parseFloat(value).toFixed(2)
            },
            startSelectingBarcodes() {
                this.selectingBarcodes = true;
                this.selectedItems = [];
            },
            cancelSelectingBarcodes() {
                this.selectingBarcodes = false;
                this.selectedItems = [];
            },
            openBarcodeConfigModal() {
                if (this.selectedItems.length === 0) {
                    this.$message.warning('Seleccione al menos un producto.');
                    return;
                }
                // Obtén todos los productos seleccionados (sin filtrar por stock)
                const visibleRecords = this.$refs.dataTable.records || [];
                const selectedRows = this.selectedItems
                    .map(id => visibleRecords.find(r => r.id === id))
                    .filter(item => !!item);

                this.barcodeItemId = selectedRows.map(item => item.id);
                this.selectedStock = selectedRows.map(item => parseFloat(item.stock));
                this.showBarcodeConfig = true;
            },
            clickBarcodeConfig(row) {
                if(!row.internal_id){
                    return this.$message.error('Para generar el código de barras debe registrar el código interno.');
                }
                this.barcodeItemId = row.id;
                this.selectedStock = parseFloat(row.stock);
                this.showBarcodeConfig = true;
            },
            downloadBarcodePng(row) {
                if(!row.internal_id){
                    return this.$message.error('Para generar el código de barras debe registrar el código interno.');
                }
                window.open(`/items/barcode/${row.id}`, '_blank');
            },
            previewBarcode(row) {
                if(!row.internal_id){
                    return this.$message.error('Para generar el código de barras debe registrar el código interno.');
                }
                window.open(`/items/barcode-label/${row.id}`, '_blank');
            },
            toggleSelectAll(val) {
                // Selecciona solo los productos visibles en la página actual
                const visibleRecords = this.$refs.dataTable.records || [];
                if (val) {
                    this.selectedItems = visibleRecords.map(item => item.id);
                } else {
                    this.selectedItems = [];
                }
                this.isIndeterminate = false;
            },
            duplicate(id)
            {
                this.$http.post(`${this.resource}/duplicate`, {id})
                .then(response => {
                    if (response.data.success) {
                        this.$message.success('Se guardaron los cambios correctamente.')
                        this.$eventHub.$emit('reloadData')
                    } else {
                        this.$message.error('No se guardaron los cambios')
                    }
                })
                .catch(error => {

                })
                this.$eventHub.$emit('reloadData')
            },
            clickWarehouseDetail(warehouses){
                this.warehousesDetail = warehouses
                this.showWarehousesDetail = true
            },
            clickCreate(recordId = null) {
                this.recordId = recordId
                this.showDialog = true
            },
            clickExport() {
                window.open(`/${this.resource}/co-export`, '_blank');
            },
            clickImport() {
                this.showImportDialog = true
            },
            clickImportListPrice() {
                this.showImportListPriceDialog = true
            },
            clickDelete(id) {
                this.destroy(`/${this.resource}/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            clickDisable(id)
            {
                this.disable(`/${this.resource}/disable/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            clickEnable(id){
                this.enable(`/${this.resource}/enable/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            // clickBarcode(row) {

            //     if(!row.internal_id){
            //         return this.$message.error('Para generar el código de barras debe registrar el código interno.')
            //     }

            //     window.open(`/${this.resource}/barcode/${row.id}`)
            // },
            clickDeleteAll(){

                this.destroyAll(`/${this.resource}/delete/all`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )

            },
        }
    }
</script>
<style scoped>
.hide-label-checkbox >>> .el-checkbox__label {
  display: none !important;
}
.hide-label-checkbox >>> .el-checkbox__inner {
  border-color: #007bff !important;
}
.hide-label-checkbox.is-checked >>> .el-checkbox__inner {
  background-color: #007bff !important;
  border-color: #007bff !important;
}
</style>
