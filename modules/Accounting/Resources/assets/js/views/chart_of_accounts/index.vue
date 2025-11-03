<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/accounting/charts">
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
                    <span>Cuentas Contables</span>
                </li>
            </ol>
            <div class="right-wrapper pull-right">
                <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="openAccountSetting()">
                    <i class="fa fa-arrows-alt"></i> Configurar Cuentas
                </button>
                <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="openForm()">
                    <i class="fa fa-plus-circle"></i> Nueva Cuenta
                </button>
            </div>
        </div>

        <div class="card mb-3">
            <!-- <div class="card-header bg-info">
                <h3 class="my-0">Listado de Cuentas Contables</h3>
            </div> -->
            <div class="card-body" style="position: relative;">
                <label class="control-label">Buscar por código de cuenta:</label>
                <div class="mb-3">
                    <el-input
                        v-model="searchCode"
                        placeholder="Buscar por código de cuenta"
                        clearable
                        @input="filterTree"
                        style="width: 300px;"
                    />
                </div>
                <div v-if="loading" class="spinner-overlay">
                    <i class="el-icon-loading spinner-icon"></i>
                </div>
                <el-tree
                    v-else
                    :data="filteredTreeData"
                    :props="defaultProps"
                    node-key="id"
                    default-expand-all
                    draggable
                    @node-drop="handleDrop"
                    class="tree-border el-tree--compact custom-tree"
                    >
                    <span slot-scope="{ node, data }" class="tree-node-label d-flex justify-content-between align-items-center w-100">
                        <span>{{ data.code }} - {{ data.label }}</span>
                        <span v-if="data.level >= 4" class="ml-auto d-flex">
                            <el-button size="mini" type="primary" icon="el-icon-edit" @click="openForm(data.id)">Editar</el-button>
                            <el-button size="mini" type="danger" icon="el-icon-delete" @click="deleteRecord(data.id)">Eliminar</el-button>
                        </span>
                    </span>
                </el-tree>
            </div>
        </div>

        <chart-account-form :showDialog.sync="showDialog" :recordId="recordId"></chart-account-form>

        <chart-account-setting :showDialog.sync="showDialogSetting" ></chart-account-setting>
    </div>
</template>

<style scoped>
    .spinner-overlay {
        position: absolute;
        top: 60px;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
    .spinner-icon {
        font-size: 48px;
        color: #409EFF;
    }
    /* Compactar los nodos */
    .el-tree--compact .el-tree-node__content {
        padding: 4px 8px !important;
        font-size: 13px;
    }

    /* Mejor separación visual */
    .custom-tree .el-tree-node__content:hover {
        background-color: #f5f7fa;
        border-radius: 4px;
    }

    /* Botones más suaves */
    .tree-node-label .el-button {
        margin-left: 4px;
        padding: 2px 6px;
        font-size: 12px;
    }

    /* Espaciado general del árbol */
    .tree-border {
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 12px;
        background-color: #ffffff;
    }
</style>

<script>
import ChartAccountForm from "./form.vue";
import DataTable from "../components/DataTable.vue";
import ChartAccountSetting from "./partials/setting.vue";

export default {
    components: { ChartAccountForm, DataTable, ChartAccountSetting },
    data() {
        return {
            showDialog: false,
            showDialogSetting: false,
            resource: "accounting/charts",
            recordId: null,
            treeData: [], // Aquí irá el árbol de cuentas
            filteredTreeData: [],
            searchCode: "",
            loading: false,
            defaultProps: {
                children: 'children',
                label: 'label',
            },
        };
    },
    created() {
        this.getData();
        this.$eventHub.$on('reloadData', () => {
            this.getData()
        })
    },
    methods: {
        handleDrop(draggingNode, dropNode, type, ev) {
            console.log('Dropped:', draggingNode, dropNode);
        },
        openForm(recordId = null) {
            this.recordId = recordId;
            this.showDialog = true;
        },
        openAccountSetting() {
            // console.log("AQUI")
            this.showDialogSetting = true;
        },
        deleteRecord(id) {
            this.$http.delete(`/${this.resource}/${id}`).then(() => {
                this.$eventHub.$emit("reloadData");
            });
        },
        translateType(type) {
            const typeMap = {
                Asset: "Activo",
                Liability: "Pasivo",
                Equity: "Patrimonio",
                Revenue: "Ingreso",
                Expense: "Gasto",
            };
            return typeMap[type] || "Desconocido"; // Valor predeterminado si no coincide
        },
        async getData(){
            await this.$http.get(`/${this.resource}/tree`)
                .then(response => {
                    this.treeData = response.data
                    this.filteredTreeData = response.data
                });
        },
        filterTree() {
            this.loading = true;
            if (!this.searchCode) {
                this.filteredTreeData = this.treeData;
                this.loading = false;
                return;
            }
            // Filtra el árbol recursivamente
            const filterRecursive = (nodes) => {
                return nodes
                    .map(node => {
                        let children = node.children ? filterRecursive(node.children) : [];
                        if (
                            node.code.includes(this.searchCode) ||
                            children.length > 0
                        ) {
                            return {
                                ...node,
                                children
                            };
                        }
                        return null;
                    })
                    .filter(node => node !== null);
            };
            this.filteredTreeData = filterRecursive(this.treeData);
            this.loading = false;
        }
    },
};
</script>
