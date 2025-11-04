<template>
    <el-dialog
        title="Importación masiva de asientos contables"
        :visible="showDialog"
        @close="close"
        width="600px">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="row pb-4">
                <!-- <div class="col-12 mt-2">
                    <a href="/formats/asientos_importacion.xlsx" target="_blank" class="text-muted">
                        Click para descargar el formato de importación
                    </a>
                </div> -->
                <div class="col-md-12 mt-3">
                    <div class="form-group" :class="{'has-danger': errors.file}">
                        <el-upload
                            ref="upload"
                            :headers="headers"
                            :action="uploadUrl"
                            :show-file-list="true"
                            :auto-upload="false"
                            :multiple="false"
                            :on-error="errorUpload"
                            :limit="1"
                            :on-success="successUpload"
                            accept=".xlsx, .xls">
                            <el-button slot="trigger" type="primary">Seleccione un archivo (xls, xlsx) para importar...</el-button>
                        </el-upload>
                    </div>
                    <small class="form-control-feedback" v-if="errors.file" v-text="errors.file[0]"></small>
                </div>
            </div>
            <span slot="footer">
                <div class="d-inline-block mr-2">
                    <el-button @click="close">Cerrar</el-button>
                </div>
                <div class="d-inline-block">
                    <el-button type="primary" native-type="submit" :loading="loading_submit">Procesar</el-button>
                </div>
            </span>
        </form>
    </el-dialog>
</template>

<script>
export default {
    props: {
        showDialog: { type: Boolean, required: true }
    },
    data() {
        return {
            loading_submit: false,
            errors: {},
            headers: headers_token,
            uploadUrl: '/accounting/journal/entries/import-excel'
        }
    },
    methods: {
        close() {
            this.$emit('update:showDialog', false)
        },
        errorUpload(err) {
            try {
                const errorMessage = typeof err === "string" ? err : err.message;
                const jsonStart = errorMessage.indexOf('{');
                if (jsonStart !== -1) {
                    const jsonString = errorMessage.substring(jsonStart);
                    const errorJson = JSON.parse(jsonString);
                    this.$message.error(`Error al subir el archivo: ${errorJson.message}`);
                } else {
                    this.$message.error("Error inesperado: " + errorMessage);
                }
            } catch (e) {
                this.$message.error("Error inesperado: " + e.message);
            }
        },
        successUpload(response, file, fileList) {
            if (response.success) {
                if (response.errors && response.errors.length > 0) {
                    this.$message({
                        message: `Importación completada con observaciones. Algunos asientos no se registraron. Revisa el detalle de errores (Logs).`,
                        type: 'warning'
                    });
                } else {
                    this.$message.success(response.message);
                }
                this.$eventHub.$emit('reloadData');
                this.$refs.upload.clearFiles();
                this.close();
            } else {
                this.$message({message: response.message, type: 'error'});
            }
        },
        async submit() {
            this.loading_submit = true
            await this.$refs.upload.submit()
            this.loading_submit = false
        },
    }
}
</script>