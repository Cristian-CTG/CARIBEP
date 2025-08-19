<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="#">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        style="margin-top: -5px;"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="icon icon-tabler icons-tabler-outline icon-tabler-file-analytics"
                    >
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                        <path
                            d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"
                        />
                        <path d="M9 17l0 -5" />
                        <path d="M12 17l0 -1" />
                        <path d="M15 17l0 -3" />
                    </svg>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active"><span> Consulta de Documentos </span></li>
            </ol>
        </div>
        <div class="card mb-0 pt-2 pt-md-0">        
            <div class="card mb-0">
                <div class="card-body">
                    <data-table :applyCustomer="true" :resource="resource" :colspan="11">
                        <tr slot="heading">
                            <!-- <th class="">#</th> -->
                            <th class="">Usuario/Vendedor</th>
                            <th class="">Tipo Documento</th>
                            <th class="">Comprobante</th>
                            <th class="">Fecha emisión</th>
                            <th>Doc. Afectado</th>
                            <th>Cotización</th>
                            <th>Caso</th>
                            <th class="">Cliente</th>
                            <th class="">Estado</th>
                            <th class="">Moneda</th>
                            <th class="text-right">Total</th>
                        </tr>
                        <tr slot-scope="{ index, row }">
                            <!-- <td>{{ index }}</td> -->
                            <td>{{row.user_name}}</td>
                            <td>{{row.document_type_description}}</td>
                            <td>{{row.number}}</td>
                            <td>{{row.date_of_issue}}</td>
                            <td>{{row.affected_document}}</td>
                            <td>{{row.quotation_number_full}}</td>
                            <td>{{row.sale_opportunity_number_full}}</td>
                            <td>{{ row.customer_name }}<br/><small v-text="row.customer_number"></small></td>
                            <td>{{row.state_type_description}}</td>
                            <td>{{ row.currency_type_id}}</td>
                            <td class="text-right">
                                {{ formatNumber(row.total) }}
                                <!-- {{ (row.document_type_id == '07') ? ( (row.total == 0) ? '0.00': '-'+row.total) : ((row.document_type_id!='07' && (row.state_type_id =='11'||row.state_type_id =='09')) ? '0.00':row.total) }} -->
                            </td>
                        </tr>
                    </data-table>
                </div>
            </div>
        </div>
    </div>    
</template>

<script>
import DataTable from '../../components/DataTableReports.vue'
export default {
    components: {DataTable},
    data() {
        return {
            resource: 'reports/sales',
            form: {},
        }
    },
    created() {},
    methods: {
        formatNumber(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",").replace(/\.(\d{2})/, ".$1");
        },
    }
}
</script>
