<template>
    <el-dialog
        title="Importación masiva de asientos contables"
        :visible="showDialog"
        @close="close"
        width="600px">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="row pb-2">
                <!-- <div class="col-md-12">
                    <el-alert
                        title="Descargue el formato de ejemplo, diligencie los datos y cárguelo para importar asientos contables."
                        type="info"
                        show-icon
                        :closable="false"
                        class="mb-2"
                    />
                </div> -->
            </div>
            <div class="row pb-2">
                <div class="col-6 col-sm-6 mt-2">
                    <el-dropdown :hide-on-click="false">
                        <span class="el-dropdown-link text-muted" style="cursor: default;">
                            Tipos de tercero <i class="el-icon-arrow-down el-icon--right"></i>
                        </span>
                        <el-dropdown-menu slot="dropdown">
                            <el-dropdown-item style="user-select: text;">Clientes</el-dropdown-item>
                            <el-dropdown-item style="user-select: text;">Proveedores</el-dropdown-item>
                            <el-dropdown-item style="user-select: text;">Empleados</el-dropdown-item>
                            <el-dropdown-item style="user-select: text;">Vendedores</el-dropdown-item>
                        </el-dropdown-menu>
                    </el-dropdown>
                </div>
                <div class="col-6 col-sm-6 mt-2">
                    <el-dropdown :hide-on-click="false">
                        <span class="el-dropdown-link text-muted" style="cursor: default;">
                            Métodos de pago
                            <i class="el-icon-arrow-down el-icon--right"></i>
                            <el-tooltip content="Puede escribir el código o el nombre del método de pago en el Excel. Si usa código, este debe estar en la lista." placement="top">
                                <i class="fa fa-info-circle ml-1" ></i>
                            </el-tooltip>
                        </span>
                        <el-dropdown-menu slot="dropdown" class="dropdown-scroll">
                            <el-dropdown-item
                                v-for="method in paymentMethods"
                                :key="method.id"
                                style="user-select: text;"
                            >
                                <b>{{ method.code }}</b>: {{ method.name }}
                            </el-dropdown-item>
                        </el-dropdown-menu>
                    </el-dropdown>
                </div>
            </div>
            <div class="row pb-3">
                <div class="col-md-12">
                    <a
                        href="/accounting/journal/entries/import-format"
                        class="btn btn-outline-secondary btn-sm mt-2"
                        target="_blank"
                    >
                        <i class="fa fa-download"></i> Descargar formato Excel de ejemplo
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
            uploadUrl: '/accounting/journal/entries/import-excel',
            paymentMethods: []
        }
    },
    watch: {
        showDialog(val) {
            if (val) this.loadPaymentMethods();
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
        async loadPaymentMethods() {
            const res = await this.$http.get('/accounting/payment-methods');
            this.paymentMethods = res.data;
        },
    }
}
</script>
<style scoped>
.dropdown-scroll {
    max-height: 220px;
    overflow-y: auto;
}
</style>