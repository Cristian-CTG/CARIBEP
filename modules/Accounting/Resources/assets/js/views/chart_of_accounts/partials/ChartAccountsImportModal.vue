<template>
    <el-dialog
        title="Importar cuentas contables"
        :visible="showDialog"
        @close="close"
        width="600px">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="row pb-2">
                <div class="col-md-12">
                    <el-alert
                        title="Ingrese el codigo y el nombre de las cuentas contables que desee registrar en el Formato Excel."
                        type="info"
                        show-icon
                        :closable="false"
                        class="mb-2"
                    />
                </div>
            </div>
            <div class="row pb-3">
                <div class="col-md-12">
                    <a
                        href="/formats/Import_accounts_format.xlsx"
                        class="btn btn-outline-secondary btn-sm mt-2"
                        style="margin-left: 8px;"
                        target="_new"
                    >
                        <i class="fa fa-download"></i> Descargar formato Excel
                    </a>
                </div>
            </div>
            <div class="row pb-4">
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
            uploadUrl: '/accounting/charts/import-excel' // Nueva ruta
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
                this.$message.success(response.message);
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