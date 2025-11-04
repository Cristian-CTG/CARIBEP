<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/accounting/journal/entries">
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
                    <span>Asientos Contables</span>
                </li>
            </ol>
            <div class="right-wrapper pull-right">
                <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="showExportModal = true">
                    <i class="fa fa-download"></i> Exportación masiva
                </button>
                <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="showImportModal = true">
                    <i class="fa fa-upload"></i> Importación masiva
                </button>
                <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="clickCreate()">
                    <i class="fa fa-plus-circle"></i> Nuevo Asiento
                </button>
            </div>
        </div>

        <div class="card mb-0">
            <!-- <div class="card-header bg-info">
                <h3 class="my-0">Listado de Asientos Contables</h3>
            </div> -->
            <div class="card-body">
                <data-table :resource="resource" ref="dataTable" :journal-prefixes="journalPrefixes">
                    <tr slot="heading">
                        <!-- <th>#</th> -->
                        <th>Fecha</th>
                        <th>Número</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>

                    <tr slot-scope="{ index, row }">
                        <!-- <td>{{ index }}</td> -->
                        <td>{{ row.date }}</td>
                        <td>{{ row.journal_prefix.prefix }}-{{ row.number }}</td>
                        <td>{{ row.description }}</td>
                        <td>
                            <span :class="statusClass(row.status)">{{ statusText(row.status) }}</span>
                        </td>
                        <td class="text-right">
                            <button v-if="row.status === 'draft'" class="btn btn-xs btn-info"
                                @click.prevent="clickCreate(row.id)">
                                Editar
                            </button>
                            <button v-if="row.status === 'draft'" class="btn btn-xs btn-warning"
                                @click.prevent="requestApproval(row.id)">
                                Solicitar Aprobación
                            </button>
                            <button v-if="row.status === 'pending_approval' && isAdmin" class="btn btn-xs btn-success"
                                @click.prevent="approve(row.id)">
                                Aprobar
                            </button>
                            <button v-if="row.status === 'pending_approval' && isAdmin" class="btn btn-xs btn-danger"
                                @click.prevent="reject(row.id)">
                                Rechazar
                            </button>
                            <button v-if="row.status === 'posted'" class="btn btn-xs btn-info"
                                @click.prevent="clickDetail(row.id)">
                                Detalle
                            </button>
                            <button v-if="row.status === 'posted'" class="btn btn-xs btn-primary"
                                @click.prevent="clickPdf(row.id)">
                                PDF
                            </button>
                            <button v-if="row.status === 'draft' || row.status === 'rejected'" class="btn btn-xs btn-danger"
                                @click.prevent="deleteEntry(row.id)">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                </data-table>
            </div>

        </div>
        <journal-entry-form
            :showDialog.sync="showDialog"
            :recordId="recordId"
            :journal-prefixes="journalPrefixes"></journal-entry-form>

        <journal-entry-detail
            :showDialog.sync="showDialogDetail"
            :recordId="recordId"
            :journal-prefixes="journalPrefixes"></journal-entry-detail>
        
        <journal-entries-export-modal
            :showDialog.sync="showExportModal">
        </journal-entries-export-modal>

        <journal-entries-import-modal
            :showDialog.sync="showImportModal">
        </journal-entries-import-modal>
    </div>
</template>

<script>
import JournalEntryForm from "./form.vue";
import DataTable from "../components/DataTable.vue";
import { deletable } from "@mixins/deletable";
import JournalEntryDetail from "./partials/details.vue";
import JournalEntriesExportModal from "./partials/JournalEntriesExportModal.vue";
import JournalEntriesImportModal from "./partials/JournalEntriesImportModal.vue";

export default {
    mixins: [deletable],
    components: { JournalEntryForm, DataTable, JournalEntryDetail, JournalEntriesExportModal, JournalEntriesImportModal },
    data() {
        return {
            showDialog: false,
            showDialogDetail: false,
            showExportModal: false,
            showImportModal: false,
            resource: "accounting/journal/entries",
            recordId: null,
            isAdmin: true, // Esto debería venir desde el backend con la sesión
            journalPrefixes: []
        };
    },
    created() {
        this.loadJournalPrefixes();
    },
    methods: {
        clickCreate(recordId = null) {
            this.recordId = recordId;
            this.showDialog = true;
        },
        requestApproval(id) {
            this.$http.put(`/${this.resource}/${id}/request-approval`).then(() => this.$eventHub.$emit("reloadData"));
        },
        approve(id) {
            this.$http.put(`/${this.resource}/${id}/approve`).then(() => this.$eventHub.$emit("reloadData"));
        },
        reject(id) {
            this.$http.put(`/${this.resource}/${id}/reject`).then(() => this.$eventHub.$emit("reloadData"));
        },
        statusText(status) {
            const statuses = {
                draft: "Borrador",
                pending_approval: "Por Aprobar",
                posted: "Aprobado",
                rejected: "Rechazado",
            };
            return statuses[status] || status;
        },
        statusClass(status) {
            const classes = {
                draft: "badge badge-secondary",
                pending_approval: "badge badge-warning",
                posted: "badge badge-success",
                rejected: "badge badge-danger",
            };
            return classes[status] || "badge badge-dark";
        },
        clickDetail(recordId = null) {
            this.recordId = recordId;
            this.showDialogDetail = true;
        },
        async loadJournalPrefixes() {
            await this.$http.get("/accounting/journal/prefixes").then((response) => {
                this.journalPrefixes = response.data;
            });
        },
        clickPdf(recordId) {
            window.open(`/accounting/journal/entries/pdf/${recordId}`, '_blank');
        },
        async deleteEntry(id) {
            this.destroy(`/${this.resource}/${id}`).then(() => {
                this.$eventHub.$emit("reloadData");
            });
        },
    },
};
</script>