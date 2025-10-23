<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/accounting/journal/entry-details-report">
                    <i class="fa fa-list-alt"></i>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active">
                    <span>Reporte de Detalles Contables</span>
                </li>
            </ol>
        </div>
        <div class="card mb-0">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 col-sm-12 pb-2">
                        <label>Mes:</label>
                        <el-date-picker
                            v-model="filters.month"
                            type="month"
                            style="width: 100%;"
                            placeholder="Selecciona mes"
                            value-format="yyyy-MM"
                            @change="getRecords"
                        ></el-date-picker>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-12 pb-2">
                        <label>Tipo comprobante:</label>
                        <el-select v-model="filters.journal_prefix_id" filterable clearable placeholder="Selecciona tipo" @change="getRecords">
                            <el-option v-for="option in journalPrefixes" :key="option.id" :value="option.id" :label="option.description"></el-option>
                        </el-select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-12 pb-2">
                        <label>Rango de numeración:</label>
                        <br>
                        <el-input
                            v-model="filters.number_from"
                            placeholder="Desde"
                            style="width: 40%; display: inline-block;"
                            @input="getRecords"
                        ></el-input>
                        <span style="width: 10%; display: inline-block; text-align: center;">-</span>
                        <el-input
                            v-model="filters.number_to"
                            placeholder="Hasta"
                            style="width: 40%; display: inline-block;"
                            @input="getRecords"
                        ></el-input>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-12 pb-2 d-flex align-items-end">
                        <el-button type="danger" icon="el-icon-printer" @click="exportPdf">
                            Exportar PDF
                        </el-button>
                    </div>
                </div>
                <data-table
                    :resource="resource"
                    :custom-filters="filters"
                    :applyFilter="false"
                    :journal-prefixes="journalPrefixes"
                    ref="dataTable">
                    <tr slot="heading">
                        <th>Comprobante</th>
                        <th>Fecha de creación</th>
                        <th>Concepto</th>
                        <th>Tercero implicado</th>
                        <th>N° Cuenta Contable</th>
                        <th>Débito</th>
                        <th>Crédito</th>
                    </tr>
                    <tr slot-scope="{ index, row }">
                        <td>
                            {{ row.journal_entry.journal_prefix.prefix }}-{{ row.journal_entry.number }}
                        </td>
                        <td>{{ row.journal_entry.date }}</td>
                        <td>{{ row.journal_entry.description }}</td>
                        <td>{{ row.third_party_name }}</td>
                        <td>{{ row.chart_of_account_id }}</td>
                        <td>{{ row.debit | currency }}</td>
                        <td>{{ row.credit | currency }}</td>
                    </tr>
                </data-table>
            </div>
        </div>
    </div>
</template>

<script>
import DataTable from "../components/DataTable.vue";
import queryString from 'query-string';

export default {
    components: { DataTable },
    data() {
        const now = new Date();
        const year = now.getFullYear();
        const month = (now.getMonth() + 1).toString().padStart(2, '0');
        return {
            resource: "accounting/entry-details-report",
            journalPrefixes: [],
            filters: {
                month: `${year}-${month}`,
                journal_prefix_id: null,
                number_from: null,
                number_to: null,
            }
        };
    },
    async created() {
        await this.loadJournalPrefixes();
        this.getRecords();
    },
    methods: {
        async loadJournalPrefixes() {
            await this.$http.get("/accounting/journal/prefixes").then((response) => {
                this.journalPrefixes = response.data;
            });
        },
        getRecords() {
            this.$refs.dataTable.getRecords();
        },
        exportPdf() {
            const params = queryString.stringify({ ...this.filters, format: 'pdf' });
            window.open(`/accounting/entry-details-report/export?${params}`, '_blank');
        },
        // getThirdPartyName(row) {
        //     // Tercero directo
        //     if (row.third_party && row.third_party.name)
        //         return row.third_party.name;

        //     const journalEntry = row.journal_entry;

        //     // Compra
        //     if (journalEntry.purchase && journalEntry.purchase.supplier) {
        //         const supplier = typeof journalEntry.purchase.supplier === 'object'
        //             ? journalEntry.purchase.supplier
        //             : JSON.parse(journalEntry.purchase.supplier);
        //         return supplier && supplier.name ? supplier.name : '-';
        //     }

        //     // Documento de venta
        //     if (journalEntry.document && journalEntry.document.customer) {
        //         const customer = typeof journalEntry.document.customer === 'object'
        //             ? journalEntry.document.customer
        //             : JSON.parse(journalEntry.document.customer);
        //         return customer && customer.name ? customer.name : '-';
        //     }

        //     // Documento POS
        //     if (journalEntry.document_pos && journalEntry.document_pos.customer) {
        //         const customer = typeof journalEntry.document_pos.customer === 'object'
        //             ? journalEntry.document_pos.customer
        //             : JSON.parse(journalEntry.document_pos.customer);
        //         return customer && customer.name ? customer.name : '-';
        //     }

        //     // Documento de soporte
        //     if (journalEntry.support_document && journalEntry.support_document.supplier) {
        //         const supplier = typeof journalEntry.support_document.supplier === 'object'
        //             ? journalEntry.support_document.supplier
        //             : JSON.parse(journalEntry.support_document.supplier);
        //         return supplier && supplier.name ? supplier.name : '-';
        //     }

        //     // Nómina
        //     if (journalEntry.document_payroll && journalEntry.document_payroll.worker) {
        //         const worker = typeof journalEntry.document_payroll.worker === 'object'
        //             ? journalEntry.document_payroll.worker
        //             : JSON.parse(journalEntry.document_payroll.worker);
        //         return worker && (worker.full_name || worker.name) ? (worker.full_name || worker.name) : '-';
        //     }

        //     // Nota de ajuste de soporte
        //     if (journalEntry.support_document_adjust_note && journalEntry.support_document_adjust_note.supplier) {
        //         const supplier = typeof journalEntry.support_document_adjust_note.supplier === 'object'
        //             ? journalEntry.support_document_adjust_note.supplier
        //             : JSON.parse(journalEntry.support_document_adjust_note.supplier);
        //         return supplier && supplier.name ? supplier.name : '-';
        //     }

        //     return '-';
        // },
    },
    filters: {
        currency(value) {
            if (!value) return '0.00';
            return Number(value).toLocaleString('es-PE', { style: 'currency', currency: 'PEN' });
        }
    }
};
</script>