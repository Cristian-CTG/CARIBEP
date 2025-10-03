<template>
  <el-dialog
    :visible.sync="localShow"
    :width="dialogWidth"
    title="Configurar Etiqueta de Código de Barras"
    @close="handleClose"
    custom-class="barcode-config-dialog"
  >
    <div class="barcode-config-content">
      <h5 class="mb-2">Opciones de etiqueta</h5>
      <div class="form-group mb-2">
        <div class="row align-items-center">
          <div class="col-6 col-md-4 d-flex flex-column align-items-center justify-content-center">
            <label class="font-weight-bold mb-1 text-center">
              Tamaño <span class="text-muted ml-1">(mm)</span>:
            </label>
            <div class="text-muted small text-center mt-1">&nbsp;</div>
          </div>
          <div class="col-6 col-md-4 d-flex flex-column align-items-center">
            <el-input-number
              class="w-100"
              v-model="width"
              :min="20"
              :max="100"
              label="Ancho"
              controls-position="right"
              placeholder="Ancho"
            />
            <div class="text-muted small text-center mt-1">Ancho</div>
          </div>
          <div class="col-6 col-md-4 d-flex flex-column align-items-center">
            <el-input-number
              class="w-100"
              v-model="height"
              :min="10"
              :max="100"
              label="Alto"
              controls-position="right"
              placeholder="Alto"
            />
            <div class="text-muted small text-center mt-1">Alto</div>
          </div>
        </div>
      </div>
      <div class="form-group mb-2">
        <label class="font-weight-bold mb-2 d-block">Campos a mostrar:</label>
        <div class="row">
          <div class="col-6 col-md-4 mb-2" v-for="(field, key) in fields" :key="key">
            <el-checkbox v-model="fields[key]">{{ fieldLabels[key] }}</el-checkbox>
          </div>
        </div>
      </div>
      <div class="form-group mb-0 d-flex justify-content-end">
        <el-button type="success" @click="printLabel">
          Imprimir / Descargar PDF
        </el-button>
      </div>
    </div>
  </el-dialog>
</template>

<script>
export default {
  props: {
    show: Boolean,
    itemId: [Number, Array],
  },
  data() {
    return {
      localShow: this.show,
      width: 32,
      height: 25,
      fields: {
        name: true,
        price: true,
        brand: true,
        category: false,
        color: false,
        size: false,
      },
      fieldLabels: {
        name: 'Nombre',
        price: 'Precio',
        brand: 'Marca',
        category: 'Categoría',
        color: 'Color',
        size: 'Talla',
      },
      dialogWidth: '600px',
    };
  },
  watch: {
    show(val) {
      this.localShow = val;
    },
    localShow(val) {
      if (!val) this.$emit('update:show', false);
    }
  },
  mounted() {
    this.setDialogWidth();
    window.addEventListener('resize', this.setDialogWidth);
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.setDialogWidth);
  },
  methods: {
    handleClose() {
      this.localShow = false;
    },
    getPrintUrl() {
      const params = new URLSearchParams({
        width: this.width,
        height: this.height,
        ...this.fields,
      }).toString();

      if (Array.isArray(this.itemId)) {
        // Para varios IDs
        return `/items/barcodes?ids=${this.itemId.join(',')}&${params}`;
      } else {
        // Para un solo ID
        return `/items/barcode-label/${this.itemId}?${params}`;
      }
    },
    printLabel() {
      window.open(this.getPrintUrl(), '_blank');
    },
    setDialogWidth() {
      this.dialogWidth = window.innerWidth < 700 ? '95%' : '600px';
    }
  },
};
</script>

<style scoped>
.barcode-config-dialog .el-dialog {
  max-width: 98vw;
  padding: 0 !important;
}
.barcode-config-content {
  padding: 0 10px 0px 10px;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}
@media (max-width: 700px) {
  .barcode-config-dialog .el-dialog {
    margin: 10px auto !important;
  }
}
h5 {
  color: #222 !important;
}
.label-barcode {
  color: #222;
  letter-spacing: 0.5px;
  text-align: right;
}
</style>