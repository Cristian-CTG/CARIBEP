<template>
    <div>
        <div class="page-header pr-0">
            <h2><a :href="breadcrumbUrl">
                <span v-html="breadcrumbIcon"></span>
            </a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>{{ title }}</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <button v-if="type == 'customers'" type="button" class="btn btn-danger btn-sm  mt-2 mr-2" @click.prevent="clickDeleteAll()"><i class="fa fa-trash"></i> Eliminar</button>
                <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickExport()"><i class="fa fa-upload"></i> Exportar</button>
                <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickImport()"><i class="fa fa-download"></i> Importar</button>
                <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickCreate()"><i class="fa fa-plus-circle"></i> Nuevo</button>
            </div>
        </div>
        <div class="card mb-0">            
            <div class="card-body">
                <data-table :resource="resource+`/${this.type}`">
                    <tr slot="heading">
                        <!-- <th>#</th> -->
                        <th>Nombre</th>
                        <th class="text-right">Número</th>
                        <th class="text-right">Acciones</th>
                    <tr>
                    <tr slot-scope="{ index, row }" :class="{ disable_color : !row.enabled}">
                        <!-- <td>{{ index }}</td> -->
                        <td>{{ row.name }}</td>
                        <td class="text-right">{{ row.number }}</td>
                        <td class="text-right">

                            <template v-if="row.enabled">
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickCreate(row.id)" >Editar</button>
                            </template>

                            <template v-if="typeUser === 'admin'">
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickDelete(row.id)">Eliminar</button>

                                <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickDisable(row.id)" v-if="row.enabled">Inhabilitar</button>
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-primary" @click.prevent="clickEnable(row.id)" v-else>Habilitar</button>

                            </template>
                        </td>
                    </tr>
                </data-table>
            </div>

            <persons-form :showDialog.sync="showDialog"
                          :type="type"
                          :recordId="recordId"
                          :api_service_token="api_service_token"></persons-form>

            <persons-import :showDialog.sync="showImportDialog"
                            :type="type"></persons-import>
        </div>
    </div>
</template>

<script>

    import PersonsForm from './form.vue'
    import PersonsImport from './import.vue'
    import DataTable from '../../../components/DataTable.vue'
    import {deletable} from '../../../mixins/deletable'

    export default {
        mixins: [deletable],
        props: ['type', 'typeUser','api_service_token'],
        components: {PersonsForm, PersonsImport, DataTable},
        data() {
            return {
                title: null,
                showDialog: false,
                showImportDialog: false,
                resource: 'persons',
                recordId: null,
            }
        },
        created() {
            this.title = (this.type === 'customers')?'Clientes':'Proveedores'
        },
        computed: {
            breadcrumbUrl() {
                return this.type === 'customers' 
                    ? '/persons/customers' 
                    : '/persons/suppliers';
            },
            breadcrumbIcon() {
                if (this.type === 'customers') {
                    return `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" 
                                stroke-linecap="round" stroke-linejoin="round" 
                                style="margin-top: -5px;">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>`;
                }
                return `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" 
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" 
                            stroke-linecap="round" stroke-linejoin="round" 
                            class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-bag" style="margin-top: -5px">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z"></path>
                            <path d="M9 11v-5a3 3 0 0 1 6 0v5"></path>
                        </svg>`;
            }
        },
        methods: {
            clickExport() {
                window.open(`/${this.resource}/co-export/${this.type}`, '_blank');
            },
            clickCreate(recordId = null) {
                this.recordId = recordId
                this.showDialog = true
            },
            clickImport() {
                this.showImportDialog = true
            },
            clickDelete(id) {
                this.destroy(`/${this.resource}/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            clickDisable(id){
                this.disable(`/${this.resource}/enabled/${0}/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            clickEnable(id){
                this.enable(`/${this.resource}/enabled/${1}/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            clickDeleteAll(){
                this.destroyAll(`/${this.resource}/delete/all/${this.type}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
        }
    }
</script>
