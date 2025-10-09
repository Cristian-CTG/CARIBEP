<template>
  <el-dialog
    :visible.sync="localShow"
    :width="dialogWidth"
    title="Configurar Etiqueta de Código de Barras"
    @close="handleClose"
    custom-class="barcode-config-dialog"
  >
    <div class="barcode-config-content">
      <label class="font-weight-bold mb-1">Opciones de etiqueta</label>
      <div class="form-group mb-2">
        <div class="row align-items-center">
          <!-- <div class="col-6 col-md-4 d-flex flex-column align-items-center justify-content-center">
            <label class="font-weight-bold mb-1 text-center">
              Tamaño <span class="text-muted ml-1">(mm)</span>:
            </label>
            <div class="text-muted small text-center mt-1">&nbsp;</div>
          </div> -->
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
            <div class="text-muted small text-center mt-1">Ancho (mm)</div>
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
            <div class="text-muted small text-center mt-1">Alto (mm)</div>
          </div>
          <div class="col-6 col-md-4 d-flex flex-column align-items-center">
            <el-input-number
              class="w-100"
              v-model="gapX"
              :min="0.1"
              :max="5"
              label="Espacio horizontal"
              controls-position="right"
              placeholder="Espacio entre etiquetas (mm)"
            />
            <div class="text-muted small text-center mt-1">Espacio entre etiquetas (mm)</div>
          </div>
        </div>
      </div>
      <label class="font-weight-bold mb-1">Opciones de hoja</label>
      <div class="form-group mb-2">
        <div class="row align-items-center">
          <div class="col-6 col-md-4 d-flex flex-column align-items-center">
            <el-input-number
              class="w-100"
              v-model="pageWidth"
              :min="50"
              :max="300"
              label="Ancho hoja"
              controls-position="right"
              placeholder="Ancho hoja (mm)"
            />
            <div class="text-muted small text-center mt-1">Ancho hoja (mm)</div>
          </div>
          <div class="col-6 col-md-4 d-flex flex-column align-items-center">
            <el-input-number
              class="w-100"
              v-model="columns"
              :min="1"
              :max="3"
              label="Columnas"
              controls-position="right"
              placeholder="Columnas"
            />
            <div class="text-muted small text-center mt-1">Columnas</div>
          </div>
          <div class="form-group mb-2" v-if="!fromPurchase">
            <label class="font-weight-bold mb-1">Cantidad de etiquetas a imprimir:</label>
            <el-input-number
              class="w-100"
              v-model="repeat"
              :min="1"
              :max="500"
              label="Cantidad"
              controls-position="right"
              placeholder="Cantidad de etiquetas"
              :disabled="useStockAsRepeat"
            />
            <el-checkbox
              v-if="showMultiUseStock"
              class="ml-2"
              v-model="useStockAsRepeat"
              @change="onUseStockAsRepeat"
            >
              Usar stock actual de cada producto
            </el-checkbox>
            <el-checkbox
              v-else-if="showSingleUseStock"
              class="ml-2"
              v-model="useStockAsRepeat"
              @change="onUseStockAsRepeat"
            >
              Usar stock actual ({{ singleStockLabel }})
            </el-checkbox>
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
    stock: {
      type: [Number, Array, String],
      default: null,
    },
    fromPurchase: {
      type: Boolean,
      default: false,
    }
  },
  data() {
    return {
      localShow: this.show,
      width: 32,
      height: 25,
      pageWidth: 100,
      columns: 3,
      gapX: 2,
      repeat: 15,
      useStockAsRepeat: false,
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
      this.localShow = val;
      // Inicializar valores al abrir para evitar estados previos
      if (val) {
        this.useStockAsRepeat = false;
        // Si es un solo item y hay stock, prellenar repeat con el stock; si no, dejar 1
        if (!Array.isArray(this.itemId) && (typeof this.stock === 'number' || typeof this.stock === 'string')) {
          this.repeat = parseInt(this.stock) || 1;
        } else {
          this.repeat = 1;
        }
      }
    },
    localShow(val) {
      if (!val) this.$emit('update:show', false);
    },
    itemId() {
      if (this.useStockAsRepeat) this.applyStockToRepeat();
    },
    stock() {
      if (this.useStockAsRepeat) this.applyStockToRepeat();
    }
  },
  mounted() {
    this.setDialogWidth();
    window.addEventListener('resize', this.setDialogWidth);
    if (this.useStockAsRepeat) this.applyStockToRepeat();
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.setDialogWidth);
  },
  computed: {
    // Mostrar checkbox para múltiples items solo si todos tienen stock > 0
    showMultiUseStock() {
      if (Array.isArray(this.itemId) && Array.isArray(this.stock) && this.itemId.length > 0) {
        return this.stock.every(s => parseFloat(s) > 0);
      }
      return false;
    },
    // Mostrar checkbox para item único solo si stock > 0
    showSingleUseStock() {
      // Solo muestra el checkbox si el stock es mayor a 0
      return !Array.isArray(this.itemId) && (parseFloat(this.stock) > 0);
    },
    // Etiqueta legible para el checkbox de stock único
    singleStockLabel() {
      if (this.stock === null || this.stock === undefined) return '0';
      const n = parseFloat(this.stock);
      return Number.isFinite(n) ? (n % 1 === 0 ? String(n) : n.toFixed(2)) : String(this.stock);
    }
  },
  methods: {
    onUseStockAsRepeat(val) {
      this.useStockAsRepeat = val;
      if (val) {
        this.applyStockToRepeat();
      } else {
        // restablecer a 1 o a un valor razonable
        this.repeat = 1;
      }
    },
    applyStockToRepeat() {
      if (this.useStockAsRepeat) {
        // Si vienen varios items y stock es array -> CSV en mismo orden
        if (Array.isArray(this.itemId) && Array.isArray(this.stock)) {
          const idsLen = this.itemId.length;
          const stocks = this.stock.slice(0, idsLen);
          while (stocks.length < idsLen) stocks.push(0);
          this.repeat = stocks.join(',');
          return;
        }
        // Si es un solo item con stock numérico
        if (!Array.isArray(this.itemId) && (typeof this.stock === 'number' || typeof this.stock === 'string')) {
          const n = parseInt(this.stock) || 0;
          this.repeat = n > 0 ? n : 0;
          return;
        }
      }
      // Si no está activo el checkbox, no tocar el repeat (deja el valor manual)
    },
    setRepeatToStock() {
      if (this.stock > 0) {
        this.repeat = this.stock;
      }
    },
    handleClose() {
      this.localShow = false;
    },
    getPrintUrl() {
      let repeatValue = this.repeat;
      // Si hay varios productos y NO se usa el checkbox, repetir la cantidad para cada producto
      if (Array.isArray(this.itemId) && !this.useStockAsRepeat) {
        // Si repeat es un número, conviértelo a CSV para todos los productos
        const qty = parseInt(this.repeat) || 1;
        repeatValue = Array(this.itemId.length).fill(qty).join(',');
      }
      const params = new URLSearchParams({
        width: this.width,
        height: this.height,
        pageWidth: this.pageWidth,
        columns: this.columns,
        gapX: this.gapX,
        repeat: repeatValue,
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
      if (this.fromPurchase) {
            // Emite evento al padre para imprimir con cantidades de compra
            this.$emit('print-purchase-labels', {
                width: this.width,
                height: this.height,
                pageWidth: this.pageWidth,
                columns: this.columns,
                gapX: this.gapX,
                fields: this.fields,
            });
        } else {
            window.open(this.getPrintUrl(), '_blank');
        }
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