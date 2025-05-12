<template>
    <div>
    <el-dialog :title="titleDialog" :visible="showDialog" @close="close" @open="loadData" width="80%">
        <div>
            <el-tabs v-model="activeModule">
                <el-tab-pane label="Ventas" name="sale">
                    <div class="row">
                        <div class="col-md-10">
                            <div class="form-group">
                                <label class="control-label">Configura las cuentas contables de ingresos y devoluciones de tus clientes con su Clasificación contable.</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-custom btn-sm mt-2 mr-2" @click.prevent="openForm()">
                                <i class="fa fa-plus-circle"></i> Nuevo
                            </button>
                        </div>
                    </div>
                   <div class="row" v-for=" row in account_sale_configurations">
                      <div class="col-md-4">
                          <div class="form-group">
                              <label class="control-label">Ingresos</label>
                              <el-select v-model="row.income_account" filterable >
                                  <el-option v-for="option in chart_accounts_sales" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group">
                              <label class="control-label">	Devoluciones en ventas</label>
                              <el-select v-model="row.sales_returns_account" filterable >
                                  <el-option v-for="option in chart_accounts_purchases" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                          </div>
                      </div>
                      <div class="col-md-3">
                          <div class="form-group">
                              <label class="control-label">	Clasificación contable</label>
                              <el-input v-model="row.accounting_clasification" :readonly="true"/>
                          </div>
                      </div>
                      <div class="col-md-1 d-flex align-items-end">
                        <div class="form-group">
                            <button type="button" 
                                    class="btn btn-xs btn-warning waves-effect waves-light w-100" 
                                    @click.prevent="openForm(row.id)">
                            Editar
                            </button>
                        </div>
                      </div>
                  </div>
                </el-tab-pane>
                <el-tab-pane label="Inventario" name="inventory">
                  <div class="row">
                      <div class="col-md-12">
                          <div class="form-group">
                              <label class="control-label">Selecciona la cuenta que se va a utilizar para el registro de entradas, salidas y ajustes de inventario.</label>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group" :class="{'has-danger': errors.inventory_account}">
                              <label class="control-label">Inventario</label>
                              <el-select v-model="form.inventory_account" filterable >
                                  <el-option v-for="option in chart_accounts_sales" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                              <small class="form-control-feedback" v-if="errors.inventory_account" v-text="errors.inventory_account[0]"></small>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group" :class="{'has-danger': errors.inventory_adjustment_account}">
                              <label class="control-label">	Ajustes de Inventario</label>
                              <el-select v-model="form.inventory_adjustment_account" filterable >
                                  <el-option v-for="option in chart_accounts_purchases" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                              <small class="form-control-feedback" v-if="errors.inventory_adjustment_account" v-text="errors.inventory_adjustment_account[0]"></small>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group" :class="{'has-danger': errors.sale_cost_account}">
                              <label class="control-label">	Costo de ventas</label>
                              <el-select v-model="form.sale_cost_account" filterable >
                                  <el-option v-for="option in chart_accounts_purchases" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                              <small class="form-control-feedback" v-if="errors.sale_cost_account" v-text="errors.sale_cost_account[0]"></small>
                          </div>
                      </div>
                  </div>
                  <div class="form-actions text-right pt-2">
                    <el-button @click.prevent="close()">Cancelar</el-button>
                    <el-button type="primary" @click.prevent="submit()" :loading="loading_submit" >Guardar</el-button>
                  </div>
                </el-tab-pane>
                <el-tab-pane label="Clientes y Proveedores" name="customer_supplier">
                  <div class="row">
                      <div class="col-md-12">
                          <div class="form-group">
                              <label class="control-label">Selecciona la cuenta que recibirá el movimiento de tus transacciones con clientes y proveedores.</label>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group" :class="{'has-danger': errors.customer_receivable_account}">
                              <label class="control-label">Cuenta por cobrar clientes</label>
                              <el-select v-model="form.customer_receivable_account" filterable >
                                  <el-option v-for="option in chart_accounts_sales" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                              <small class="form-control-feedback" v-if="errors.customer_receivable_account" v-text="errors.customer_receivable_account[0]"></small>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group" :class="{'has-danger': errors.customer_returns_account}">
                              <label class="control-label">	Devoluciones de clientes</label>
                              <el-select v-model="form.customer_returns_account" filterable >
                                  <el-option v-for="option in chart_accounts_purchases" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                              <small class="form-control-feedback" v-if="errors.customer_returns_account" v-text="errors.customer_returns_account[0]"></small>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group" :class="{'has-danger': errors.supplier_payable_account}">
                              <label class="control-label">Cuentas de proveedores</label>
                              <el-select v-model="form.supplier_payable_account" filterable >
                                  <el-option v-for="option in chart_accounts_sales" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                              <small class="form-control-feedback" v-if="errors.supplier_payable_account" v-text="errors.supplier_payable_account[0]"></small>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group" :class="{'has-danger': errors.supplier_returns_account}">
                              <label class="control-label">	Devoluciones de proveedores</label>
                              <el-select v-model="form.supplier_returns_account" filterable >
                                  <el-option v-for="option in chart_accounts_purchases" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                              <small class="form-control-feedback" v-if="errors.supplier_returns_account" v-text="errors.supplier_returns_account[0]"></small>
                          </div>
                      </div>
                  </div>
                  <div class="form-actions text-right pt-2">
                    <el-button @click.prevent="close()">Cancelar</el-button>
                    <el-button type="primary" @click.prevent="submit()" :loading="loading_submit" >Guardar</el-button>
                  </div>
                </el-tab-pane>
                <el-tab-pane label="Patrimonio" name="equity">
                  <div class="row">
                      <div class="col-md-12">
                          <div class="form-group">
                              <label class="control-label">Selecciona la cuenta en la que se van a registrar los resultados de la empresa y los ajustes por saldos iniciales.</label>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group" :class="{'has-danger': errors.retained_earning_account}">
                              <label class="control-label">Ganancias acumuladas</label>
                              <el-select v-model="form.retained_earning_account" filterable >
                                  <el-option v-for="option in chart_accounts_sales" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                              <small class="form-control-feedback" v-if="errors.retained_earning_account" v-text="errors.retained_earning_account[0]"></small>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group" :class="{'has-danger': errors.profit_period_account}">
                              <label class="control-label">	Utilidad del ejercicio</label>
                              <el-select v-model="form.profit_period_account" filterable >
                                  <el-option v-for="option in chart_accounts_purchases" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                              <small class="form-control-feedback" v-if="errors.profit_period_account" v-text="errors.profit_period_account[0]"></small>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group" :class="{'has-danger': errors.lost_period_account}">
                              <label class="control-label">Pérdida del ejercicio</label>
                              <el-select v-model="form.lost_period_account" filterable >
                                  <el-option v-for="option in chart_accounts_sales" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                              <small class="form-control-feedback" v-if="errors.lost_period_account" v-text="errors.lost_period_account[0]"></small>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group" :class="{'has-danger': errors.adjustment_opening_balance_banks_account}">
                              <label class="control-label">	Ajustes por saldos iniciales en bancos</label>
                              <el-select v-model="form.adjustment_opening_balance_banks_account" filterable >
                                  <el-option v-for="option in chart_accounts_purchases" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                              <small class="form-control-feedback" v-if="errors.adjustment_opening_balance_banks_account" v-text="errors.adjustment_opening_balance_banks_account[0]"></small>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="form-group" :class="{'has-danger': errors.adjustment_opening_balance_banks_inventory}">
                              <label class="control-label">	Ajustes por saldos iniciales en inventarios</label>
                              <el-select v-model="form.adjustment_opening_balance_banks_inventory" filterable >
                                  <el-option v-for="option in chart_accounts_purchases" :key="option.code" :value="option.code" :label="`${option.code} - ${option.name}`"></el-option>
                              </el-select>
                              <small class="form-control-feedback" v-if="errors.adjustment_opening_balance_banks_inventory" v-text="errors.adjustment_opening_balance_banks_inventory[0]"></small>
                          </div>
                      </div>
                  </div>
                  <div class="form-actions text-right pt-2">
                    <el-button @click.prevent="close()">Cancelar</el-button>
                    <el-button type="primary" @click.prevent="submit()" :loading="loading_submit" >Guardar</el-button>
                  </div>
                </el-tab-pane>
            </el-tabs>
        </div>
    </el-dialog>

    <chart-form-sale :showDialog.sync="showDialogForm" :recordId="recordId"></chart-form-sale>
    </div>
  </template>
  
  <script>
  import ChartFormSale from "./formSale.vue";

  export default {
    components: { ChartFormSale},
    props: ["showDialog"],
    data() {
      return {
        titleDialog: 'Parametrización contable',
        resource: "accounting/charts",
        showDialogForm: false,
        recordId: null,
        activeModule: 'sale',
        form: {},
        account_sale_configurations: [],
        chart_accounts_sales: [],
        chart_accounts_purchases: [],
        loading_submit: false,
        errors: {},
      }
    },
    created() {
        this.initForm();
        this.$eventHub.$on('reloadData', () => {
            this.loadData()
        })
    },
    methods: {
        initForm(){
          this.form = {
            inventory_account: null,
            inventory_adjustment_account: null,
            sale_cost_account: null,
            customer_receivable_account: null,
            customer_returns_account: null,
            supplier_payable_account: null,
            supplier_returns_account: null,
            retained_earning_account: null,
            profit_period_account: null,
            lost_period_account: null,
            adjustment_opening_balance_banks_account: null,
            adjustment_opening_balance_banks_inventory: null
          };
          
        },
        openForm(recordId = null) {
            this.recordId = recordId;
            this.showDialogForm = true;
        },
        async loadData() {
            this.initForm();
            await this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    if(response.data.chart_account_configurations){
                        this.form = response.data.chart_account_configurations
                    }
                    this.account_sale_configurations = response.data.account_sale_configurations
                    this.chart_accounts_sales = response.data.chart_accounts_sales
                    this.chart_accounts_purchases = response.data.chart_accounts_purchases
                })
        },
        async submit() {
            this.loading_submit = true;
            const method = "post";
            const url = `/${this.resource}/accounts-configuration`;

            try {
                const response = await this.$http[method](url, this.form);
                this.$message.success(response.data.message);
                this.close();
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data;
                }
            } finally {
                this.loading_submit = false;
            }
        },
        close() {
            this.$emit("update:showDialog", false);
        },
    }

  }
  </script>
  