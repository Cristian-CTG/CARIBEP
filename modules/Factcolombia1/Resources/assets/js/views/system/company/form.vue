<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @close="close" @open="create" :close-on-click-modal="false">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <el-collapse v-model="activePanels">
                    <el-collapse-item name="company">
                        <template slot="title">
                            <div class="icon-collapse">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-building"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M3 21l18 0"></path><path d="M9 8l1 0"></path><path d="M9 12l1 0"></path><path d="M9 16l1 0"></path><path d="M14 8l1 0"></path><path d="M14 12l1 0"></path><path d="M14 16l1 0"></path><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16"></path></svg>
                            </div>
                            <strong class="ml-2">Datos de la empresa</strong>
                        </template>
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <!-- header shown by collapse -->
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': errors.identification_number}">
                                    <label class="control-label">Número de identificación</label>
                                    <el-input  v-model="form.identification_number" :maxlength="15" :disabled="form.is_update">
                                    </el-input>
                                    <small class="form-control-feedback" v-if="errors.identification_number" v-text="errors.identification_number[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': errors.dv}">
                                    <label class="control-label">Dv</label>
                                    <el-input  v-model="form.dv" :disabled="form.is_update"></el-input>
                                    <small class="form-control-feedback" v-if="errors.dv" v-text="errors.dv[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': errors.name}">
                                    <label class="control-label">Nombre de la Empresa</label>
                                    <el-input  v-model="form.name" :disabled="form.is_update"></el-input>
                                    <small class="form-control-feedback" v-if="errors.name" v-text="errors.name[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': (errors.subdomain || errors.uuid)}">
                                    <label class="control-label">Nombre de Subdominio</label>
                                    <el-input class="subdomain-input" v-model="form.subdomain" :disabled="form.is_update">
                                        <template slot="append">{{ url_base }}</template>
                                    </el-input>
                                    <small class="form-control-feedback" v-if="errors.subdomain" v-text="errors.subdomain[0]"></small>
                                    <small class="form-control-feedback" v-if="errors.uuid" v-text="errors.uuid[0]"></small>
                                </div>
                            </div>
                        </div>

                        <div class="row" v-if="form.is_update">
                            <div class="col-md-12 mb-2">
                                <label class="control-label">
                                    API Token
                                    <el-tooltip class="item" effect="dark" content="Código de acceso que permite a las aplicaciones o usuarios ejecutar funciones de API" placement="top-start">
                                        <i class="fa fa-info-circle"></i>
                                    </el-tooltip>
                                </label>
                                <el-input
                                    :value="maskedApiToken"
                                    :disabled="true"
                                    class="token-input"
                                >
                                    <template #suffix>
                                        <span
                                            class="copy-btn"
                                            @click="copyToClipboard"
                                            title="Copiar token"
                                        >
                                            <svg  xmlns="http://www.w3.org/2000/svg"  width="18"  height="18"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-copy"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg>
                                        </span>
                                    </template>
                                </el-input>
                            </div>
                        </div>
                    </el-collapse-item>

                    <el-collapse-item name="user">
                        <template slot="title">
                            <div class="icon-collapse">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path></svg>
                            </div>
                            <strong class="ml-2">Datos usuario</strong>
                        </template>
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <!-- header shown by collapse -->
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': errors.email}">
                                    <label class="control-label">Correo de Acceso</label>
                                    <el-input  v-model="form.email" :disabled="form.is_update"></el-input>
                                    <small class="form-control-feedback" v-if="errors.email" v-text="errors.email[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': (errors.password)}">
                                    <label class="control-label">Contraseña</label>
                                    <el-input type="password"  v-model="form.password"></el-input>
                                    <small class="form-control-feedback" v-if="errors.password" v-text="errors.password[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': (errors.password_confirmation)}">
                                    <label class="control-label">Confirmar contraseña</label>
                                    <el-input type="password"  v-model="form.password_confirmation"></el-input>
                                    <small class="form-control-feedback" v-if="errors.password_confirmation" v-text="errors.password_confirmation[0]"></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div  class="form-group" :class="{'has-danger': errors.type_liability_id}">
                                    <label class="control-label">Tipo de responsabilidad</label>
                                    <el-select filterable  v-model="form.type_liability_id">
                                        <el-option v-for="option in type_liabilities" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.type_liability_id" v-text="errors.type_liability_id[0]"></small>
                                </div>
                            </div>
                        </div>
                    </el-collapse-item>

                    <el-collapse-item name="general">
                        <template slot="title">
                            <div class="icon-collapse">
                                <svg  xmlns="http://www.w3.org/2000/svg"  width="20"  height="20"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-list-details"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 5h8" /><path d="M13 9h5" /><path d="M13 15h8" /><path d="M13 19h5" /><path d="M3 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M3 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /></svg>
                            </div>
                            <strong class="ml-2">Datos generales</strong>
                        </template>
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <!-- header shown by collapse -->
                            </div>

                            <div class="col-md-3">
                                <div class="form-group" :class="{'has-danger': (errors.plan_id)}">
                                    <label class="control-label">Plan</label>
                                        <el-select v-model="form.plan_id" placeholder="Seleccione un plan" @change="setPlanLimits">
                                            <el-option
                                                v-for="plan in plans"
                                                :key="plan.id"
                                                :label="plan.name"
                                                :value="plan.id">
                                            </el-option>
                                        </el-select>
                                    <small class="form-control-feedback" v-if="errors.plan_id" v-text="errors.plan_id[0]"></small>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group" :class="{'has-danger': (errors.economic_activity_code)}">
                                    <label class="control-label">Actividad económica</label>
                                    <el-input  v-model="form.economic_activity_code"></el-input>
                                    <small class="form-control-feedback" v-if="errors.economic_activity_code" v-text="errors.economic_activity_code[0]"></small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group" :class="{'has-danger': (errors.ica_rate)}">
                                    <label class="control-label">Tasa ICA</label>
                                    <el-input  v-model="form.ica_rate"></el-input>
                                    <small class="form-control-feedback" v-if="errors.ica_rate" v-text="errors.ica_rate[0]"></small>
                                </div>
                            </div>


                            <div class="col-md-6">
                                <div  class="form-group" :class="{'has-danger': errors.type_document_identification_id}">
                                    <label class="control-label">Seleccionar Tipo Documento</label>
                                    <el-select filterable  v-model="form.type_document_identification_id">
                                        <el-option v-for="option in type_document_identifications" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.type_document_identification_id" v-text="errors.type_document_identification_id[0]"></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div  class="form-group" :class="{'has-danger': errors.department_id}">
                                    <label class="control-label">Seleccionar Departamento</label>
                                    <el-select filterable  v-model="form.department_id" @change="cascade">
                                        <el-option v-for="option in departments" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.department_id" v-text="errors.department_id[0]"></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div  class="form-group" :class="{'has-danger': errors.municipality_id}">
                                    <label class="control-label">Seleccionar Municipio</label>
                                    <el-select filterable  v-model="form.municipality_id">
                                        <el-option v-for="option in municipalities" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.municipality_id" v-text="errors.municipality_id[0]"></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div  class="form-group" :class="{'has-danger': errors.type_organization_id}">
                                    <label class="control-label">Seleccionar Tipo Organizacion</label>
                                    <el-select filterable  v-model="form.type_organization_id">
                                        <el-option v-for="option in type_organizations" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.type_organization_id" v-text="errors.type_organization_id[0]"></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div  class="form-group" :class="{'has-danger': errors.type_regime_id}">
                                    <label class="control-label">Seleccionar Regimen</label>
                                    <el-select filterable  v-model="form.type_regime_id">
                                        <el-option v-for="option in type_regimes" :key="option.id" :value="option.id" :label="option.name"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.type_regime_id" v-text="errors.type_regime_id[0]"></small>
                                </div>
                            </div>


                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': (errors.merchant_registration)}">
                                    <label class="control-label">Registro mercantil</label>
                                    <el-input  v-model="form.merchant_registration"></el-input>
                                    <small class="form-control-feedback" v-if="errors.merchant_registration" v-text="errors.merchant_registration[0]"></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': (errors.address)}">
                                    <label class="control-label">Dirección</label>
                                    <el-input  v-model="form.address"></el-input>
                                    <small class="form-control-feedback" v-if="errors.address" v-text="errors.address[0]"></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': (errors.phone)}">
                                    <label class="control-label">Teléfono</label>
                                    <el-input  v-model="form.phone"></el-input>
                                    <small class="form-control-feedback" v-if="errors.phone" v-text="errors.phone[0]"></small>
                                </div>
                            </div>

                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12" >
                                <div class="form-group">
                                    <label class="control-label">Módulos</label>
                                    <div class="row">
                                        <div class="col-4" v-for="(module,ind) in form.modules" :key="ind">
                                            <el-checkbox v-model="module.checked">{{ module.description }}</el-checkbox>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </el-collapse-item>
                </el-collapse>
            </div>
            <div class="form-actions text-right pt-2">
                <el-button @click.prevent="close()">Cancelar</el-button>
                <el-button type="primary" native-type="submit" :loading="loading_submit" dusk="submit">
                    <template v-if="loading_submit">
                        {{button_text}}
                    </template>
                    <template v-else>
                        Guardar
                    </template>
                </el-button>
            </div>
        </form>
    </el-dialog>
</template>
<style>
.el-dialog__body{
    padding: 10px 20px;
}
.el-collapse-item__header,
.el-collapse,
.el-collapse-item__wrap{
    border: none !important;
}
.el-collapse-item {
    border: 1px solid var(--black-highlight);
    padding: 0 6px;
    border-radius: 12px;
    margin-bottom: 10px;
}
.el-collapse-item__wrap,
.el-collapse-item__header {
    padding: 0 4px;
    background-color: transparent;
}
.token-input {
    position: relative;
    width: 100%;
}
.token-input .copy-btn {
    background: none;
    border: none;
    padding: 4px;
    margin-right: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s ease;
    margin-top: 3px;
    color: var(--black-accend);
}
.token-input .copy-btn:hover {
    color: var(--black-dark);
    background-color: transparent;
    border: none;
}
.token-input .bi-clipboard {
    pointer-events: none;
}
</style>

<script>

    export default {
        props: ['showDialog', 'recordId'],
        data() {
            return {
                headers: headers_token,
                loading_submit: false,
                loading_search: false,
                titleDialog: null,
                button_text:null,
                resource: 'co-companies',
                // keep collapse panels open by default
                activePanels: ['company', 'user', 'general'],
                error: {},
                errors: {},
                form: {},
                url_base: null,
                departments:[],
                municipalities:[],
                all_municipalities:[],
                type_document_identifications: [],
                type_organizations: [],
                modules: [],
                type_regimes: [],
                toggle: false,
                type_liabilities: [],
                plans: [],
            }
        },
        async created() {
            await this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.modules = response.data.modules
                    this.departments = response.data.departments
                    this.all_municipalities = response.data.municipalities
                    this.type_document_identifications = response.data.type_document_identifications
                    this.type_organizations = response.data.type_organizations
                    this.type_regimes = response.data.type_regimes
                    this.url_base = response.data.url_base
                    this.type_liabilities = response.data.type_liabilities
                })
            await this.$http.get('/plans/tables')
                .then(response => {
                    this.plans = response.data.plans
                })
            await this.initForm()
        },
        computed: {
            maskedApiToken() {
                if (!this.form.api_token) return '';
                const token = this.form.api_token;
                // Si el token es muy corto, lo mostramos todo
                if (token.length <= 10) return token;
                return `${token.slice(0, 6)}••••••••${token.slice(-4)}`;
            }
        },
        methods: {
            copyToClipboard() {
                if (!this.form.api_token) {
                    this.$message.warning('No hay token para copiar');
                    return;
                }
            
                const el = document.createElement('textarea');
                el.value = this.form.api_token;
                el.setAttribute('readonly', '');
                el.style.position = 'absolute';
                el.style.left = '-9999px';
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
            
                this.$message.success('Token copiado al portapapeles');
            },

            cascade() {
                this.form.municipality_id = null
                this.municipalities = this.all_municipalities.filter(
                    x => x.department_id == this.form.department_id
                )
            },

            filterMunicipalities() {
                this.municipalities = this.all_municipalities.filter(
                    x => x.department_id == this.form.department_id
                )
            },
            initForm() {

                this.errors = {}
                this.form = {
                    id: null,
                    is_update: false,
                    identification_number: null,
                    name: null,
                    subdomain: null,
                    email: null,
                    password: null,
                    password_confirmation: null,
                    api_token: null,
                    department_id: null,
                    municipality_id: null,
                    type_organization_id: null,
                    type_regime_id: null,
                    merchant_registration: null,
                    address: null,
                    phone: null,
                    economic_activity_code: null,
                    ica_rate: null,
                    limit_documents: null,
                    limit_users: 1,
                    type_document_identification_id: null,
                    dv: 1,
                    language_id: 79,
                    country_id: 46,
                    type_liability_id: 14,
                    id_service: null,
                    modules: []
                }

                this.modules.forEach(module => {
                    this.form.modules.push({
                        id: module.id,
                        description: module.description,
                        checked: true
                    })
                })
            },
            create() {
                this.titleDialog = (this.recordId)? 'Editar compañia':'Crear Nueva compañia'
                if (this.recordId) {
                    this.$http.get(`/${this.resource}/record/${this.recordId}`)
                        .then(response => {
                                this.form = response.data.data
                                this.form.is_update = true
                                this.filterMunicipalities()
                            })
                }
            },
            hasModules(){

                let modules_checked = 0
                this.form.modules.forEach(module =>{
                    if(module.checked){
                        modules_checked++
                    }
                })

                return (modules_checked > 0) ? true:false

            },
            async submit() {

                // console.log(this.form)

                if(!this.form.is_update){
                    let has_modules = await this.hasModules()
                    if(!has_modules)
                        return this.$message.error('Debe seleccionar al menos un módulo')
                }


                this.button_text = (this.form.is_update) ? 'Actualizando compañia...':'Creando base de datos...'
                this.loading_submit = true
                await this.$http.post(`${this.resource}${(this.form.is_update ? '/update' : '')}`, this.form)
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success(response.data.message)
                            this.$eventHub.$emit('reloadData')
                            this.close()
                        } else {
                            this.$message.error(response.data.message)
                        }
                    })
                    .catch(error => {
                        if (error.response.status === 422) {
                            this.errors = error.response.data
                        }else if(error.response.status === 500){
                            this.$message.error(error.response.data.message);
                        }
                         else {
                            console.log(error.response)
                        }
                    })
                    .then(() => {
                        this.loading_submit = false
                    })
            },
            close() {
                this.$emit('update:showDialog', false)
                this.initForm()
            },
            errorUpload(r)
            {
                console.log(r)
            },
            successUpload(response)
            {
                if (response.success) {
                    this.form.certificate = response.data.filename
                   // this.form.image_url = response.data.temp_image
                    this.form.temp_path = response.data.temp_path
                } else {
                    this.$message.error(response.message)
                }
            },
            setPlanLimits() {
                const selectedPlan = this.plans.find(plan => plan.id === this.form.plan_id);
                if (selectedPlan) {
                    this.form.limit_documents = selectedPlan.limit_documents;
                    this.form.limit_users = selectedPlan.limit_users;
                } else {
                    this.form.limit_documents = null;
                    this.form.limit_users = null;
                }
            },
        }
    }
</script>
