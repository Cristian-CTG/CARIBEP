<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Cupones</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickCreate()"><i class="fa fa-plus-circle"></i> Nuevo</button>
            </div>
        </div>
        <div class="card mb-m0">
            <div class="card-header bg-info">
                <h3 class="my-0">Listado de cupones</h3>
            </div>
            <div class="card-body">
                <data-table :resource="resource" :loading="loadDataTable">
                    <tr slot="heading" width="100%">
                        <th>#</th>
                        <th>Título</th>
                        <th>Descripción</th>
                        <th>Monto mínimo compras</th>
                        <th>Tienda</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>{{ row.title }}</td>
                        <td>{{ row.description }}</td>
                        <td>{{ row.minimum_purchase_amount }}</td>
                        <td>{{ row.establishment }}</td>
                        <td>{{ row.coupon_date }}</td>
                        <td>{{ (row.status)?'Activo':'Inactivo' }}</td>
                        <td class="text-right">
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickCreate(row.id)">Editar</button>
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-danger" @click.prevent="clickDelete(row.id)">Eliminar</button>
                        </td>
                    </tr>
                </data-table>
            </div>
                <coupons-form :showDialog.sync="showDialog" :recordId="recordId"></coupons-form>
            </div>
    </div>
</template>
<script>

    import CouponsForm from './form.vue'
    import DataTable from '@components/DataTableCoupon.vue'
    import {deletable} from '@mixins/deletable'

    export default {
        props: {
            route: {
                required: true
            },
        },
        mixins: [deletable],
        components: {CouponsForm, DataTable},
        data() {
            return {
                showDialog: false,
                showImportDialog: false,
                showImportListPriceDialog: false,
                showWarehousesDetail: false,
                loadDataTable: false,
                resource: 'co-coupon',
                recordId: null,
                warehousesDetail:[],
                config: {}
            }
        },
        created() {

        },
        methods: {
            clickCreate(recordId = null) {
                this.recordId = recordId
                this.showDialog = true
            },
            clickDelete(id) {
                this.destroy(`/${this.resource}/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            },
            refreshData() {
                this.loadDataTable = true;
                this.$eventHub.$emit('reloadData')
                this.loadDataTable = false;
            },
        }
    }
</script>
