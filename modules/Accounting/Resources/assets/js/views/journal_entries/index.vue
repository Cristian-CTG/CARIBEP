<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active">
                    <span>Asientos Contables</span>
                </li>
            </ol>
            <div class="right-wrapper pull-right">
                <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="clickCreate()">
                    <i class="fa fa-plus-circle"></i> Nuevo Asiento
                </button>
            </div>
        </div>

        <div class="card mb-0">
            <div class="card-header bg-info">
                <h3 class="my-0">Listado de Asientos Contables</h3>
            </div>
            <div class="card-body">
                <data-table :resource="resource" ref="dataTable">
                    <tr slot="heading">
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Prefijo</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>

                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>{{ row.date }}</td>
                        <td>{{ row.journal_prefix.prefix }}</td>
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
                        </td>
                    </tr>
                </data-table>
            </div>

        </div>
        <journal-entry-form :showDialog.sync="showDialog" :recordId="recordId"></journal-entry-form>
        
        <journal-entry-detail 
            :showDialog.sync="showDialogDetail" 
            :recordId="recordId"
            >
        </journal-entry-detail>
    </div>
</template>

<script>
import JournalEntryForm from "./form.vue";
import DataTable from "../components/DataTable.vue";
import { deletable } from "@mixins/deletable";
import JournalEntryDetail from "./partials/details.vue";

export default {
    mixins: [deletable],
    components: { JournalEntryForm, DataTable,JournalEntryDetail },
    data() {
        return {
            showDialog: false,
            showDialogDetail: false,
            resource: "accounting/journal/entries",
            recordId: null,
            isAdmin: true, // Esto debería venir desde el backend con la sesión
        };
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
    },
};
</script>