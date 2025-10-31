<template>
  <el-dialog :visible="visible" @close="closeDialog" :title="editMode ? 'Editar detalle' : 'Agregar detalle manual'" width="1000px" :z-index="2000">
    <el-form :model="form" label-width="120px">
      <el-form-item label="Fecha">
        <el-date-picker v-model="form.date" type="date" value-format="yyyy-MM-dd" style="width:100%"></el-date-picker>
      </el-form-item>
      <el-form-item label="Tipo">
        <el-select v-model="form.type" placeholder="Seleccione">
          <el-option label="Entrada" value="entrance"></el-option>
          <el-option label="Salida" value="exit"></el-option>
        </el-select>
      </el-form-item>
      <el-form-item label="Valor">
        <el-input v-model="form.value" type="number" min="0"></el-input>
      </el-form-item>
      <el-form-item label="Origen">
        <el-input v-model="form.source"></el-input>
      </el-form-item>
      <el-form-item label="N° Soporte">
        <el-input v-model="form.support_number"></el-input>
      </el-form-item>
      <el-form-item label="Cheque">
        <el-input v-model="form.check"></el-input>
      </el-form-item>
      <el-form-item label="Concepto">
        <el-input v-model="form.concept"></el-input>
      </el-form-item>
      <el-form-item label="Tipo de tercero">
        <el-select v-model="form.third_party_type" placeholder="Seleccione tipo">
          <el-option label="Cliente" value="customers"></el-option>
          <el-option label="Proveedor" value="suppliers"></el-option>
          <el-option label="Empleado" value="employee"></el-option>
          <el-option label="Vendedor" value="seller"></el-option>
        </el-select>
      </el-form-item>
      <el-form-item label="Tercero">
        <el-select
          v-model="form.third_party_id"
          filterable
          remote
          reserve-keyword
          placeholder="Buscar tercero"
          :remote-method="searchThirdParty"
          :loading="loadingThird"
        >
          <el-option
            v-for="item in thirdPartyOptions"
            :key="item.id"
            :label="item.name"
            :value="item.id"
          />
        </el-select>
      </el-form-item>
    </el-form>
    <span slot="footer" class="dialog-footer">
        <el-button @click="closeDialog">Cancelar</el-button>
        <el-button type="primary" @click="saveDetail">Guardar</el-button>
        <el-button v-if="editMode" type="danger" @click="deleteDetail">Eliminar</el-button>
    </span>
  </el-dialog>
</template>

<script>
export default {
  props: {
    visible: Boolean,
    editMode: Boolean,
    detail: Object, // Si es edición, se pasa el detalle a editar
  },
  data() {
    return {
      form: {
        date: '',
        type: 'entrance',
        value: '',
        source: '',
        support_number: '',
        check: '',
        concept: '',
        third_party_id: null,
      },
      thirdPartyOptions: [],
      loadingThird: false,
      showCreateThird: false,
      thirdForm: {
        name: '',
        type: '',
        document: '',
      }
    }
  },
  watch: {
    visible(val) {
        if (val) {
          // Resetear el formulario si es necesario
          if (!this.editMode) {
              this.form = {
              date: '',
              type: 'entrance',
              value: '',
              source: '',
              support_number: '',
              check: '',
              concept: '',
              third_party_id: null,
              third_party_type: '',
              };
          }
        }
    },
    detail: {
        immediate: true,
        handler(val) {
        if (val) this.form = { ...val };
        }
    }
  },
  methods: {
    async onThirdPartySelected(option) {
        // option.id es tipo_id, por ejemplo: "person_5", "worker_2", "seller_3"
        const [type, origin_id] = option.id.split('_');
        const res = await this.$http.post('/accounting/journal/thirds/sync-from-origin', {
            type,
            origin_id
        });
        // res.data es el tercero creado/actualizado, puedes usar su id para asociarlo al detalle
        this.form.third_party_id = res.data.id;
    },
    async searchThirdParty(query) {
      if (!query || !this.form.third_party_type) return;
      this.loadingThird = true;
      const res = await this.$http.get('/accounting/journal/thirds/third-parties', {
        params: {
          search: query,
          type: this.form.third_party_type
        }
      });
      this.thirdPartyOptions = res.data.data;
      this.loadingThird = false;
    },
    saveDetail() {
        this.$emit('save', { ...this.form });
        this.closeDialog();
    },
    deleteDetail() {
        this.$emit('delete', this.form);
        this.closeDialog();
    },
    closeDialog() {
        this.$emit('update:visible', false);
    },
  }
}
</script>