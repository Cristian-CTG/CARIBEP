<template>
  <div>
    <div class="page-header pr-0">
      <h2>
        <a href="/promotions">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-cart" style="margin-top: -5px;">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
              <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
              <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
              <path d="M17 17h-11v-14h-2"></path>
              <path d="M6 5l14 1l-1 7h-13"></path>
          </svg>
        </a>
      </h2>
      <ol class="breadcrumbs">
        <li class="active">
          <span>Promociones</span>
        </li>
      </ol>
      <div class="right-wrapper pull-right">
        <template>
          <!-- v-if="typeUser === 'admin'" -->
          <!-- <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickImport()"><i class="fa fa-upload"></i> Importar</button>-->
          <button
            type="button"
            class="btn btn-custom btn-sm mt-2 mr-2"
            @click.prevent="clickCreate()"
          >
            <i class="fa fa-plus-circle"></i> Nuevo
          </button>
        </template>
      </div>
    </div>
    <div class="card mb-0">
      <!-- <div class="card-header bg-info">
        <h3 class="my-0">Listado de Promociones</h3>
      </div> -->
      <div class="card-body">
        <data-table :apply-filter="false" :resource="resource">
          <tr slot="heading" width="100%">
            <!-- <th>#</th> -->
            <th>Nombre</th>
            <th>Descripción</th>
            <th class="text-center">Imagen</th>
            <th class="text-right">Acciones</th>
          </tr>
          <tr></tr>
          <tr slot-scope="{ index, row }">
            <!-- <td>{{ index }}</td> -->
            <td>{{ row.name }}</td>
            <td>{{ row.description }}</td>
            <td class="text-center">
              <img :src="row.image_url" alt width="170" height="130" />
            </td>
            <td class="text-right">
              <template>
                <!-- v-if="typeUser === 'admin'" -->
                <button
                  type="button"
                  class="btn waves-effect waves-light btn-xs btn-info"
                  @click.prevent="clickCreate(row.id)"
                >Editar</button>
                <button
                  type="button"
                  class="btn waves-effect waves-light btn-xs btn-danger"
                  @click.prevent="clickDelete(row.id)"
                >Eliminar</button>
              </template>
            </td>
          </tr>
        </data-table>
      </div>

      <promotions-form :showDialog.sync="showDialog" :recordId="recordId"></promotions-form>
    </div>
  </div>
</template>
<script>
import PromotionsForm from "./form.vue";
// import ItemsImport from './import.vue'
import DataTable from "../../../components/DataTable.vue";
import { deletable } from "../../../mixins/deletable";

export default {
  props: [], //'typeUser'
  mixins: [deletable],
  components: { PromotionsForm, DataTable }, //ItemsImport
  data() {
    return {
      showDialog: false,
      showImportDialog: false,

      showImageDetail: false,
      resource: "promotions",
      recordId: null
    };
  },
  created() {},
  methods: {
    clickCreate(recordId = null) {
      this.recordId = recordId;
      this.showDialog = true;
    },
    clickImport() {
      this.showImportDialog = true;
    },
    clickDelete(id) {
      this.destroy(`/${this.resource}/${id}`).then(() =>
        this.$eventHub.$emit("reloadData")
      );
    }
  }
};
</script>
