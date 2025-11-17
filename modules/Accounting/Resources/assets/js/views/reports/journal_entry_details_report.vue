<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/accounting/journal/entry-details-report">
                    <i class="fa fa-list-alt"></i>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Libro Diario</span></li>
            </ol>
        </div>

        <div class="card mb-0">
            <div class="card-body">

                <div class="row mb-3 align-items-end">
                    <div class="col-md-3 mb-2">
                        <label>Filtrar por</label>
                        <el-select v-model="filters.date_mode" class="w-100">
                            <el-option label="Por mes" value="month" />
                            <el-option label="Entre fechas" value="range" />
                        </el-select>
                    </div>

                    <div class="col-md-3 mb-2" v-if="filters.date_mode === 'month'">
                        <label>Mes</label>
                        <el-date-picker
                            v-model="filters.month"
                            type="month"
                            value-format="yyyy-MM"
                            format="yyyy-MM"
                            placeholder="Seleccione mes"
                            class="w-100"
                        />
                    </div>

                    <div class="col-md-4 mb-2" v-if="filters.date_mode === 'range'">
                        <label>Rango de fechas</label>
                        <el-date-picker
                            v-model="filters.date_range"
                            type="daterange"
                            range-separator="a"
                            start-placeholder="Inicio"
                            end-placeholder="Fin"
                            value-format="yyyy-MM-dd"
                            class="w-100"
                        />
                    </div>

                    <div class="col-md-3 mb-2">
                        <label>Tipo comprobante</label>
                        <el-select 
                            v-model="filters.journal_prefix_id"
                            clearable filterable
                            placeholder="Seleccione tipo"
                            class="w-100"
                        >
                            <el-option
                                v-for="opt in journalPrefixes"
                                :key="opt.id"
                                :value="opt.id"
                                :label="opt.description"
                            />
                        </el-select>
                    </div>

                    <div class="col-md-3 mb-2">
                        <label>Numeración desde</label>
                        <el-input v-model="filters.number_from" placeholder="Desde"/>
                    </div>

                    <div class="col-md-3 mb-2">
                        <label>Numeración hasta</label>
                        <el-input v-model="filters.number_to" placeholder="Hasta"/>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12 text-left">
                        <div class="d-inline-block mr-2">
                            <el-button type="primary" @click="fetchRecords">Buscar</el-button>
                        </div>
                        <div class="d-inline-block mr-2">
                            <el-button type="primary" @click="exportPdf">PDF</el-button>
                        </div>
                        <div class="d-inline-block">
                            <el-button type="success" @click="exportExcel">Excel</el-button>
                        </div>
                    </div>
                </div>

                <div v-if="loading" class="text-center my-4">
                    <el-spinner type="circle"/> Cargando...
                </div>

                <div v-else>
                    <div v-if="entries.length === 0" class="text-center my-4">
                        No se encontraron datos
                    </div>

                    <div v-for="entry in entries" :key="entry.id" class="mb-4">

                        <div class="row mb-2">
                            <div class="col-md-8">
                                <h5 class="mb-1">
                                    <b>{{ entry.prefix }}-{{ entry.number }}</b>
                                </h5>
                                <div>
                                    <span class="mr-3"><b>Fecha:</b> {{ entry.date }}</span>
                                    <span><b>Concepto:</b> {{ entry.description }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Cuenta</th>
                                        <th>Nombre</th>
                                        <th>Tercero</th>
                                        <th class="text-right">Débito</th>
                                        <th class="text-right">Crédito</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="(detail, index) in entry.details"
                                        :key="'detail-' + entry.id + '-' + index">

                                        <td>{{ detail.account_code }}</td>
                                        <td>{{ detail.account_name }}</td>
                                        <td>{{ detail.third_party_name }}</td>
                                        <td class="text-right">{{ formatCurrency(detail.debit) }}</td>
                                        <td class="text-right">{{ formatCurrency(detail.credit) }}</td>
                                    </tr>
                                </tbody>

                                <tfoot>
                                    <tr class="font-weight-bold bg-light">
                                        <td colspan="3" class="text-right">Total comprobante</td>
                                        <td class="text-right">{{ formatCurrency(entry.total_debit) }}</td>
                                        <td class="text-right">{{ formatCurrency(entry.total_credit) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-12 text-right">
                            <h5>
                                <b>Total general:</b>
                                <span class="mr-3">{{ formatCurrency(totalDebit) }}</span>
                                <span>{{ formatCurrency(totalCredit) }}</span>
                            </h5>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import queryString from "query-string";

export default {
    data() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, "0");

        return {
            loading: false,
            journalPrefixes: [],
            entries: [],
            totalDebit: 0,
            totalCredit: 0,

            filters: {
                date_mode: "month",
                month: `${year}-${month}`,
                date_range: [],
                journal_prefix_id: null,
                number_from: "",
                number_to: ""
            }
        };
    },

    mounted() {
        this.loadJournalPrefixes();
        this.fetchRecords();
    },

    methods: {
        async loadJournalPrefixes() {
            try {
                const res = await this.$http.get("/accounting/journal/prefixes");
                this.journalPrefixes = res.data;
            } catch (e) {
                console.error(e);
            }
        },

        async fetchRecords() {
            this.loading = true;

            const params = { ...this.filters };

            if (this.filters.date_mode === "month") {
                params.date_start = null;
                params.date_end = null;
            }

            if (this.filters.date_mode === "range") {
                params.month = null;

                if (this.filters.date_range && this.filters.date_range.length === 2) {
                    params.date_start = this.filters.date_range[0];
                    params.date_end   = this.filters.date_range[1];
                }
            }

            try {
                const { data } = await this.$http.get(
                    "/accounting/entry-details-report/records",
                    { params }
                );

                this.entries = data.data;
                this.totalDebit = data.meta.total_debit;
                this.totalCredit = data.meta.total_credit;

            } catch (e) {
                console.error(e);
            }

            this.loading = false;
        },

        buildExportParams(format) {
            const params = { ...this.filters, format };

            if (this.filters.date_mode === "month") {
                delete params.date_start;
                delete params.date_end;
                delete params.date_range;
            }

            if (this.filters.date_mode === "range") {
                params.month = null;
                delete params.month;

                if (this.filters.date_range && this.filters.date_range.length === 2) {
                    params.date_start = this.filters.date_range[0];
                    params.date_end   = this.filters.date_range[1];
                }

                delete params.date_range;
            }

            delete params.date_mode;

            return params;
        },

        exportPdf() {
            const params = this.buildExportParams("pdf");
            const qs = queryString.stringify(params);
            window.open(`/accounting/entry-details-report/export?${qs}`, "_blank");
        },

        exportExcel() {
            const params = this.buildExportParams("excel");
            const qs = queryString.stringify(params);
            window.open(`/accounting/entry-details-report/export-excel?${qs}`, "_blank");
        },

        formatCurrency(val) {
            const n = Number(val) || 0;
            return n.toLocaleString("es-PE", {
                style: "currency",
                currency: "PEN"
            });
        }
    }
};
</script>