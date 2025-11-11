<template>
    <el-dialog
        title="Exportación masiva de asientos contables"
        :visible="showDialog"
        @close="close"
        width="800px">
        <form autocomplete="off" @submit.prevent="exportExcel">
            <div class="row pb-4">
                <div class="col-12 mt-2">
                    <p class="mb-2">Exportar Asientos Contables</p>
                </div>
                <div class="col-12 col-sm-6 mt-2">
                    <div class="form-group mb-2">
                        <label class="control-label">Prefijo</label>
                        <el-select v-model="filters.prefix_id" placeholder="Todos" style="width:100%">
                            <el-option :value="null" label="Todos"></el-option>
                            <el-option
                                v-for="prefix in prefixes"
                                :key="prefix.id"
                                :value="prefix.id"
                                :label="prefix.description"
                            ></el-option>
                        </el-select>
                    </div>
                </div>
                <div class="col-12 col-sm-6 mt-2">
                    <div class="form-group mb-2">
                        <label class="control-label">Fechas</label>
                        <el-date-picker
                            v-model="filters.daterange"
                            type="daterange"
                            range-separator="a"
                            start-placeholder="Desde"
                            end-placeholder="Hasta"
                            value-format="yyyy-MM-dd"
                            style="width: 100%;"
                        ></el-date-picker>
                    </div>
                </div>
            </div>
            <span slot="footer">
                <div class="d-inline-block mr-2">
                    <el-button @click="close">Cerrar</el-button>
                </div>
                <div class="d-inline-block">
                    <el-button type="primary" native-type="submit" :loading="loading">Exportar</el-button>
                </div>
            </span>
        </form>
    </el-dialog>
</template>

<script>
export default {
    props: {
        showDialog: { type: Boolean, required: true }
    },
    data() {
        return {
            loading: false,
            prefixes: [],
            filters: {
                prefix_id: null,
                daterange: []
            }
        }
    },
    watch: {
        showDialog(val) {
            if (val) this.loadPrefixes();
        }
    },
    methods: {
        close() {
            this.$emit('update:showDialog', false)
        },
        async loadPrefixes() {
            // Solo prefijos editables
            const res = await this.$http.get('/accounting/journal/prefixes');
            this.prefixes = res.data.filter(p => p.modifiable);
        },
        exportExcel() {
            this.loading = true;
            let params = {};
            if (this.filters.prefix_id) params.prefix_id = this.filters.prefix_id;
            if (this.filters.daterange && this.filters.daterange.length === 2) {
                params.date_from = this.filters.daterange[0];
                params.date_to = this.filters.daterange[1];
            }
            const query = new URLSearchParams(params).toString();
            window.open(`/accounting/journal/entries/export-excel?${query}`, '_blank');
            this.loading = false;
            this.close();
        }
    }
}
</script>