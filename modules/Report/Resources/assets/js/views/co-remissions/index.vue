<template>
<div>
    <div class="page-header pr-0">
        <h2>
            <a href="/reports/co-remissions">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    style="margin-top: -5px;"
                    width="20"
                    height="20"
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
            <li class="active"><span> Reporte de Remisiones </span></li>
        </ol>
    </div>
    <div class="card mb-0 pt-2 pt-md-0">
        <!-- <div class="card-header bg-info">
            <h3 class="my-0">Reporte de Remisiones</h3>
        </div> -->
        <div class="card mb-0">
                <div class="card-body">
                    <data-table :resource="resource">
                        <tr slot="heading">
                            <!-- <th>#</th> -->
                            <th>Fecha Emisión</th>
                            <th class="">Usuario/Vendedor</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th>Remisión</th>
                            <th>Cotización</th>
                            <th class="text-center">Moneda</th>
                            <th class="text-center">Total</th>
                        <tr>
                        <tr slot-scope="{ index, row }">
                            <!-- <td>{{ index }}</td>   -->
                            <td>{{row.date_of_issue}}</td>
                            <td>{{row.user_name}}</td>
                            <td>{{ row.customer_name }}<br/><small v-text="row.customer_number"></small></td>
                            <td>{{row.state_type_description}}</td>
                            <td>{{row.number_full}}</td>
                            <td>{{row.quotation_number_full}}</td>
 
                            <td class="text-center">{{row.currency_name}}</td>
                            <td class="text-center">{{ row.total}}</td>
                            
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
                resource: 'reports/co-remissions',                 
                form: {}, 

            }
        },
        async created() { 
        },
        methods: { 
             
            
        }
    }
</script>