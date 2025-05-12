<template>
    <el-dialog :title="title" :visible="showDialog" @close="close" @open="getData" width="50%">
        <div class="form-body">
            <div class="row">             
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Prefijo</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(row, index) in records" :key="index">
                                <template v-if="row.id">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ row.prefix }}</td>
                                    <td>{{ row.description }}</td>
                                    <td>
                                        <el-button type="primary" size="mini" @click="edit(row)">Editar</el-button>
                                        <el-button type="danger" size="mini" @click="remove(row.id)">Eliminar</el-button>
                                    </td>
                                </template>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- FORMULARIO DEBAJO DE LA TABLA -->
                    <div class="mt-4 p-3 border bg-light rounded">
                        <el-form :model="form" label-width="100px">
                        <el-form-item label="Prefijo">
                            <el-input v-model="form.prefix"></el-input>
                        </el-form-item>
                        <el-form-item label="Descripción">
                            <el-input v-model="form.description"></el-input>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" @click="submitForm">
                            {{ form.id ? 'Actualizar' : 'Agregar' }}
                            </el-button>
                            <el-button @click="resetForm">Cancelar</el-button>
                        </el-form-item>
                        </el-form>
                    </div>
                </div>
            </div>
        </div>

    </el-dialog>

</template>

<script>

    export default {
        props: ['showDialog'],
        mixins: [],
        data() {
            return {
                title: 'Prefijos Parametrizables',
                resource: 'accounting/journal/prefixes',
                records: [],
                showAddButton: true,
                form: {
                    id: null,
                    prefix: '',
                    description: '',
                    modifiable:true
                },
            }
        },
        async created() {

        },
        methods: {
            async getData() {
                await this.$http.get(`/${this.resource}`)
                    .then(response => {
                        this.records = response.data;
                    });
                this.$eventHub.$emit('reloadDataUnpaid')

            },
            close() {
                this.$emit('update:showDialog', false);
            },
            resetForm() {
                this.form = { id: null, prefix: '', description: '',modifiable:true };
            },
            edit(row) {
                this.form = { ...row };
            },
            async submitForm() {
                if (this.form.id) {
                    // Actualizar
                    await this.$http.put(`/${this.resource}/${this.form.id}`, this.form);
                } else {
                    // Crear nuevo
                    await this.$http.post(`/${this.resource}`, this.form);
                }
                await this.getData();
                this.resetForm();
            },
            async remove(id) {
                if (confirm('¿Estás seguro de eliminar este prefijo?')) {
                    await this.$http.delete(`/${this.resource}/${id}`);
                    await this.getData();
                }
            },
        }
    }
</script>
