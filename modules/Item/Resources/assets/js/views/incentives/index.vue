<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/incentives">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text" style="margin-top: -5px;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Productos</span></li>
            </ol>
            <div class="right-wrapper pull-right"> 
            </div>
        </div>
        <div class="card mb-0">
            <div class="card-body">
                <data-table :resource="resource">
                    <tr slot="heading" width="100%">
                        <!-- <th>#</th> -->
                        <!-- <th>Cód. Interno</th> -->
                        <th>Producto</th> 
                        <th>Tipo</th> 
                        <th>Comisión</th>
                        <th class="text-right">Acciones</th>
                    <tr>
                    <tr slot-scope="{ index, row }">
                        <!-- <td>{{ index }}</td> -->
                        <!-- <td>{{ row.internal_id }}</td> -->
                        <td>{{ row.full_description }}</td> 
                        <td>{{ row.commission_type }}</td>
                        <td>{{ row.commission_amount }}</td>
                        <td class="text-right">
                            <template v-if="typeUser === 'admin'">
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickCreate(row.id)">Comisión</button>
                                <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickDelete(row.id)">Eliminar</button>
                            </template>
                        </td>
                    </tr>
                </data-table>
            </div>

            <items-form :showDialog.sync="showDialog"
                        :recordId="recordId"></items-form>
 

        </div>
    </div>
</template>
<script>

    import ItemsForm from './form.vue'
    import DataTable from '../../../../../../../resources/js/components/DataTable.vue'

    export default {
        props:['typeUser'],
        components: {ItemsForm,  DataTable},
        data() {
            return {
                showDialog: false,
                showImportDialog: false,
                showWarehousesDetail: false,
                resource: 'incentives',
                recordId: null,
            }
        },
        created() {
        },
        methods: { 
            clickCreate(recordId = null) {
                this.recordId = recordId
                this.showDialog = true
            }, 
            clickDelete(id) {
                this.destroy(`/${this.resource}/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            destroy(url) {
                return new Promise((resolve) => {
                    this.$confirm('¿Desea eliminar el incentivo?', 'Eliminar', {
                        confirmButtonText: 'Eliminar',
                        cancelButtonText: 'Cancelar',
                        type: 'warning'
                    }).then(() => {
                        this.$http.delete(url)
                            .then(res => {
                                if(res.data.success) {
                                    this.$message.success(res.data.message)
                                    resolve()
                                }else{
                                    this.$message.error(res.data.message)
                                    resolve()
                                }
                            })
                            .catch(error => {
                                if (error.response.status === 500) {
                                    this.$message.error('Error al intentar eliminar');
                                } else {
                                    console.log(error.response.data.message)
                                }
                            })
                    }).catch(error => {
                        console.log(error)
                    });
                })
            },
        }
    }
</script>
