<template>
  <div class="card card-config">
    <div class="card-header bg-info bg-info-customer-admin">
      <h3 class="my-0">Configuración del Login</h3>
    </div>

    <div class="card-body">
      <form autocomplete="off" @submit.prevent="submit">
        <div class="form-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group" :class="{'has-danger': errors.login_bg_image}">
                <label class="control-label">Imagen de fondo del login
                  <el-tooltip
                    class="item"
                    effect="dark"
                    content="Se recomienda usar una imagen de 1280x844 píxeles con fondo transparente para un mejor resultado visual."
                    placement="top-start"
                  >
                    <i class="fa fa-info-circle"></i>
                  </el-tooltip>
                </label>
                <el-input v-model="imageFileName" :readonly="true" placeholder="Ninguna imagen subida">
                    <el-upload slot="append"
                               :headers="headers"
                               :data="{'type': 'login_bg_image'}"
                               action="/api/config-login/upload"
                               :show-file-list="false"
                               :on-success="onImageUploadSuccess"
                               :on-error="onImageUploadError"
                               :before-upload="beforeImageUpload">
                        <el-button type="primary" icon="el-icon-upload"></el-button>
                    </el-upload>
                </el-input>

                <div v-if="previewImage" class="mt-2">
                  <img :src="previewImage" alt="Preview" style="max-width: 100%; height: 120px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;" />
                  <div class="mt-1">
                    <el-button size="mini" type="danger" @click="removeImage">Eliminar imagen</el-button>
                  </div>
                </div>
                <small class="form-control-feedback" v-if="errors.login_bg_image" v-text="errors.login_bg_image[0]"></small>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group d-flex flex-column" :class="{'has-danger': errors.login_bg_color}">
                <label class="control-label">Color de fondo del panel de login
                  <el-tooltip 
                    class="item" 
                    effect="dark" 
                    content="Puedes escribir un valor válido (por ejemplo: #ff0000, hsl(0, 100%, 50%)) y el sistema lo convertirá automáticamente a RGB." 
                    placement="top-start"
                  >
                    <i class="fa fa-info-circle"></i>
                  </el-tooltip>
                </label>
                <el-color-picker
                  class="col-md-12 px-0"
                  v-model="form.login_bg_color"
                  :predefine="predefinedColors"
                  show-alpha
                  color-format="hex"
                />
                <small class="form-control-feedback" v-if="errors.login_bg_color" v-text="errors.login_bg_color[0]"></small>
              </div>
            </div>
            
          </div>
        </div>

        <!-- Botón Guardar -->
        <div class="form-actions text-right pt-2">
          <el-button type="primary" native-type="submit" :loading="loading_submit">
            Guardar
          </el-button>
        </div>
      </form>
    </div>
  </div>
</template>
<style>
.el-color-picker__trigger {
  width: 100% !important;
  padding: 0px;
}
.el-button.el-color-dropdown__link-btn{
  margin-left: auto;
}
.el-button.el-color-dropdown__btn.el-button--default {
  margin-left: auto;
  background-color: var(--black-primary);
  color: #fff;
  border: none;
  margin-top: 5px;
}
.el-button.el-color-dropdown__btn.el-button--default:hover {
  background-color: var(--black-primary);
  color: #fff;
  border: none;
  opacity: 0.8;
}
</style>
<script>
export default {
  data() {
    return {
      loading_submit: false,
      resource: 'config-login',
      errors: {},
      form: {
        login_bg_color: '#ffffff',
        login_bg_image: null,
      },
      previewImage: null,
      imageFileName: '',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      predefinedColors: [
        '#ffffff', '#000000', '#f5f5f5', '#1e90ff', '#ff4757',
        '#2ed573', '#ffa502', '#747d8c'
      ]
    }
  },
  created() {
    this.initForm()
    this.$http.get(`/api/config-login/record`).then(({ data }) => {
      const r = (data && data.data) ? data.data : {}
      Object.assign(this.form, {
        login_bg_color: r.login_bg_color ?? '#ffffff',
        login_bg_image: r.login_bg_image ?? null
      })
      
      // Configurar preview de imagen si existe
      if (this.form.login_bg_image) {
        this.imageFileName = this.form.login_bg_image
        this.previewImage = `/storage/uploads/system/${this.form.login_bg_image}`
      }
    })
  },
  methods: {
    initForm() {
      this.errors = {}
      this.form = {
        login_bg_color: '#ffffff',
        login_bg_image: null,
      }
      this.previewImage = null
      this.imageFileName = ''
    },
    beforeImageUpload(file) {
      const isImage = /\.(jpg|jpeg|png|gif|svg)$/i.test(file.name)
      const isLt2M = file.size / 1024 / 1024 < 2

      if (!isImage) {
        this.$message.error('Solo se permiten archivos de imagen!')
        return false
      }
      if (!isLt2M) {
        this.$message.error('El archivo no puede pesar más de 2MB!')
        return false
      }
      return true
    },
    onImageUploadSuccess(response, file) {
      if (response.success) {
        this.form.login_bg_image = response.filename
        this.imageFileName = response.filename
        this.previewImage = `/storage/uploads/system/${response.filename}`
        this.$message.success('Imagen subida correctamente')
      } else {
        this.$message.error(response.message || 'Error al subir la imagen')
      }
    },
    onImageUploadError(error) {
      this.$message.error('Error al subir la imagen')
      console.error(error)
    },
    removeImage() {
      this.form.login_bg_image = null
      this.previewImage = null
      this.imageFileName = ''
      
      // Guardar cambios inmediatamente
      this.submit()
    },
    submit() {
      this.loading_submit = true
      const formData = new FormData()
      formData.append('login_bg_color', this.form.login_bg_color)
      
      // Enviar el nombre del archivo (puede ser null para eliminarlo)
      formData.append('login_bg_image_name', this.form.login_bg_image || '')

      this.$http.post(`/api/config-login`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      .then(response => {
        if (response.data.success) {
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
      .finally(() => {
        this.loading_submit = false
      })
    }
  }
}
</script>
