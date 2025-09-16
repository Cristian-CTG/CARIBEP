<template>
    <div v-loading="loading">
        <div class="page-header pr-0">
            <h2><a href="/co-email-reading/process-emails">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-check" style="margin-top: -5px;"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M11.5 21h-5.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v6"></path><path d="M16 3v4"></path><path d="M8 3v4"></path><path d="M4 11h16"></path><path d="M15 19l2 2l4 -4"></path></svg>
            </a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Correos procesados</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <!-- <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickRunProcess()"><i class="fa fa-check"></i> Procesar correos</button> -->
                <el-button class="submit mt-2 mr-2" type="primary"  :loading="loading" @click.prevent="clickRunProcess()"  icon="el-icon-check"> Procesar correos</el-button>
            </div>
        </div>
        <div class="card mb-0">
            <!-- <div class="card-header bg-info">
                <h3 class="my-0">Correos procesados</h3>
            </div> -->
            <div class="card-body">
                
                <data-table :resource="resource">
                    <tr slot="heading">
                        <!-- <th>#</th> -->
                        <th>Usuario</th>
                        <th>Fecha y hora inicio</th>
                        <th>Fecha y hora término</th>
                        <th>Fechas de búsqueda</th>
                        <th class="text-center">Procesado</th>
                        <th>Error</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                    <tr slot-scope="{ index, row }">
                        <!-- <td>{{ index }}</td> -->
                        <td>{{ row.email_user }}</td>
                        <td>{{ row.start_date }} {{ row.start_time }} </td>
                        <td>{{ row.end_date }} {{ row.end_time }} </td>
                        <td>Desde <b>{{ row.search_start_date }}</b> - Hasta <b>{{ row.search_end_date }}</b> </td>
                        <td class="text-center">
                            <template v-if="row.success">
                                <i class="fas fa-check" style="color:red"></i>
                            </template>
                            <template v-else>
                                <i class="fas fa-times"  style="color:green"></i>
                            </template>
                        </td>
                        <td>{{ row.errors }}</td>

                        <td class="text-right">
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickCreate(row.id)">Detalle</button>
                        </td>
                    </tr>
                </data-table>
            </div>
            
            <detail-form :showDialog.sync="showDialog"
                            :recordId="recordId"></detail-form>
                            
            <search-form :showDialog.sync="showDialogSearch"></search-form>
        </div>
    </div>
</template>
<script>

    import DetailForm from './detail.vue'
    import SearchForm from './partials/search.vue'
    import DataTable from '@components/DataTable.vue'

    export default {
        components: {DataTable, DetailForm, SearchForm},
        data() {
            return {
                resource: 'co-email-reading',
                showDialog: false,
                showDialogSearch: false,
                loading: false,
                recordId: null,
            }
        },
        created() {
        },
        methods: {
            async clickRunProcess(){

                this.showDialogSearch = true

                // this.loading = true
                
                // await this.$http.get(`/co-radian-events/search-imap-emails`)
                //     .then(response => {

                //         if(response.data.success)
                //         {
                //             this.$message.success(response.data.message)
                //         }
                //         else
                //         {
                //             this.$message.error(response.data.message)
                //         }

                //         this.$eventHub.$emit('reloadData')
                //     })
                //     .catch(error => {
                //         console.log(error)
                //         this.$message.error(error.response.data.message)
                //     })
                //     .then(() => {
                //         this.loading = false
                //     })
            },
            clickCreate(recordId) {
                this.recordId = recordId
                this.showDialog = true
            },
        }
    }
</script>
