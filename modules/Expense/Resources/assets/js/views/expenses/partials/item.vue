<template>
    <el-dialog :title="titleDialog" :visible="showDialog"   @close="close">
        <form autocomplete="off" @submit.prevent="clickAddItem">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group" :class="{'has-danger': errors.description}">
                            <label class="control-label">
                                Descripción
                            </label>
                            <el-input type="textarea" autosize v-model="form.description"></el-input>
                            <small class="form-control-feedback" v-if="errors.description" v-text="errors.description[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group" :class="{'has-danger': errors.total}">
                            <label class="control-label">
                                Total
                            </label>
                            <el-input v-model="form.total" >
                                <template slot="prepend" v-if="currencyType">{{ currencyType.symbol }}</template>
                            </el-input>
                            <small class="form-control-feedback" v-if="errors.total" v-text="errors.total[0]"></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group col-12">
                            <label>Cuenta contable</label>
                            <el-select
                                v-model="form.chart_of_account_id"
                                filterable
                                remote
                                placeholder="Buscar cuenta contable"
                                :remote-method="remoteSearchAccounts"
                                :loading="loadingAccounts"
                                @visible-change="handleDropdownVisible"
                                style="width: 100%"
                            >
                                <el-option
                                    v-for="option in localAccounts"
                                    :key="option.id"
                                    :value="option.id"
                                    :label="option.code + ' - ' + option.name"
                                ></el-option>
                            </el-select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions text-right mt-4">
                <el-button @click.prevent="close()">Cerrar</el-button>
                <el-button type="primary" native-type="submit">Agregar</el-button>
            </div>
        </form>
    </el-dialog>
</template>

<script>
export default {
  props: ['showDialog', 'currencyType', 'chartOfAccounts'],
  data() {
    return {
      titleDialog: 'Agregar Detalle',
      errors: {},
      localAccounts: [],
      loadingAccounts: false,
      searchTimeout: null,
      form: {},
      defaultAccount: null, // cuenta 5195 completa (id, code, name)
    };
  },
  watch: {
    showDialog(val) {
      if (val) {
        this.initForm();
        this.ensureDefaultAccount();
      }
    },
  },
  methods: {
    // Busca la cuenta 5195 directamente y guarda su info
    async ensureDefaultAccount() {
      try {
        this.loadingAccounts = true;
        // Buscar cuenta 5195 (solo una vez)
        if (!this.defaultAccount) {
          const defaultAccount = await this.$http.get('/expenses/search-chart-accounts', {
            params: { search: '5195', limit: 1 },
          });
          if (defaultAccount.data.data.length > 0) {
            this.defaultAccount = defaultAccount.data.data[0]; // guardamos {id, code, name}
          }
        }

        // Cargar las primeras cuentas (sin filtro)
        await this.remoteSearchAccounts('');

        // Si la 5195 no está en el listado, la agregamos al final
        if (
          this.defaultAccount &&
          !this.localAccounts.some(acc => acc.id === this.defaultAccount.id)
        ) {
          this.localAccounts.push(this.defaultAccount);
        }

        // Asignar valor al select
        if (this.defaultAccount) {
          this.form.chart_of_account_id = this.defaultAccount.id;
        }
      } catch (error) {
        console.error('Error :', error);
      } finally {
        this.loadingAccounts = false;
      }
    },

    async remoteSearchAccounts(query = '') {
      if (this.searchTimeout) clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(async () => {
        this.loadingAccounts = true;
        try {
          const res = await this.$http.get('/expenses/search-chart-accounts', {
            params: { search: query, limit: 50 },
          });
          this.localAccounts = res.data.data;

          // Asegurar que la cuenta 5195 esté incluida (sin duplicar)
          if (
            this.defaultAccount &&
            !this.localAccounts.some(acc => acc.id === this.defaultAccount.id)
          ) {
            this.localAccounts.push(this.defaultAccount);
          }
        } finally {
          this.loadingAccounts = false;
        }
      }, 300);
    },

    handleDropdownVisible(visible) {
      if (visible && this.localAccounts.length === 0) {
        this.remoteSearchAccounts('');
      }
    },

    initForm() {
      this.errors = {};
      this.form = {
        description: null,
        total: null,
        total_original: null,
        currency_id: null,
        chart_of_account_id: null,
      };
    },

    close() {
      this.initForm();
      this.$emit('update:showDialog', false);
    },

    clickAddItem() {
      this.form.currency_id = this.currencyType.id;
      this.form.total_original = parseFloat(this.form.total);
      this.$emit('add', this.form);
      this.initForm();
    },
  },
};
</script>
