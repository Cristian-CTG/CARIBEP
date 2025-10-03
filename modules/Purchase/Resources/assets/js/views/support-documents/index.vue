<template>
    <div  v-loading="loading">
        <div class="page-header pr-0">
            <h2><a href="/support-documents">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-bag" style="margin-top: -5px;">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z"></path>
                    <path d="M9 11v-5a3 3 0 0 1 6 0v5"></path>
                </svg>
            </a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>DSNOF</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <a :href="`/${resource}/create`" class="btn btn-custom btn-sm  mt-2 mr-2"><i class="fa fa-plus-circle"></i> Nuevo</a>
            </div>
        </div>
        <div class="card mb-0">
            <!-- <div class="card-header bg-info">
                <h3 class="my-0">Listado de Documentos de soporte</h3>
            </div> -->
            <div class="card-body">
                <data-table :resource="resource" :init-search="initSearch">
                    <tr slot="heading" width="100%">
                        <!-- <th>#</th> -->
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>Tipo</th>
                        <th class="text-center">Documento</th>
                        <th class="text-center">Estado</th>
                        <th>Documentos relacionados</th>
                        <th class="text-center">Moneda</th>
                        <th class="text-center">Total</th>
                        <th class="text-right">Acciones</th>
                    <tr>
                    <tr slot-scope="{ index, row }">
                        <!-- <td>{{ index }}</td> -->
                        <td>{{ row.date_of_issue }}</td>
                        <td>{{ row.supplier_full_name }}</td>  
                        <td>{{ row.type_document_name }}</td>  
                        <td class="text-center">{{ row.number_full }}</td>  
                        <td class="text-center">
                            <template v-if="row.state_document_id">
                                <span class="badge bg-secondary text-white" :class="{'bg-secondary': (row.state_document_id === 1), 'bg-success': (row.state_document_id === 5), 'bg-dark': (row.state_document_id === 6)}">
                                    {{ row.state_document_name }}
                                </span>
                            </template>
                        </td>  
                        <td>
                            <template v-for="(item, index) in row.support_document_relateds">
                                <span class="ml-1" :key="index">
                                    {{ item.number_full }} 
                                    <br>
                                </span>
                            </template>
                        </td>  
                        <td class="text-center">{{ row.currency_name }}</td> 
                        <td class="text-center">{{ row.total | numberFormat }}</td> 
                        <td class="text-right">

                            <template v-if="!row.is_adjust_note">
                                <a class="btn waves-effect waves-light btn-xs btn-warning" :href="`/support-document-adjust-notes/create/${row.id}`">Nota de ajuste</a>
                            </template>

                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickOptions(row.id)">Opciones</button>
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickSupportDocumentPayment(row.id)">Pagos</button>

                        </td>
                    </tr>
                </data-table>
            </div>

            <support-document-options 
                :showDialog.sync="showDialogOptions"     
                :recordId="recordId"
                :showClose="true">
            </support-document-options>

            <support-document-payments
                :showDialog.sync="showDialogSupportDocumentPayments"
                :recordId="recordId"
                :showClose="true">
            </support-document-payments>

        </div>
    </div>
</template>
<script>

    import SupportDocumentPayments from './payments.vue'
    import SupportDocumentOptions from './partials/options.vue' 
    import DataTable from '@components/DataTableResource.vue'
    import {deletable} from '@mixins/deletable'

    export default {
        mixins: [deletable],
        components: { 
            DataTable, 
            SupportDocumentOptions, 
            SupportDocumentPayments
        },
        data() {
            return {
                showDialogSupportDocumentPayments: false,
                showDialog: false,
                resource: 'support-documents',
                recordId: null,
                recordNumberFull: null,
                showDialogOptions:false,
                loading: false,
                initSearch: {
                    column: 'date_of_issue',
                    value: this.getCurrentMonth()
                }
            }
        },
        created() { 
        },
        methods: { 
            clickOptions(recordId) {
                this.recordId = recordId
                this.showDialogOptions = true
            },
            clickSupportDocumentPayment(recordId) {
                this.recordId = recordId
                this.showDialogSupportDocumentPayments = true
            },
            getCurrentMonth() {
                const date = new Date();
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                return `${year}-${month}`;
            },
        }
    }
</script>
