<template>
    <div id="styleSwitcher" class="style-switcher">

        <!-- Close button: pone style.right = -260px y quita clases open/active -->
        <div class="d-flex justify-content-between align-items-center px-3 mt-3">
            <h4 class="m-0">Configuraciones visuales</h4>
            <button type="button" class="style-switcher-close btn-close" @click="closeSwitcher" aria-label="Cerrar">
                <svg  xmlns="http://www.w3.org/2000/svg"  width="22"  height="22"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-x"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
            </button>
        </div>            

        <!-- <a id="styleSwitcherOpen" class="style-switcher-open" href="#"><i class="fas fa-cogs"></i></a> -->

        <form class="style-switcher-wrap" autocomplete="off">

            <div v-if="visual == null">
                <h5 class="">No posee ajustes actualmente</h5>
                <a href="" class="text-warning" v-if="typeUser != 'integrator'">cargar ajustes por defecto</a>
                <br>
            </div>
            <div v-if="typeUser != 'integrator'">

                <!-- <div class="switch-container">
                    <h5>Tema del menú lateral</h5>
                    <el-switch
                        v-model="visuals.sidebars"
                        active-text="Oscuro"
                        inactive-text="Claro"
                        active-value="dark"
                        inactive-value="light"
                        active-color="#383f48"
                        inactive-color="#ccc"
                        @change="submit">
                    </el-switch>
                </div> -->

                <!-- <div class="pt-3 switch-container">
                    <h5>Menú lateral contraído</h5>
                    <div :class="{'has-danger': errors.compact_sidebar}">
                        <el-switch
                            v-model="form.compact_sidebar"
                            active-text="Si"
                            inactive-text="No"
                            @change="submitForm">
                        </el-switch>
                        <br>
                        <small class="form-control-feedback" v-if="errors.compact_sidebar" v-text="errors.compact_sidebar[0]"></small>
                    </div>
                </div> -->

                <div class="pt-3">
                    <h5>Visualización de productos en POS</h5>
                    <div :class="{'has-danger': errors.colums_grid_item}">
                        <el-select
                          v-model="form.colums_grid_item"
                          placeholder="Seleccionar diseño"
                          @change="submitForm"
                          style="width: 100%"
                        >
                          <el-option label="Predeterminado" :value="3" />
                          <el-option label="Cómodo"         :value="4" />
                          <el-option label="Compacto"       :value="5" />
                          <el-option label="Apilado"        :value="6" />
                        </el-select>
                        <small class="form-control-feedback" v-if="errors.colums_grid_item" v-text="errors.colums_grid_item[0]"></small>
                    </div>
                </div>

<!--                <div class="pt-3">
                    <h5>Ver icono de soporte</h5>
                    <div :class="{'has-danger': errors.enable_whatsapp}">
                        <el-switch
                            v-model="form.enable_whatsapp"
                            active-text="Si"
                            inactive-text="No"
                            @change="submitForm">
                        </el-switch>
                        <small class="form-control-feedback" v-if="errors.enable_whatsapp" v-text="errors.enable_whatsapp[0]"></small>
                        <br>
                        <small class="form-control-feedback">Se mostrará si el administrador ha añadido número de soporte</small>
                    </div>
                </div>      -->

            </div>
        </form>

    </div>
</template>

<script>
    export default {
        props:['visual','typeUser'],

        data() {
            return {
                loading_submit: false,
                resource: 'configurations',
                errors: {},
                form: {},
                visuals: {},
            }
        },
        async created() {
            await this.initForm()
            await this.getRecords()
        },
        methods: {
            closeSwitcher() {
              const el = document.getElementById('styleSwitcher')
              if (!el) return

              if (!el.classList.contains('open') && !el.classList.contains('active')) {
                el.style.right = '-280px'
                el.classList.remove('open', 'active', 'closing')
                return
              }
          
              el.classList.add('closing')
          
              void el.offsetWidth
          
              el.style.right = '-280px'
          
              const onEnd = () => {
                el.classList.remove('closing')
                el.classList.remove('open', 'active')
                el.removeEventListener('transitionend', onEnd)
              }
              el.addEventListener('transitionend', onEnd)
            },

            initForm() {
                this.errors = {}
                this.form = {
                    id: 1,
                    compact_sidebar: true,
                    colums_grid_item: "4",
                    enable_whatsapp: true,
                    phone_whatsapp: ''
                }
            },
            getRecords() {
                this.$http.get(`/${this.resource}/record`) .then(response => {
                    if (response.data !== ''){
                        this.visuals = response.data.data.visual;
                        this.form = response.data.data;
                        this.form.colums_grid_item = Number(this.form.colums_grid_item)
                    }
                });
            },
            submit() {
                this.$http.post(`/${this.resource}/visual_settings`, this.visuals).then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                    }
                    else {
                        this.$message.error(response.data.message);
                    }
                }).catch(error => {
                    if (error.response.status === 422) {
                        this.errors = error.response.data.errors;
                    }
                    else {
                        console.log(error);
                    }
                }).then(() => {
                    location.reload();
                });
            },
            submitForm() {
                this.loading_submit = true;
                this.$http.post(`/${this.resource}`, this.form).then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        location.reload()
                    }
                    else {
                        this.$message.error(response.data.message);
                    }
                }).catch(error => {
                    if (error.response.status === 422) {
                        this.errors = error.response.data.errors;
                    }
                    else {
                        console.log(error);
                    }
                }).then(() => {
                    this.loading_submit = false;
                });
            },
        }
    }
</script>