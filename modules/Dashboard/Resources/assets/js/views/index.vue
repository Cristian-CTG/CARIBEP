<template>
  <div v-if="typeUser == 'admin'">   
    <!-- <header
      class="page-header"
      style="display: flex; justify-content: space-between; align-items: center"
    >
      <div>
        <h2>Dashboard</h2>
      </div>
      <div class="d-flex align-items-center h-100">
        {{ filterLabel }}
        <el-button 
          type="primary"
          @click="toggleFilters"
          size="small"
          class="mx-2 p-2"
          :title="showFilters ? 'Ocultar filtros' : 'Mostrar filtros'"
        >
          <svg v-if="showFilters" xmlns="http://www.w3.org/2000/svg"  width="20"  height="20"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"
            stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-adjustments-alt">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 8h4v4h-4z" />
            <path d="M6 4l0 4" /><path d="M6 12l0 8" />
            <path d="M10 14h4v4h-4z" /><path d="M12 4l0 10" />
            <path d="M12 18l0 2" /><path d="M16 5h4v4h-4z" />
            <path d="M18 4l0 1" /><path d="M18 9l0 11" />
          </svg>

          <svg v-else xmlns="http://www.w3.org/2000/svg"  width="20"  height="20"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"
            stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-adjustments-off">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 10a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
            <path d="M6 6v2" /><path d="M6 12v8" /><path d="M10 16a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
            <path d="M12 4v4m0 4v2" /><path d="M12 18v2" />
            <path d="M16 7a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M18 4v1" />
            <path d="M18 9v5m0 4v2" /><path d="M3 3l18 18" />
          </svg>
        </el-button>
      </div>
    </header>  -->
    <div class="row">
      <div class="col-12" v-if="resolutions.length > 0">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
          <p class="mb-0"><strong>Las siguientes Resoluciones estan por vencer:</strong></p>
          <p class="mb-0" v-for="resolution in resolutions" :key="resolution.id">{{ resolution.prefix }} - {{ resolution.resolution_number }} - Fecha: {{ resolution.resolution_date_end }}</p>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>
      <div class="col-xl-12">
        <section class="card card-featured-left card-featured-secondary mb-3">
          <div class="card-body py-2 mt-0">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h4 class="card-title">Mi Plan</h4>
                <div v-if="electronicConsumption === null" class="loading-container">
                </div>
                <div v-else-if="!electronicConsumption.plan_name || electronicConsumption.plan_name === 'Sin plan'" class="alert alert-danger mb-0 py-2">
                  No tiene un plan asignado, comuníquese con su administrador.
                </div>
                <div v-else class="small text-muted">
                  <span class="mr-2"><strong>Plan:</strong> {{ electronicConsumption.plan_name }}</span>
                  <span class="mr-2"><strong>Estado:</strong> {{ electronicConsumption.plan_status }}</span>
                  <span class="mr-2"><strong>Vigencia:</strong> {{ electronicConsumption.plan_start }} - {{ electronicConsumption.plan_end }}</span>
                  <span><strong>Límite:</strong>{{ electronicConsumption.plan_limit_documents == 0 ? 'Ilimitado' : electronicConsumption.plan_limit_documents }}</span>
                </div>
              </div>
              <div v-if="electronicConsumption && electronicConsumption.plan_name && electronicConsumption.plan_name !== 'Sin plan'">
                <span class="badge badge-primary p-2">
                  <strong>Total:</strong>{{ electronicConsumption.total_documents }} / {{ electronicConsumption.plan_limit_documents == 0 ? 'Ilimitado' : electronicConsumption.plan_limit_documents }}
                </span>
              </div>
            </div>
            <div v-if="electronicConsumption === null" class="mt-2 text-center py-3">
              <i class="fas fa-circle-notch fa-spin text-primary fa-2x"></i>
            </div>
            <div v-else-if="electronicConsumption && electronicConsumption.plan_name && electronicConsumption.plan_name !== 'Sin plan'" class="mt-2">
              <table class="table table-sm table-borderless mb-0">
                <tbody>
                  <tr>
                    <template v-for="(count, type) in electronicConsumption.documents">
                      <td class="font-weight-bold text-muted text-center" :key="type">
                        {{ type }}
                        <span class="badge badge-info">{{ count }}</span>
                      </td>
                    </template>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </div>
      <div class="col-xl-12 px-0">
        <section class="card card-featured-secondary">
          <div class="card-body">
            <div class="filters-container">
              <div class="filter-item">
                <label class="control-label">Establecimiento</label>
                <el-select v-model="form.establishment_id" @change="loadAll" style="width: 100%">
                  <el-option
                    v-for="option in establishments"
                    :key="option.id"
                    :value="option.id"
                    :label="option.name"
                  ></el-option>
                </el-select>
              </div>
            
              <div class="filter-item">
                <label class="control-label">Periodo</label>
                <el-select v-model="form.period" @change="changePeriod" style="width: 100%">
                  <el-option key="all" value="all" label="Todos"></el-option>
                  <el-option key="month" value="month" label="Por mes"></el-option>
                  <el-option key="between_months" value="between_months" label="Entre meses"></el-option>
                  <el-option key="date" value="date" label="Por fecha"></el-option>
                  <el-option key="between_dates" value="between_dates" label="Entre fechas"></el-option>
                </el-select>
              </div>
            
              <template v-if="form.period === 'month' || form.period === 'between_months'">
                <div class="filter-item">
                  <label class="control-label">Mes de</label>
                  <el-date-picker
                    v-model="form.month_start"
                    type="month"
                    @change="changeDisabledMonths"
                    value-format="yyyy-MM"
                    format="MM/yyyy"
                    :clearable="false"
                    style="width: 100%"
                  ></el-date-picker>
                </div>
              </template>
            
              <template v-if="form.period === 'between_months'">
                <div class="filter-item">
                  <label class="control-label">Mes al</label>
                  <el-date-picker
                    v-model="form.month_end"
                    type="month"
                    :picker-options="pickerOptionsMonths"
                    @change="loadAll"
                    value-format="yyyy-MM"
                    format="MM/yyyy"
                    :clearable="false"
                    style="width: 100%"
                  ></el-date-picker>
                </div>
              </template>
            
              <template v-if="form.period === 'date' || form.period === 'between_dates'">
                <div class="filter-item">
                  <label class="control-label">Fecha del</label>
                  <el-date-picker
                    v-model="form.date_start"
                    type="date"
                    @change="changeDisabledDates"
                    value-format="yyyy-MM-dd"
                    format="dd/MM/yyyy"
                    :clearable="false"
                    style="width: 100%"
                  ></el-date-picker>
                </div>
              </template>
            
              <template v-if="form.period === 'between_dates'">
                <div class="filter-item">
                  <label class="control-label">Fecha al</label>
                  <el-date-picker
                    v-model="form.date_end"
                    type="date"
                    :picker-options="pickerOptionsDates"
                    @change="loadAll"
                    value-format="yyyy-MM-dd"
                    format="dd/MM/yyyy"
                    :clearable="false"
                    style="width: 100%"
                  ></el-date-picker>
                </div>
              </template>
            
              <div class="filter-item">
                <label class="control-label">
                  Moneda
                  <el-tooltip class="item" effect="dark" content="Filtra por moneda del documento emitido" placement="top-start">
                    <i class="fa fa-info-circle"></i>
                  </el-tooltip>
                </label>
                <el-select v-model="form.currency_id" filterable @change="loadAll" style="width: 100%">
                  <el-option v-for="option in currencies" :key="option.id" :value="option.id" :label="option.name"></el-option>
                </el-select>
              </div>
            </div>
          </div>
        </section>
      </div>      
    </div>
    
    <div class="row mb-0">

      <div class="col-xl-4 col-md-4">
        <section class="card card-featured-left card-featured-primary">
          <div class="card-body px-4 py-3">
            <div class="widget-summary">
              <div class="widget-summary-col">
                <div class="summary">
                  <h4 class="title d-flex justify-content-between">
                    Total General
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="18"  height="18"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-cash text-muted"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 15h-3a1 1 0 0 1 -1 -1v-8a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v3" /><path d="M7 9m0 1a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v8a1 1 0 0 1 -1 1h-12a1 1 0 0 1 -1 -1z" /><path d="M12 14a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /></svg>
                  </h4>
                  <div class="info mt-4">
                    <strong class="amount">{{ getCurrencySymbol() + ' ' + formatNumber(kpiTotalGeneral) }}</strong>
                  </div>
                  <div class="description mt-1">
                    <small class="text-muted">Facturas + Remisiones + POS</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
      
      <div class="col-xl-4 col-md-4">
        <section class="card card-featured-left card-featured-success">
          <div class="card-body px-4 py-3">
            <div class="widget-summary">
              <div class="widget-summary-col">
                <div class="summary">
                  <h4 class="title d-flex justify-content-between">                    
                    Total Pagado
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="18"  height="18"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-circle-check text-muted"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>
                  </h4>
                  <div class="info mt-4">
                    <strong class="amount">{{ getCurrencySymbol() + ' ' + formatNumber(kpiTotalPaid) }}</strong>
                  </div>
                  <div class="description mt-1">
                    <small class="text-muted">Facturas + Remisiones + POS</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
      
      <div class="col-xl-4 col-md-4">
        <section class="card card-featured-left card-featured-tertiary">
          <div class="card-body px-4 py-3">
            <div class="widget-summary">
              <div class="widget-summary-col">
                <div class="summary">
                  <h4 class="title d-flex justify-content-between text-warning">
                    Total por Pagar
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="18"  height="18"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-clock text-warning"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 7v5l3 3" /></svg>
                  </h4>
                  <div class="info mt-4">
                    <strong class="amount text-warning">{{ getCurrencySymbol() + ' ' + formatNumber(kpiTotalToPay) }}</strong>
                  </div>
                  <div class="description mt-1">
                    <small class="text-muted">Facturas + Remisiones + POS</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
          
    </div>
    
    <div class="row">
      <div class="col-xl-12">
        <div class="row">
          <div class="col-xl-4">
            <section class="card card-featured-left card-featured-secondary">
              <div class="card-body">
                <div class="widget-summary">
                  <div class="widget-summary-col">
                    <div class="row no-gutters">
                      <div class="col-md-12 m-b-10">
                        <h2 class="card-title">Facturas Electrónicas</h2>
                      </div>                      
                    </div>
                    <div class="row m-t-20">
                      <div class="col-md-12">
                        <x-graph type="doughnut" :all-data="sale_note.graph"></x-graph>
                      </div>
                    </div>
                    <div class="col-lg-12 d-flex flex-column text-center px-0" v-if="sale_note.totals.total_payment > 0 || sale_note.totals.total_to_pay > 0 || sale_note.totals.total > 0">
                       <div class="col-lg-12 px-0">                         
                         <div class="summary amount-container">
                            <h4 class="title text-muted">
                              Total &nbsp;
                            </h4>
                            <div>
                              <strong class="amount">{{ formatNumber(sale_note.totals.total) }}</strong>
                            </div>
                          </div>
                       </div>                       
                       <div class="col-lg-12 px-0 d-flex mt-3">
                        <div class="col-lg-6 px-0">
                          <div class="summary amount-container">
                           <h4 class="title text-muted">
                             Total Pagado
                           </h4>
                           <div>
                             <strong class="amount amount-small text-muted">{{ formatNumber(sale_note.totals.total_payment) }}</strong>
                           </div>
                         </div>
                        </div>
                        <div class="col-lg-6 px-0">
                          <div class="summary amount-container">
                            <h4 class="title amount-danger">
                              Monto pendiente
                            </h4>
                            <div>
                              <strong
                                class="amount amount-small amount-danger"
                              >{{ formatNumber(sale_note.totals.total_to_pay) }}</strong>
                            </div>
                          </div>
                        </div>                        
                       </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div>
          <div class="col-xl-4">
            <section class="card card-featured-left card-featured-secondary">
              <div class="card-body">
                <div class="widget-summary">
                  <div class="widget-summary-col">
                    <div class="row no-gutters">
                      <div class="col-md-12 m-b-10">
                        <h2 class="card-title">Remisiones</h2>
                      </div>                      
                    </div>
                    <div class="row m-t-20">
                      <div class="col-md-12">
                        <x-graph type="doughnut" :all-data="document.graph"></x-graph>
                      </div>
                    </div>
                    <div class="col-lg-12 d-flex flex-column text-center px-0" v-if="document.totals.total_payment > 0 || document.totals.total_to_pay > 0 || document.totals.total > 0">
                       <div class="col-lg-12 px-0">                         
                         <div class="summary amount-container">
                            <h4 class="title text-muted">
                              Total &nbsp;
                            </h4>
                            <div>
                              <strong class="amount">{{ formatNumber(document.totals.total) }}</strong>
                            </div>
                          </div>
                       </div>                       
                       <div class="col-lg-12 px-0 d-flex mt-3">
                        <div class="col-lg-6 px-0">
                          <div class="summary amount-container">
                           <h4 class="title text-muted">
                             Total Pagado
                           </h4>
                           <div>
                             <strong class="amount amount-small text-muted">{{ formatNumber(document.totals.total_payment) }}</strong>
                           </div>
                         </div>
                        </div>
                        <div class="col-lg-6 px-0">
                          <div class="summary amount-container">
                            <h4 class="title amount-danger">
                              Monto pendiente
                            </h4>
                            <div>
                              <strong
                                class="amount amount-small amount-danger"
                              >{{ formatNumber(document.totals.total_to_pay) }}</strong>
                            </div>
                          </div>
                        </div>                        
                       </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div>
          <div class="col-xl-4">
            <section class="card card-featured-left card-featured-secondary">
              <div class="card-body">
                <div class="widget-summary">
                  <div class="widget-summary-col">
                    <div class="row no-gutters">
                      <div class="col-md-12 m-b-10">
                        <h2 class="card-title">Ventas POS</h2>
                      </div>                      
                    </div>
                    <div class="row m-t-20">
                      <div class="col-md-12">
                        <x-graph type="doughnut" :all-data="document_pos.graph"></x-graph>
                      </div>
                    </div>
                    <div class="col-lg-12 d-flex flex-column text-center px-0" v-if="document_pos.totals.total_payment > 0 || document_pos.totals.total_to_pay > 0 || document_pos.totals.total > 0">
                       <div class="col-lg-12 px-0">                         
                         <div class="summary amount-container">
                            <h4 class="title text-muted">
                              Total &nbsp;
                            </h4>
                            <div>
                              <strong class="amount">{{ formatNumber(document_pos.totals.total) }}</strong>
                            </div>
                          </div>
                       </div>                       
                       <div class="col-lg-12 px-0 d-flex mt-3">
                        <div class="col-lg-6 px-0">
                          <div class="summary amount-container">
                           <h4 class="title text-muted">
                             Total Pagado
                           </h4>
                           <div>
                             <strong class="amount amount-small text-muted">{{ formatNumber(document_pos.totals.total_payment) }}</strong>
                           </div>
                         </div>
                        </div>
                        <div class="col-lg-6 px-0">
                          <div class="summary amount-container">
                            <h4 class="title amount-danger">
                              Monto pendiente
                            </h4>
                            <div>
                              <strong
                                class="amount amount-small amount-danger"
                              >{{ formatNumber(document_pos.totals.total_to_pay) }}</strong>
                            </div>
                          </div>
                        </div>                        
                       </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div>
          <!-- <div class="col-xl-6 col-md-6">
            <section class="card card-featured-left card-featured-secondary">
              <div class="card-body" v-if="general">
                <div class="widget-summary">
                  <div class="widget-summary-col">
                    <div class="summary">
                      <div class="row no-gutters">
                        <div class="col-md-12 m-b-10">
                          <h2 class="card-title">Totales</h2>
                        </div>
                        <div class="col-lg-4">
                          <div class="summary">
                            <h4 class="title text-danger">
                              Total
                              <br />notas de venta
                            </h4>
                            <div class="info">
                              <strong
                                class="amount text-danger"
                              >{{ general.totals.total_sale_notes }}</strong>
                            </div>
                          </div>
                        </div>
                        <div class="col-lg-4">
                          <div class="summary">
                            <h4 class="title text-info">
                              Total
                              <br />comprobantes
                            </h4>
                            <div class="info">
                              <strong
                                class="amount text-info"
                              >{{ general.totals.total_documents }}</strong>
                            </div>
                          </div>
                        </div>
                        <div class="col-lg-4">
                          <div class="summary">
                            <h4 class="title">
                              Total
                              <br />&nbsp;
                            </h4>
                            <div class="info">
                              <strong class="amount">{{ general.totals.total }}</strong>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="row m-t-20">
                        <div class="col-md-12">
                          <x-graph-line :all-data="general.graph"></x-graph-line>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div>


          <div class="col-xl-3 col-md-3">
            <section class="card card-featured-left card-featured-secondary">
              <div class="card-body" v-if="document">
                <div class="widget-summary">
                  <div class="widget-summary-col">
                    <div class="row no-gutters">
                      <div class="col-md-12 m-b-10 mb-4">
                        <h2 class="card-title">Balance Ventas - Compras - Gastos</h2>
                      </div>
                      <div class="col-lg-6">
                        <div class="summary">
                          <h4 class="title text-info">
                            Totales
                            <el-popover placement="right" width="100%" trigger="hover">
                              <p><span class="custom-badge">T. Ventas - T. Compras/Gastos</span></p>
                              <p>Total comprobantes:<span class="custom-badge pull-right">{{ balance.totals.total_document }}</span></p>
                              <p>Total notas de venta:<span class="custom-badge pull-right">{{ balance.totals.total_sale_note }}</span></p>
                              <p>Total compras:<span class="custom-badge pull-right">- {{ balance.totals.total_purchase }}</span></p>
                              <p>Total gastos:<span class="custom-badge pull-right">- {{ balance.totals.total_expense }}</span></p>
                              <el-button icon="el-icon-view" type="primary" size="mini" slot="reference" circle></el-button>
                            </el-popover>
                            <br />
                          </h4>
                          <div class="info">
                            <strong class="amount text-info">{{ balance.totals.all_totals }}</strong>
                          </div>
                        </div>
                      </div>
                      <div class="col-lg-6">
                        <div class="summary">
                          <h4 class="title text-danger">
                            Total Pagos
                            <el-popover placement="right" width="100%" trigger="hover">
                              <p><span class="custom-badge">T. Pagos Ventas - T. Pagos Compras/Gastos</span></p>
                              <p>Total pagos comprobantes:<span class="custom-badge pull-right">{{ balance.totals.total_payment_document }}</span></p>
                              <p>Total pagos notas de venta:<span class="custom-badge pull-right">{{ balance.totals.total_payment_sale_note }}</span></p>
                              <p>Total pagos compras:<span class="custom-badge pull-right">- {{ balance.totals.total_payment_purchase }}</span></p>
                              <p>Total pagos gastos:<span class="custom-badge pull-right">- {{ balance.totals.total_payment_expense }}</span></p>
                              <el-button icon="el-icon-view" type="danger" size="mini" slot="reference" circle></el-button>
                            </el-popover>
                            <br />
                          </h4>
                          <div class="info">
                            <strong class="amount text-danger">{{ balance.totals.all_totals_payment }}</strong>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="row m-t-20">
                      <div class="col-md-12">
                        <x-graph type="doughnut" :all-data="balance.graph"></x-graph>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div>


          <div class="col-xl-3 col-md-3">
            <section class="card card-featured-left card-featured-secondary">
              <div class="card-body" v-if="utilities">
                <div class="widget-summary">
                  <div class="widget-summary-col">
                    <div class="row no-gutters">
                      <div class="col-md-12 m-b-10">
                        <h2 class="card-title">Utilidades/Ganancias</h2>
                      </div>
                      <div class="col-lg-4">
                        <div class="summary">
                          <h4 class="title text-info">
                            Ingreso
                          </h4>
                          <div class="info">
                            <strong class="amount text-info">{{ utilities.totals.total_income }}</strong>
                          </div>
                        </div>
                      </div>
                      <div class="col-lg-4">
                        <div class="summary">
                          <h4 class="title text-danger">
                            Egreso
                          </h4>
                          <div class="info">
                            <strong class="amount text-danger">{{ utilities.totals.total_egress }}</strong>
                          </div>
                        </div>
                      </div>
                      <div class="col-lg-4">
                        <div class="summary">
                          <h4 class="title">
                            Utilidad
                            <br />&nbsp;
                          </h4>
                          <div class="info">
                            <strong class="amount">{{ utilities.totals.utility }}</strong>
                          </div>
                        </div>
                      </div>
                      <div class="col-lg-12 ">
                        <div class="summary">
                          <h4 class="title">
                            <br>
                            <el-checkbox  v-model="form.enabled_expense" @change="loadDataUtilities">Considerar gastos</el-checkbox><br>
                          </h4>
                        </div>
                      </div>
                      <div class="col-lg-12 ">
                        <div class="summary">
                          <h4 class="title">
                            <el-checkbox  v-model="filter_item" @change="changeFilterItem">Filtrar por producto</el-checkbox><br>
                          </h4>
                        </div>
                      </div>
                      <div class="col-lg-12 " v-if="filter_item">
                        <div class="summary">
                          <h4 class="title">
                            <div class="form-group">
                                <el-select v-model="form.item_id" filterable remote  popper-class="el-select-customers"  clearable
                                    placeholder="Buscar producto"
                                    :remote-method="searchRemoteItems"
                                    :loading="loading_search"
                                    @change="loadDataUtilities">
                                    <el-option v-for="option in items" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                </el-select>
                            </div>
                          </h4>
                        </div>
                      </div>
                    </div>
                    <div class="row m-t-20">
                      <div class="col-md-12">
                        <x-graph type="doughnut" :all-data="utilities.graph"></x-graph>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div> -->


          <!-- <div class="col-xl-6 col-md-6">
            <section class="card card-featured-left card-featured-secondary">
              <div class="card-body" v-if="general">
                <div class="widget-summary">
                  <div class="widget-summary-col">
                    <div class="summary">
                      <div class="row no-gutters">
                        <div class="col-md-12 m-b-10">
                          <h2 class="card-title">
                            Compras
                            <el-tooltip
                              class="item"
                              effect="dark"
                              content="Aplica filtro por establecimiento"
                              placement="top-start"
                            >
                              <i class="fa fa-info-circle"></i>
                            </el-tooltip>
                          </h2>
                        </div>
                        <div class="col-lg-4">
                          <div class="summary">
                            <h4 class="title text-danger">
                              Total
                              <br />percepciones
                            </h4>
                            <div class="info">
                              <strong
                                class="amount text-danger"
                              >{{ purchase.totals.purchases_total_perception }}</strong>
                            </div>
                          </div>
                        </div>
                        <div class="col-lg-4">
                          <div class="summary">
                            <h4 class="title text-info">
                              Total
                              <br />compras
                            </h4>
                            <div class="info">
                              <strong
                                class="amount text-info"
                              >{{ purchase.totals.purchases_total }}</strong>
                            </div>
                          </div>
                        </div>
                        <div class="col-lg-4">
                          <div class="summary">
                            <h4 class="title">
                              Total
                              <br />&nbsp;
                            </h4>
                            <div class="info">
                              <strong class="amount">{{ purchase.totals.total }}</strong>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="row m-t-20">
                        <div class="col-md-12">
                          <x-graph-line :all-data="purchase.graph"></x-graph-line>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div> -->

          <div class="col-xl-3 col-md-6">
            <section class="card card-featured-left">
              <div class="card-body">
                <h2 class="card-title">Ventas por producto</h2>
                <div class="mt-3">
                  <el-checkbox  v-model="form.enabled_move_item" @change="loadDataAditional">Ordenar por movimientos</el-checkbox><br>
                </div>
                <div class="table-responsive table-default">
                  <table class="table table-default">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th class="text-right">
                          Mov.
                            <el-tooltip
                              class="item"
                              effect="dark"
                              content="Movimientos (Cantidad de veces vendido)"
                              placement="top-start"
                            >
                              <i class="fa fa-info-circle"></i>
                            </el-tooltip>
                        </th>
                        <th class="text-right">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <template v-for="(row, index) in items_by_sales">
                        <tr :key="index">
                          <td>{{ index + 1 }}</td>
                          <td>{{ row.internal_id }}</td>
                          <td>{{ row.description }}</td>
                          <td class="text-right">{{ row.move_quantity }}</td>
                          <td class="text-right">{{ row.total | numberFormat }}</td>
                        </tr>
                      </template>
                    </tbody>
                  </table>
                </div>
              </div>
            </section>
          </div>
          <div class="col-xl-3 col-md-6">
            <section class="card card-featured-left">
              <div class="card-body">
                <h2 class="card-title">Top clientes</h2>
                <div class="mt-3">
                  <el-checkbox  v-model="form.enabled_transaction_customer" @change="loadDataAditional">Ordenar por transacciones</el-checkbox><br>
                </div>
                <div class="table-responsive table-default">
                  <table class="table table-default">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th class="text-right">
                          Trans.
                            <el-tooltip
                              class="item"
                              effect="dark"
                              content="Transacciones (Cantidad de ventas realizadas)"
                              placement="top-start"
                            >
                              <i class="fa fa-info-circle"></i>
                            </el-tooltip>
                        </th>
                        <th class="text-right">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <template v-for="(row, index) in top_customers">
                        <tr :key="index">
                          <td>{{ index + 1 }}</td>
                          <td>
                            {{ row.name }}
                            <br />
                            <small v-text="row.number"></small>
                          </td>
                          <td class="text-right">{{ row.transaction_quantity }}</td>
                          <td class="text-right">{{ row.total | numberFormat }}</td>
                        </tr>
                      </template>
                    </tbody>
                  </table>
                </div>
              </div>
            </section>
          </div>

          <div class="col-xl-6 col-md-12 col-lg-12">
            <dashboard-stock></dashboard-stock>
          </div>

        </div>
      </div>

      <div class="col-xl-4"></div>
    </div>
  </div>
</template>
<style>
.widget-summary .summary {
  min-height: inherit;
}

.custom-badge {
  font-size: 15px;
  font-weight: bold;
}

.widget-summary .summary .title {
  font-size: .875rem;
  font-weight: 500;
}

.widget-summary .summary .amount {
  font-size: 24px;
  font-weight: bold;
}
.filters-container {
  display: flex;
  flex-wrap: nowrap;
  gap: 12px;
  align-items: flex-end;
}

.filter-item {
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
}
.amount-container h4.title{
  font-size: .80rem !important;
  font-weight: normal !important;
}
.amount-small{
  font-size: 18px !important;
}
.amount-danger{
  color: #e9797e !important;
}
.loading-container {
  display: flex;
  align-items: center;
  padding: 10px 0;
}
.loading-container i {
  font-size: 18px;
}
@media (max-width: 525px) {
  .filters-container {
    flex-wrap: wrap;
    flex-direction: column;
    gap: 15px;
  }

  .filter-item {
    width: 100% !important;
  }

  .filter-item el-select,
  .filter-item el-date-picker {
    width: 100% !important;
  }
}
@media only screen and (min-width: 768px) {
    html.fixed .inner-wrapper {
        padding-top: 60px;
    }
}
</style>
<script>
// import DocumentPayments from "../../../../../../resources/js/views/tenant/documents/partials/payments.vue";
// import SaleNotePayments from "../../../../../../resources/js/views/tenant/sale_notes/partials/payments.vue";
import DashboardStock from "./partials/dashboard_stock.vue";
import queryString from "query-string";

export default {
  props: ["typeUser", "soapCompany"],
  components: { DashboardStock },
  data() {
    return {
      showFilters: false,
      loading_search:false,
      records_base: [],
      selected_customer: null,
      customers: [],
      resource: "dashboard",
      establishments: [],
      balance: {
        totals: {},
        graph: {}
      },
      document: {
        totals: {},
        graph: {}
      },
      sale_note: {
        totals: {},
        graph: {}
      },
      document_pos: {
        totals: {},
        graph: {}
      },
      general: {
        totals: {},
        graph: {}
      },
      purchase: {
        totals: {},
        graph: {}
      },
      utilities: {
        totals: {},
        graph: {}
      },
      disc: [],
      form: {},
      pickerOptionsDates: {
        disabledDate: time => {
          time = moment(time).format("YYYY-MM-DD");
          return this.form.date_start > time;
        }
      },
      pickerOptionsMonths: {
        disabledDate: time => {
          time = moment(time).format("YYYY-MM");
          return this.form.month_start > time;
        }
      },
      records: [],
      items_by_sales: [],
      top_customers: [],
      recordId: null,
      showDialogDocumentPayments: false,
      showDialogSaleNotePayments: false,
      filter_item:false,
      all_items: [],
      items:[],
      currencies:[],
      resolutions: [],
      electronicConsumption: null,
    };
  },
  async created() {
    this.initForm();
    await this.$http.get(`/${this.resource}/filter`).then(response => {
      this.establishments = response.data.establishments;
      this.currencies = response.data.currencies;
      this.form.establishment_id =
        this.establishments.length > 0 ? this.establishments[0].id : null;
    });
    await this.loadAll();
    await this.filterItems();
    await this.getResolutions();
    await this.loadElectronicConsumption();
    // this.$eventHub.$on("reloadDataUnpaid", () => {
    //   this.loadAll();
    // });
  },
  computed: {
    filterLabel() {
      const period = this.form.period;

      if (!period || period === "all") {
        return "Filtrado: Todos los registros";
      }

      if (period === "month" && this.form.month_start) {
        return `Filtrado por mes: ${this.formatMonth(this.form.month_start)}`;
      }

      if (period === "between_months" && this.form.month_start && this.form.month_end) {
        return `Filtrado entre meses: ${this.formatMonth(this.form.month_start)} - ${this.formatMonth(this.form.month_end)}`;
      }

      if (period === "date" && this.form.date_start) {
        return `Filtrado por fecha: ${this.formatDate(this.form.date_start)}`;
      }

      if (period === "between_dates" && this.form.date_start && this.form.date_end) {
        return `Filtrado entre fechas: ${this.formatDate(this.form.date_start)} - ${this.formatDate(this.form.date_end)}`;
      }

      return "Filtrado: Sin datos seleccionados";
    },
    kpiTotalPaid()    { return this.getTotalPaid(); },
    kpiTotalToPay()   { return this.getTotalToPay(); },
    kpiTotalGeneral() { return this.getTotalGeneral(); },
  },
  methods: {
    toNumber(v) {
      if (v === null || v === undefined) return 0;
      const cleaned = String(v).replace(/[^\d.-]/g, '');
      const n = Number(cleaned);
      return isNaN(n) ? 0 : n;
    },
    
    getTotalPaid() {
      const saleNotePaid   = this.toNumber(this.sale_note?.totals?.total_payment);
      const documentPaid   = this.toNumber(this.document?.totals?.total_payment);
      const documentPosPaid= this.toNumber(this.document_pos?.totals?.total_payment);
      return saleNotePaid + documentPaid + documentPosPaid;
    },

    getTotalToPay() {
      const saleNoteToPay   = this.toNumber(this.sale_note?.totals?.total_to_pay);
      const documentToPay   = this.toNumber(this.document?.totals?.total_to_pay);
      const documentPosToPay= this.toNumber(this.document_pos?.totals?.total_to_pay);
      return saleNoteToPay + documentToPay + documentPosToPay;
    },

    getTotalGeneral() {
      const saleNoteTotal   = this.toNumber(this.sale_note?.totals?.total);
      const documentTotal   = this.toNumber(this.document?.totals?.total);
      const documentPosTotal= this.toNumber(this.document_pos?.totals?.total);
      return saleNoteTotal + documentTotal + documentPosTotal;
    },
    getCurrencySymbol() {
      const selectedCurrency = this.currencies.find(currency => currency.id === this.form.currency_id);
      return selectedCurrency?.symbol || '$';
    },
    toggleFilters() {
      this.showFilters = !this.showFilters;
    },
    formatNumber(number) {
      if (number === undefined || number === null || number === 0) return '0.00';
      
      // Convertir a número y formatear con 2 decimales
      const num = parseFloat(number);
      if (num === 0) return '0.00';
      
      // Formatear con separador de miles (coma) y 2 decimales
      return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    },
    changeFilterItem(){
      this.form.item_id = null
      this.loadDataUtilities()
    },
    searchRemoteItems(input) {

        if (input.length > 1) {

            this.loading_search = true
            let parameters = `input=${input}`


            this.$http.get(`/reports/data-table/items/?${parameters}`)
                    .then(response => {
                        this.items = response.data.items
                        this.loading_search = false

                        if(this.items.length == 0){
                            this.filterItems()
                        }
                    })
        } else {
            this.filterItems()
        }

    },
    filterItems() {
        this.items = this.all_items
    },
    calculateTotalCurrency(currency_type_id, exchange_rate_sale, total) {
        if(currency_type_id == 'USD') {
            return parseFloat(total) * exchange_rate_sale;
        } else {
            return parseFloat(total);
        }
    },
    clickDownloadDispatch(download) {
      window.open(download, "_blank");
    },
    clickDownload(type) {
      let query = queryString.stringify({
        ...this.form
      });
      window.open(`/reports/no_paid/${type}/?${query}`, "_blank");
    },
    initForm() {
      this.form = {
        currency_id: 170,
        item_id: null,
        establishment_id: null,
        enabled_expense: null,
        enabled_move_item:false,
        enabled_transaction_customer:false,
        period: "month",
        date_start: moment().format("YYYY-MM-DD"),
        date_end: moment().format("YYYY-MM-DD"),
        month_start: moment().format("YYYY-MM"),
        month_end: moment().format("YYYY-MM"),
        customer_id: null
      };
    },
    changeDisabledDates() {
      if (this.form.date_end < this.form.date_start) {
        this.form.date_end = this.form.date_start;
      }
      this.loadAll();
    },
    changeDisabledMonths() {
      if (this.form.month_end < this.form.month_start) {
        this.form.month_end = this.form.month_start;
      }
      this.loadAll();
    },
    changePeriod() {
      if (this.form.period === "month") {
        this.form.month_start = moment().format("YYYY-MM");
        this.form.month_end = moment().format("YYYY-MM");
      }
      if (this.form.period === "between_months") {
        this.form.month_start = moment()
          .startOf("year")
          .format("YYYY-MM"); 
        this.form.month_end = moment()
          .endOf("year")
          .format("YYYY-MM");
      }
      if (this.form.period === "date") {
        this.form.date_start = moment().format("YYYY-MM-DD");
        this.form.date_end = moment().format("YYYY-MM-DD");
      }
      if (this.form.period === "between_dates") {
        this.form.date_start = moment()
          .startOf("month")
          .format("YYYY-MM-DD");
        this.form.date_end = moment()
          .endOf("month")
          .format("YYYY-MM-DD");
      }
      this.loadAll();
    },
    loadAll() {
      this.loadData();
     // this.loadUnpaid();
      this.loadDataAditional();
      // this.loadDataUtilities();
      //this.loadCustomer();
    },
    loadData() {
      this.$http.post(`/${this.resource}/data`, this.form).then(response => {
        this.document = response.data.data.document; // remisiones - remission
        this.document_pos = response.data.data.document_pos;
        this.balance = response.data.data.balance;
        this.sale_note = response.data.data.sale_note;
        this.general = response.data.data.general;
        this.customers = response.data.data.customers;
        this.items = response.data.data.items;
      });
      this.$http.get(`/command/df`).then(response => {
        if (response.data[0] != 'error'){
          this.disc.used = Number(response.data[0].replace(/[^0-9\.]+/g,""));
          this.disc.avail = Number(response.data[1].match(/\d/g).join(""));
          this.disc.pcent = Number(response.data[2].match(/\d/g).join(""));
        } else {
          this.disc.error = true;
        }
      });
    },
    loadDataAditional() {
      this.$http
        .post(`/${this.resource}/data_aditional`, this.form)
        .then(response => {
          this.purchase = response.data.data.purchase;
          this.items_by_sales = response.data.data.items_by_sales;
          this.top_customers = response.data.data.top_customers;
        });
    },
    loadDataUtilities() {
      this.$http
        .post(`/${this.resource}/utilities`, this.form)
        .then(response => {
          this.utilities = response.data.data.utilities;
        });
    },
    getResolutions() {
      axios.get(`/co-configuration-all`).then(response => {
        this.resolutions = this.expiringResolutions(response.data.typeDocuments);
      }).catch(error => {
        console.error(error)
      }).then(() => {});
    },
    expiringResolutions(resolutions) {
      const today = new Date();
      const limitDate = new Date();
      limitDate.setDate(today.getDate() + 15);

      return resolutions.filter((resolution) => {
        if (resolution.resolution_date_end) {
          const resolutionEndDate = new Date(resolution.resolution_date_end);
          return resolutionEndDate >= today && resolutionEndDate <= limitDate;
        }
        return false;
      });
    },
    formatMonth(date) {
      const [year, month] = date.split("-");
      return `${month}/${year}`;
    },
    formatDate(date) {
      const [year, month, day] = date.split("-");
      return `${day}/${month}/${year}`;
    },
    async loadElectronicConsumption() {
      await this.$http.get('/dashboard/electronic-consumption').then(response => {
        this.electronicConsumption = response.data;
      });
    },
  }
};
</script>
