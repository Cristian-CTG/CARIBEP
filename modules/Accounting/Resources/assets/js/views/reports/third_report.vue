<template>
  <div>
    <div class="page-header pr-0">
      <h2>
        <a href="/accounting/third-report" class="mr-2">
          <i class="fa fa-list-alt"></i>
        </a>
      </h2>
      <ol class="breadcrumbs">
        <li class="active"><span>Reporte de Terceros</span></li>
      </ol>
    </div>
    <div class="card mb-0 pt-2 pt-md-0">
      <div class="card-body">
        <div class="row mt-2">
            <div class="col-md-3 pb-1">
                <div class="form-group">
                    <label>Rango de fechas de movimientos</label>
                    <el-date-picker
                      v-model="filters.dates"
                      type="daterange"
                      range-separator="a"
                      start-placeholder="Fecha inicio"
                      end-placeholder="Fecha fin"
                      value-format="yyyy-MM-dd"
                      :clearable="false"
                    ></el-date-picker>
                </div>
            </div>
          <div class="col-md-3 pb-1">
            <div class="form-group">
              <label>Tipo de tercero</label>
              <el-select v-model="filters.type" placeholder="Seleccione" @change="fetchThirds" clearable>
                <el-option label="Todos" value="all"></el-option>
                <el-option label="Cliente" value="customers"></el-option>
                <el-option label="Proveedor" value="suppliers"></el-option>
                <el-option label="Empleado" value="employee"></el-option>
                <el-option label="Vendedor" value="seller"></el-option>
              </el-select>
            </div>
          </div>
          <div class="col-md-5 pb-1" v-if="filters.type !== 'all' && filters.type ">
            <div class="form-group">
              <label>Tercero</label>
              <el-select v-model="filters.third_id" placeholder="Seleccione" filterable :disabled="thirds.length === 0">
                <el-option
                  v-if="thirds.length > 0"
                  :key="'all'"
                  label="Todos"
                  value="all"
                ></el-option>
                <el-option
                  v-for="third in thirds"
                  :key="third.id"
                  :label="third.name"
                  :value="third.id"
                ></el-option>
              </el-select>
            </div>
          </div>
          <!-- <div class="col-md-4 pb-1">
            <div class="form-group">
              <label>Buscar</label>
              <el-input v-model="filters.search" placeholder="Nombre o documento" @input="fetchThirds"></el-input>
            </div>
          </div> -->
          <div class="col-lg-12 mt-3">
            <div class="d-inline-block mb-2">
              <el-button
                type="primary"
                icon="el-icon-search"
                @click="onBuscar"
                :disabled="!canSearch"
              >Buscar</el-button>
            </div>
            <div class="d-inline-block mb-2 ml-2">
              <el-button
                type="primary"
                icon="el-icon-tickets"
                @click.prevent="handleExport('pdf')"
                :disabled="!canExport"
              >Exportar PDF</el-button>
            </div>
            <div class="d-inline-block mb-2 ml-2">
              <el-button
                type="success"
                icon="el-icon-document"
                @click.prevent="handleExport('excel')"
                :disabled="!canExport"
              >Exportar Excel</el-button>
            </div>
            <!-- <el-button
              type="success"
              icon="el-icon-document"
              @click.prevent="exportAllThirds"
              style="margin-left: 10px;"
            >Reporte de todos los terceros</el-button> -->
          </div>
        </div>
        <div v-if="previewRows.length > 0" class="mt-4" v-loading="loading">
            <el-table :data="previewRows" style="width: 100%" size="mini">
              <el-table-column
                prop="nombre"
                label="Nombre"
                width="200"
              />
              <el-table-column
                prop="documento"
                label="Documento"
                width="140"
              />
              <el-table-column prop="codigo" label="Código" width="120"/>
              <el-table-column prop="cuenta" label="Cuenta"/>
              <el-table-column prop="debito" label="Débito" width="120"/>
              <el-table-column prop="credito" label="Crédito" width="120"/>
            </el-table>
            <el-pagination
              class="mt-2"
              background
              layout="prev, pager, next, total"
              :total="previewTotal"
              :page-size="previewPerPage"
              :current-page.sync="previewPage"
              @current-change="fetchPreview"
            />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import queryString from 'query-string'

export default {
  name: "ThirdPartyReport",
  data() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    return {
      filters: {
        type: "",
        third_id: "",
        search: "",
        dates: [],
      },
      thirds: [],
      loading: false,
      previewRows: [],
      previewTotal: 0,
      previewPerPage: 10,
      previewPage: 1,
      hasSearched: false,
    };
  },
  computed: {
    canSearch() {
      // Si tipo es "all", solo requiere fechas
      if (this.filters.type === 'all') {
        return this.filters.dates.length === 2;
      }
      // Si hay tipo y tercero seleccionado
      return this.filters.type && this.filters.third_id && this.filters.dates.length === 2;
    },
    canExport() {
      return this.previewRows.length > 0;
    },
  },
  methods: {
    onBuscar() {
      this.previewPage = 1;
      this.hasSearched = true;
      this.fetchPreview(1);
    },
    handleExport(format) {
      // Si el select de tercero es "Todos", exporta todos los terceros del tipo seleccionado
      if (this.filters.third_id === 'all' || this.filters.type === 'all') {
        if (format === 'pdf') {
          this.exportAllThirds();
        } else {
          this.exportAllThirdsExcel();
        }
      } else {
        if (format === 'pdf') {
          this.exportReport();
        } else {
          this.exportExcel();
        }
      }
    },
    async fetchPreview(page = 1) {
      if (
        (this.filters.type !== 'all' && (!this.filters.third_id || this.filters.dates.length !== 2)) ||
        (this.filters.type === 'all' && this.filters.dates.length !== 2)
      ) {
        this.previewRows = [];
        this.previewTotal = 0;
        return;
      }
      this.previewPage = page;
      const params = {
        type: this.filters.type === 'all' ? '' : this.filters.type, // <-- aquí
        third_id: this.filters.type === 'all' ? 'all' : this.filters.third_id,
        start_date: this.filters.dates[0],
        end_date: this.filters.dates[1],
        page: this.previewPage,
        per_page: this.previewPerPage,
        search: this.filters.search,
      };
      this.loading = true;
      try {
        const res = await this.$http.get('/accounting/third-report/preview-records', { params });
        this.previewRows = res.data.data;
        this.previewTotal = res.data.total;
      } catch (e) {
        this.previewRows = [];
        this.previewTotal = 0;
      }
      this.loading = false;
    },
    async fetchThirds() {
      if (!this.filters.type) {
        this.thirds = [];
        this.filters.third_id = "";
        return;
      }
      this.loading = true;
      try {
        const params = {
          type: this.filters.type,
          search: this.filters.search,
        };
        const response = await this.$http.get('/accounting/third-report/records', { params });
        this.thirds = response.data.data || [];
        if (!this.thirds.find(t => t.id === this.filters.third_id)) {
          this.filters.third_id = "";
        }
      } catch (e) {
        this.thirds = [];
        this.filters.third_id = "";
      }
      this.loading = false;
    },
    exportReport() {
      // Exporta solo un tercero específico
      if (!this.filters.third_id || this.filters.third_id === 'all' || this.filters.dates.length !== 2) return;
      const params = {
        ...this.filters,
        start_date: this.filters.dates[0],
        end_date: this.filters.dates[1],
        export: 'pdf'
      };
      window.open(`/accounting/third-report/export?${queryString.stringify(params)}`, '_blank');
    },
    exportAllThirds() {
      // Exporta todos los terceros del tipo seleccionado
      if (!this.filters.type || this.filters.dates.length !== 2) return;
      const params = {
        type: this.filters.type,
        start_date: this.filters.dates[0],
        end_date: this.filters.dates[1],
        search: this.filters.search,
        export: 'pdf'
      };
      window.open(`/accounting/third-report/export-all?${queryString.stringify(params)}`, '_blank');
    },
    exportExcel() {
      // Exporta solo un tercero específico
      if (!this.filters.third_id || this.filters.third_id === 'all' || this.filters.dates.length !== 2) return;
      const params = {
        ...this.filters,
        start_date: this.filters.dates[0],
        end_date: this.filters.dates[1],
        export: 'excel'
      };
      window.open(`/accounting/third-report/export-excel?${queryString.stringify(params)}`, '_blank');
    },
    exportAllThirdsExcel() {
      // Exporta todos los terceros del tipo seleccionado
      if (!this.filters.type || this.filters.dates.length !== 2) return;
      const params = {
        type: this.filters.type,
        start_date: this.filters.dates[0],
        end_date: this.filters.dates[1],
        search: this.filters.search,
        export: 'excel'
      };
      window.open(`/accounting/third-report/export-all-excel?${queryString.stringify(params)}`, '_blank');
    },
  },
  // watch: {
  //   'filters.third_id'(val) {
  //     if (this.filters.type !== 'all' && val && this.filters.dates.length === 2) {
  //       this.fetchPreview(1);
  //     } else if (this.filters.type === 'all' && this.filters.dates.length === 2) {
  //       this.fetchPreview(1);
  //     } else {
  //       this.previewRows = [];
  //       this.previewTotal = 0;
  //     }
  //   },
  //   'filters.dates'(val) {
  //     if (
  //       (this.filters.type !== 'all' && this.filters.third_id && val.length === 2) ||
  //       (this.filters.type === 'all' && val.length === 2)
  //     ) {
  //       this.fetchPreview(1);
  //     } else {
  //       this.previewRows = [];
  //       this.previewTotal = 0;
  //     }
  //   }
  // },
  mounted() {
    // Si quieres cargar clientes por defecto, descomenta:
    // this.filters.type = 'customers';
    // this.fetchThirds();
  }
};
</script>

<style scoped>
.page-header {
  margin-bottom: 20px;
}
.card {
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
</style>