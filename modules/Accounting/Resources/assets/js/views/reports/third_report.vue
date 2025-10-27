<template>
  <div>
    <div class="page-header pr-0">
      <h2>
        <span>Reporte de Terceros</span>
      </h2>
      <ol class="breadcrumbs">
        <li class="active"><span>Exportar reporte PDF</span></li>
      </ol>
    </div>
    <div class="card mb-0 pt-2 pt-md-0">
      <div class="card-body">
        <div class="row mt-2">
            <div class="col-md-3 pb-1">
                <div class="form-group">
                    <label>Mes</label>
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
              <el-select v-model="filters.type" placeholder="Seleccione" @change="fetchThirds">
                <el-option label="Cliente" value="customers"></el-option>
                <el-option label="Proveedor" value="suppliers"></el-option>
                <el-option label="Empleado" value="employee"></el-option>
                <el-option label="Vendedor" value="seller"></el-option>
              </el-select>
            </div>
          </div>
          <div class="col-md-5 pb-1">
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
            <el-button
              type="primary"
              icon="el-icon-tickets"
              @click.prevent="filters.third_id === 'all' ? exportAllThirds() : exportReport()"
              :disabled="!filters.third_id"
            >Exportar PDF</el-button>
            <!-- <el-button
              type="success"
              icon="el-icon-document"
              @click.prevent="exportAllThirds"
              style="margin-left: 10px;"
            >Reporte de todos los terceros</el-button> -->
          </div>
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
    };
  },
  methods: {
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
        // Cambia la URL para consultar los terceros registrados en third_parties
        const response = await this.$http.get('/accounting/third-report/records', { params });
        this.thirds = response.data.data || [];
        // Si el tercero seleccionado ya no está en la lista, lo deselecciona
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
      if (!this.filters.third_id || !this.filters.dates.length === 2) return;
      const params = {
        ...this.filters,
        start_date: this.filters.dates[0],
        end_date: this.filters.dates[1],
        export: 'pdf'
      };
      window.open(`/accounting/third-report/export?${queryString.stringify(params)}`, '_blank');
    },
    exportAllThirds() {
      if (!this.filters.type || this.filters.dates.length !== 2) return;
      const params = {
        type: this.filters.type,
        start_date: this.filters.dates[0],
        end_date: this.filters.dates[1],
        export: 'pdf'
      };
      window.open(`/accounting/third-report/export-all?${queryString.stringify(params)}`, '_blank');
    },
  },
  watch: {
    'filters.type': 'fetchThirds'
  },
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