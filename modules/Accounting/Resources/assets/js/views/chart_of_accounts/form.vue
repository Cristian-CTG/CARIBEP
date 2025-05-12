<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @close="close" @open="loadData">
        <form @submit.prevent="submit">
            <div class="row">
                <!-- Selector de Clase -->
                <div class="col-12">
                    <div class="form-group">
                        <label>Clase</label>
                        <el-select v-model="form.parent_id" :loading="loadingHierarchy" placeholder="Seleccione una clase (opcional)" @change="handleParentSelection">
                            <el-option v-for="account in parentAccounts" :key="account.id" :label="account.code + ' - ' + account.name" :value="account.id" />
                        </el-select>
                        <!-- Spinner encima del select -->
                        <div v-if="loadingHierarchy"
                            class="custom-spinner-overlay d-flex align-items-center justify-content-center"
                            >
                            <i class="el-icon-loading spinner-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- Selectores de Niveles -->
                <div v-if="!loadingHierarchy" class="col-12" v-for="(level, index) in hierarchy" :key="index">
                    <div class="form-group">
                        <label>{{ levelTitles[index] }}</label>
                        <el-select
                            v-model="level.selected"
                            :loading="level.loading"
                            @change="handleSublevelSelection(level.selected, index)"
                        >
                            <el-option
                                v-for="child in level.children"
                                :key="child.id"
                                :label="child.label"
                                :value="child.id"
                                :disabled="index === 4"
                            />
                        </el-select>
                        <!-- Spinner encima del select -->
                        <div v-if="level.loading"
                            class="custom-spinner-overlay d-flex align-items-center justify-content-center"
                            >
                            <i class="el-icon-loading spinner-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- Campo de Código -->
                <div class="col-6">
                    <div class="form-group">
                        <label>Código</label>
                        <el-input v-model="form.code" :maxlength="getMaxCodeLength()" show-word-limit />
                    </div>
                </div>

                <!-- Campo de Nombre dinámico -->
                <div class="col-6">
                    <div class="form-group">
                        <label>Nombre de {{ dynamicNameLabel }}</label>
                        <el-input v-model="form.name" />
                    </div>
                </div>

                <!-- Campo de Nivel -->
                <div class="col-6">
                    <div class="form-group">
                        <label>Nivel</label>
                        <el-input v-model="form.level" type="number" readonly />
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="col-12 pt-4 text-right">
                <el-button @click="close">Cancelar</el-button>
                <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
            </div>
        </form>
    </el-dialog>
</template>
<style scoped>
    .custom-spinner-overlay {
    position: absolute;
    top: 35px; /* Ajusta si tu label es más alto */
    left: 0;
    width: 100%;
    height: 38px;
    background: rgba(255, 255, 255, 0.6);
    pointer-events: none;
    z-index: 2;
    }

    .spinner-icon {
    font-size: 18px;
    color: #409EFF;
    }
</style>
<script>
export default {
    props: ["showDialog", "recordId"],
    data() {
        return {
            loading_submit: false,
            titleDialog: null,
            resource: "accounting/charts",
            form: { code: "", name: "", level: 1, parent_id: null, type: "" },
            parentAccounts: [],
            hierarchy: [],
            parentCode: "",
            errors: {},
            levelTitles: ["Grupo", "Cuenta", "Subcuenta",'Auxiliar','Detalle'], // Títulos dinámicos para los niveles
            loadingHierarchy:false,

        };
    },
    computed: {
        // Etiqueta dinámica para el campo de Nombre
        dynamicNameLabel() {
            switch (this.form.level) {
                case 1:
                    return "Clase";
                case 2:
                    return "Grupo";
                case 3:
                    return "Cuenta";
                case 4:
                    return "Subcuenta";
                case 5:
                    return "Auxiliar";
                case 6:
                    return "Detalle";
                default:
                    return "Detalle";
            }
        }
    },
    methods: {
        async loadData() {
            this.titleDialog = this.recordId ? "Editar Cuenta" : "Nueva Cuenta";
            this.hierarchy = [];

            // Cargar cuentas de nivel 1
            await this.loadParentAccounts();

            if (this.recordId) {
                // Cargar datos del registro a editar
                const { account, parents } = await this.fetchRecord(this.recordId);
                this.setupForm(account);
                if (parents.length > 0) {
                    this.form.parent_id = parents[0].id;
                    await this.handleParentSelection();
                    await this.loadHierarchyForEdit(parents);
                }
                this.form.level = account.level;
                this.form.code = account.code;
            }
        },

        async loadParentAccounts() {
            const response = await this.$http.get(`/${this.resource}/records?column=level&value=1`);
            this.parentAccounts = response.data.data;
        },

        async fetchRecord(id) {
            const response = await this.$http.get(`/${this.resource}/${id}`);
            return response.data.data;
        },

        setupForm(account) {
            this.form = {
                code: account.code,
                name: account.name,
                level: account.level,
                // parent_id: account.parent_id,
                type: account.type
            };
        },

        async loadHierarchyForEdit(parents) {
            this.loadingHierarchy = true;

            for (let i = 1; i < parents.length; i++) {
                const parent = parents[i];

                // Validar si el nivel ya existe en la jerarquía
                if (this.hierarchy[i - 1]) {
                    // Asignar el valor seleccionado en el nivel correspondiente
                    this.hierarchy[i - 1].selected = parent.id;

                    // Si hay un siguiente nivel en parents, cargar sus hijos
                    if (i < parents.length - 1) {
                        this.$set(this.hierarchy[i-1], 'loading', true);
                        const children = await this.fetchChildren(parent.id);
                        this.$set(this.hierarchy[i-1], 'loading', false);

                        if (this.hierarchy[i]) {
                            this.hierarchy[i].children = children;
                        } else {
                            this.hierarchy.push({ selected: null, children,loading: false });
                        }
                    }
                }
            }
            this.loadingHierarchy = false;
        },

        async fetchChildren(parentId) {
            const response = await this.$http.get(`/${this.resource}/children/${parentId}`);
            // Mapear los hijos a la estructura deseada
            return response.data.children.map(child => ({
                id: child.id,
                label: `${child.code} - ${child.name}`,
                code: child.code
            }));
        },

        async handleParentSelection() {
            if (!this.form.parent_id) {
                this.resetHierarchy();
                return;
            }

            const selectedParent = this.parentAccounts.find(acc => acc.id === this.form.parent_id);
            if (selectedParent) {
                this.parentCode = selectedParent.code;
                this.form.code = this.parentCode;
                this.form.type = selectedParent.type;
            }

            const children = await this.fetchChildren(this.form.parent_id);
            this.hierarchy = [{ selected: null, children, loading: false }];
            this.form.level = 2;
        },

        async handleSublevelSelection(parent_id, index) {
            if (!parent_id) {
                this.hierarchy = this.hierarchy.slice(0, index);
                this.form.level = this.hierarchy.length + 1;
                return;
            }

            const selectedChild = this.hierarchy[index].children.find(child => child.id === parent_id);
            if (selectedChild) {
                this.parentCode = selectedChild.code;
                this.form.code = this.parentCode;
            }

            //Mostrar loading
            this.$set(this.hierarchy[index], 'loading', true);

            const children = await this.fetchChildren(parent_id);
            //Ocultar loading
            this.$set(this.hierarchy[index], 'loading', false);

            this.hierarchy = this.hierarchy.slice(0, index + 1);


            if (children.length) {
                // this.hierarchy.push({ selected: null, children });
                if (this.hierarchy.length <= 5) {
                    this.hierarchy.push({ selected: null, children, loading: false });
                }
            } else {
                if(this.hierarchy.length < 6) {
                    this.hierarchy.push({ selected: null, children: [], loading: false });
                }
            }
            this.form.level = this.hierarchy.length + 1;

            this.updateCode();
        },

        updateCode() {
            const lastSelectedLevel = this.hierarchy.findLast(level => level.selected);

            if (lastSelectedLevel) {
                const selectedChild = lastSelectedLevel.children.find(child => child.id === lastSelectedLevel.selected);
                this.form.code = selectedChild ? selectedChild.code : "";
            } else {
                this.form.code = "";
            }
        },

        resetHierarchy() {
            this.hierarchy = [];
            this.form.code = "";
            this.form.level = 1;
        },

        getMaxCodeLength() {
            const levels = [1, 2, 4, 6, 8 , 10];
            return levels[this.form.level - 1] || 1;
        },

        async submit() {
            this.loading_submit = true;

            const lastSelectedParentId = this.hierarchy.reduceRight((acc, level) => acc || level.selected, null);
            if (lastSelectedParentId) {
                this.form.parent_id = lastSelectedParentId;
            }

            const method = this.recordId ? "put" : "post";
            const url = this.recordId ? `/${this.resource}/${this.recordId}` : `/${this.resource}`;

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
            this.resetForm();
            this.$emit("update:showDialog", false);
            this.$eventHub.$emit("reloadData");
        },

        resetForm() {
            this.form = { code: "", name: "", level: 1, parent_id: null, type: "" };
            this.hierarchy = [];
            this.parentCode = "";
        }
    }
};
</script>