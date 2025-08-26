<template>
    <div class="card card-config">
        <div class="card-header bg-info bg-info-customer-admin">
            <h3 class="my-0">Configuración de Soporte</h3>
        </div>
        <div class="card-body">
                <form autocomplete="off" @submit.prevent="submit">
                    <div class="form-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group" :class="{'has-danger': errors.phone}">
                                    <label class="control-label">Teléfono</label>
                                    <el-input v-model="form.phone"></el-input>
                                    <div class="sub-title text-muted">
                                      <small>
                                        Ingresa el número con el código de país, sin espacios ni símbolos.  
                                        Este número se mostrará en el panel de soporte de los clientes.  
                                        Ejemplo: 573155555555
                                      </small>
                                    </div>
                                    <small class="form-control-feedback" v-if="errors.phone" v-text="errors.phone[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group" :class="{'has-danger': errors.whatsapp_number}">
                                  <label class="control-label">Número de WhatsApp</label>
                                  <el-input v-model="form.whatsapp_number"></el-input>
                                  <div class="sub-title text-muted">
                                    <small>Ingresa el número con el código de país, sin espacios ni símbolos.  
                                    Este será el número de WhatsApp que verán los clientes en el panel de soporte.  
                                    Ejemplo: 573155555555
                                    </small>
                                  </div>
                                  <small class="form-control-feedback" v-if="errors.whatsapp_number" v-text="errors.whatsapp_number[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': errors.address_contact}">
                                    <label class="control-label">Correo de Contacto</label>
                                    <el-input v-model="form.address_contact"></el-input>
                                    <small class="form-control-feedback" v-if="errors.address_contact" v-text="errors.address_contact[0]"></small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group" :class="{'has-danger': errors.introduction}">
                                    <label class="control-label">Presentación</label>
                                    <ckeditor
                                      type="classic"
                                      v-model="form.introduction"
                                      :config="editorConfig">
                                    </ckeditor>
                                    <small class="form-control-feedback" v-if="errors.introduction" v-text="errors.introduction[0]"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions text-right pt-2">
                        <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
                    </div>
                </form>
        </div>
    </div>
</template>

<script>
import Vue from 'vue'
import CKEditor from 'vue-ckeditor5'
import ClassicEditor from '@ckeditor/ckeditor5-build-classic'

const options = {
    editors: { classic: ClassicEditor },
    name: 'ckeditor' // el tag que usas en el template
}
Vue.use(CKEditor.plugin, options)

export default {
    data() {
        return {
            loading_submit: false,
            resource: 'users',
            errors: {},
            form: {},
            Editor: ClassicEditor,
            editorConfig: {
              toolbar: [
                'heading','|','bold','italic','link','bulletedList','numberedList','|','blockQuote','undo','redo'
              ],
              placeholder: 'Escribe aquí tu presentación...'
            }
        }
    },
    created() {
      this.initForm()
      this.$http.get(`/${this.resource}/record`).then(({ data }) => {
        const r = (data && data.data) ? data.data : {}
        Object.assign(this.form, {
          id: r.id ?? null,
          name: r.name ?? null,
          email: r.email ?? null,
          api_token: r.api_token ?? null,
          phone: r.phone ?? null,
          whatsapp_number: r.whatsapp_number ?? null,
          address_contact: r.address_contact ?? null,
          introduction: r.introduction ?? ''   // string, no null
        })
      })
    },
    methods: {
        initForm() {
          this.errors = {}
          this.form = {
            id: null, name: null, email: null, api_token: null,
            password: null, password_confirmation: null,
            phone: null, whatsapp_number: null, address_contact: null,
            introduction: '' // string para CKEditor
          }
        },
        submit() {
            this.loading_submit = true
            // Post the full form so backend validation that depends on other fields passes
            this.$http.post(`/${this.resource}`, this.form)
                .then(response => {
                    if (response.data.success) {
                        this.form.password = null
                        this.form.password_confirmation = null
                        this.$message.success(response.data.message)
                    } else {
                        this.$message.error(response.data.message)
                    }
                })
                .catch(error => {
                    if (error.response && error.response.status === 422) {
                        this.errors = error.response.data
                    } else {
                        console.log(error)
                    }
                })
                .then(() => {
                    this.loading_submit = false
                })
        },
    }
}

</script>