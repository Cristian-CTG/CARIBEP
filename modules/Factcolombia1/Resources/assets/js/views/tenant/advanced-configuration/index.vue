<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="#"><i class="fas fa-cogs"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Configuración</span></li>
                <li><span class="text-muted">Avanzado</span></li>
            </ol>
        </div>
        <div class="card card-dashboard">
            <div class="card-body card-advanced-config">
                <template>
                    <form autocomplete="off">
                        <el-tabs v-model="activeName">

                            <el-tab-pane class="mb-3"
                                         name="general">
                                <span slot="label">General</span>
                                <div class="row">

                                    <div class="col-md-4 mt-4" :class="{'has-danger': errors.uvt}">
                                        <div class="form-group">
                                            <label class="control-label">Valor UVT
                                                <el-tooltip class="item" effect="dark" content="Límite UVT = 5 x Valor UVT" placement="top-start">
                                                    <i class="fa fa-info-circle"></i>
                                                </el-tooltip>
                                            </label>
                                            <el-input-number v-model="form.uvt" :min="0" controls-position="right"
                                                             @change="submit"></el-input-number>

                                            <small class="form-control-feedback" v-if="errors.uvt" v-text="errors.uvt[0]"></small>
                                        </div>
                                    </div>
                                </div>
                            </el-tab-pane>

                            <el-tab-pane class="mb-3" name="sale">
                                <span slot="label">Ventas</span>
                                <div class="row">
                                    <div class="col-md-4 mt-4" :class="{'has-danger': errors.item_tax_included}">
                                        <label class="control-label">
                                            Incluir impuesto al precio de registro
                                            <el-tooltip class="item" effect="dark" content="Aplicado en Factura Electrónica" placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <div class="form-group" :class="{'has-danger': errors.item_tax_included}">
                                            <el-switch v-model="form.item_tax_included" active-text="Si" inactive-text="No" @change="submit"></el-switch>
                                            <small class="form-control-feedback" v-if="errors.item_tax_included" v-text="errors.item_tax_included[0]"></small>
                                        </div>
                                    </div>
                                    <!-- Nuevo switch para validar stock mínimo -->
                                    <div class="col-md-4 mt-4" :class="{'has-danger': errors.validate_min_stock}">
                                        <label class="control-label">
                                            Validar stock mínimo
                                            <el-tooltip class="item" effect="dark" content="Si está activo, no se podrá vender por debajo del stock mínimo" placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <div class="form-group">
                                            <el-switch v-model="form.validate_min_stock" active-text="Si" inactive-text="No" @change="submit"></el-switch>
                                            <small class="form-control-feedback" v-if="errors.validate_min_stock" v-text="errors.validate_min_stock[0]"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mt-4" :class="{'has-danger': errors.validate_discount_code}">
                                        <label class="control-label">
                                            Validar código para descuento
                                            <el-tooltip class="item" effect="dark" content="Si está activo, se requerirá un código para aplicar descuentos" placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <div class="form-group">
                                            <el-switch v-model="form.validate_discount_code" active-text="Si" inactive-text="No" @change="submit"></el-switch>
                                            <small class="form-control-feedback" v-if="errors.validate_discount_code" v-text="errors.validate_discount_code[0]"></small>
                                        </div>
                                        <!-- Solo para admin: mostrar y generar el código si el switch está activado -->
                                        <div v-if="form.validate_discount_code && user && user.type === 'admin'" class="mt-3">
                                            <label class="control-label">Código de descuento (solo admin)</label>
                                            <div class="form-group" style="display: flex; gap: 8px;">
                                                <el-input v-model="form.discount_code" readonly style="max-width: 150px;"></el-input>
                                                <!-- Botón Actualizar -->
                                                <el-tooltip effect="dark" content="Actualizar: consulta el código actual en la base de datos" placement="top">
                                                    <el-button type="info" size="mini" icon="el-icon-refresh" @click="refreshDiscountCode"></el-button>
                                                </el-tooltip>
                                                <!-- Botón Copiar -->
                                                <el-tooltip effect="dark" content="Copiar: copia el código al portapapeles" placement="top">
                                                    <el-button type="success" size="mini" icon="el-icon-document-copy" @click="copyDiscountCode"></el-button>
                                                </el-tooltip>
                                                <!-- Botón Generar -->
                                                <el-tooltip effect="dark" content="Generar: crea un nuevo código de descuento" placement="top">
                                                    <el-button type="primary" size="mini" @click="generateDiscountCode">Generar</el-button>
                                                </el-tooltip>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mt-4" :class="{'has-danger': errors.custom_remission_footer_enabled}">
                                        <label class="control-label">
                                            Footer personalizado en remisiones
                                            <el-tooltip class="item" effect="dark" content="Activa un mensaje personalizado en el pie de página de las remisiones" placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <div class="form-group">
                                            <el-switch v-model="form.custom_remission_footer_enabled" active-text="Si" inactive-text="No" @change="submit"></el-switch>
                                            <small class="form-control-feedback" v-if="errors.custom_remission_footer_enabled" v-text="errors.custom_remission_footer_enabled[0]"></small>
                                        </div>
                                        <div class="form-group mt-2" v-if="form.custom_remission_footer_enabled">
                                            <label class="control-label">Mensaje de footer</label>
                                            <el-input type="textarea" v-model="form.custom_remission_footer_message" maxlength="250" show-word-limit @change="submit"></el-input>
                                            <small class="form-control-feedback" v-if="errors.custom_remission_footer_message" v-text="errors.custom_remission_footer_message[0]"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mt-4">
                                        <label class="control-label">
                                            Activar vistas de vendedores
                                            <el-tooltip class="item" effect="dark" content="Muestra el módulo de vendedores en el menú" placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <div class="form-group">
                                            <el-switch v-model="form.enable_seller_views" active-text="Sí" inactive-text="No" @change="submit"></el-switch>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mt-4" :class="{'has-danger': errors.default_format_print}">
                                        <label class="control-label">
                                            Formato de impresión por defecto
                                            <el-tooltip class="item" effect="dark" content="Este formato se seleccionará automáticamente al crear un comprobante, pero puedes cambiarlo en cada documento." placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <div class="form-group">
                                            <el-select v-model="form.default_format_print" placeholder="Seleccione formato" @change="submit">
                                                <el-option label="Media Carta" :value="1"></el-option>
                                                <el-option label="Carta" :value="2"></el-option>
                                                <el-option label="Tirilla" :value="3"></el-option>
                                            </el-select>
                                            <small class="form-control-feedback" v-if="errors.default_format_print" v-text="errors.default_format_print[0]"></small>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <hr style="border-top: 3px solid #e0e0e0;">
                                        <h4 class="mt-3 mb-2">Campos personalizados para plantilla</h4>
                                    </div>
                                    <div class="col-md-4 mt-3">
                                        <label class="control-label">
                                            Nota de encabezado
                                            <el-tooltip class="item" effect="dark" content="Este campo se mostrara antes de los datos del cliente" placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <el-input
                                            type="textarea"
                                            v-model="form.head_note"
                                            maxlength="120"
                                            show-word-limit
                                            placeholder="Ingrese una nota de encabezado"
                                            @change="submit"
                                        ></el-input>
                                    </div>
                                    <!-- <div class="col-md-4 mt-3">
                                        <label class="control-label">
                                            Notas en plantilla
                                            <el-tooltip class="item" effect="dark" content="Este campo se mostrará antes del footer y después de la tabla de productos" placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <el-input
                                            type="textarea"
                                            v-model="form.notes"
                                            maxlength="120"
                                            show-word-limit
                                            placeholder="Ingrese notas adicionales"
                                            @change="submit"
                                        ></el-input>
                                    </div> -->
                                    <div class="col-md-4 mt-3">
                                        <label class="control-label">
                                            Nota de pie de página
                                            <el-tooltip class="item" effect="dark" content="Este campo se mostrará en el pie de página de la factura" placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <el-input
                                            type="textarea"
                                            v-model="form.foot_note"
                                            maxlength="120"
                                            show-word-limit
                                            placeholder="Ingrese una nota de pie de página"
                                            @change="submit"
                                        ></el-input>
                                    </div>
                                </div>
                            </el-tab-pane>

                            <el-tab-pane class="mb-3"
                                         name="payroll">
                                <span slot="label">Nómina</span>
                                <div class="row">

                                    <div class="col-md-4 mt-4">
                                        <label class="control-label">Salario mínimo</label>
                                        <div class="form-group">
                                            <el-input-number v-model="form.minimum_salary" :min="0" controls-position="right"
                                                             @change="submit"></el-input-number>

                                        </div>
                                    </div>
                                    <div class="col-md-2"></div>
                                    <div class="col-md-4 mt-4">
                                        <label class="control-label">Subsidio de transporte</label>
                                        <div class="form-group">
                                            <el-input-number v-model="form.transportation_allowance" :min="0" controls-position="right"
                                                             @change="submit"></el-input-number>
                                        </div>
                                    </div>

                                </div>
                            </el-tab-pane>


                            <el-tab-pane class="mb-3" name="radian">
                                <span slot="label">Recepción documentos (RADIAN)</span>
                                <div class="row">
                                    <div class="col-md-12">
                                        <h4>Configuración de correo electrónico</h4>
                                    </div>

                                    <div class="col-md-4 mt-2">
                                        <label class="control-label">Dirección del host</label>
                                        <div class="form-group">
                                            <el-input v-model="form.radian_imap_host"></el-input>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mt-2">
                                        <label class="control-label">Puerto del host</label>
                                        <div class="form-group">
                                            <el-input v-model="form.radian_imap_port"></el-input>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mt-2">
                                        <label class="control-label">Encriptación</label>
                                        <div class="form-group">
                                            <el-input v-model="form.radian_imap_encryption"></el-input>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mt-2">
                                        <label class="control-label">Correo electrónico</label>
                                        <div class="form-group">
                                            <el-input v-model="form.radian_imap_user"></el-input>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mt-2">
                                        <label class="control-label">Contraseña
                                            <el-tooltip class="item" effect="dark" content="La contraseña se obtiene como clave de aplicación en Gmail u otros servicios de correo." placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <div class="form-group">
                                            <el-input v-model="form.radian_imap_password" show-password></el-input>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mt-2">
                                        <div class="form-actions text-right">
                                            <el-button class="submit" type="primary" @click.prevent="clickSaveEmailRadian" :loading="loading_submit">Guardar</el-button>
                                        </div>
                                    </div>

                                </div>
                                <div class="row mt-4">
                                    <div class="col-md-4 mt-2">
                                        <label class="control-label">
                                            Mostrar documentos por
                                            <el-tooltip class="item" effect="dark" content="Elija si desea mostrar documentos por crédito o al contado" placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <div class="form-group">
                                            <el-switch
                                                v-model="form.radian_show_credit_only"
                                                :active-value="true"
                                                :inactive-value="false"
                                                active-text="Crédito y Contado"
                                                inactive-text="Solo Crédito"
                                                @change="submit"
                                            ></el-switch>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mt-2">
                                        <label class="control-label">
                                            Procesar correos RADIAN automáticamente
                                            <el-tooltip class="item" effect="dark" content="Si está activo, los correos se procesarán automáticamente cada día." placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <div class="form-group">
                                            <el-switch
                                                v-model="form.auto_process_radian_emails"
                                                :active-value="true"
                                                :inactive-value="false"
                                                active-text="Sí"
                                                inactive-text="No"
                                                @change="submit"
                                            ></el-switch>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mt-2">
                                        <label class="control-label">
                                            Procesar eventos RADIAN automáticamente
                                            <el-tooltip class="item" effect="dark" content="Si está activo, los eventos pendientes se procesarán automáticamente cada día." placement="top-start">
                                                <i class="fa fa-info-circle"></i>
                                            </el-tooltip>
                                        </label>
                                        <div class="form-group">
                                            <el-switch
                                                v-model="form.auto_process_radian_events"
                                                :active-value="true"
                                                :inactive-value="false"
                                                active-text="Sí"
                                                inactive-text="No"
                                                @change="submit"
                                            ></el-switch>
                                        </div>
                                    </div>
                                </div>
                            </el-tab-pane>

                            <el-tab-pane class="mb-3" name="dataDelete">
                                <span slot="label">Eliminar datos de prueba</span>
                                <div class="row">
                                    <div class="col-md-12">
                                        <el-alert
                                            title="Importante"
                                            type="warning"
                                            description="Esta acción borrará todos los registros en base de datos, asegurece de respaldar."
                                            show-icon>
                                        </el-alert>
                                    </div>

                                    <div class="col-md-12 mt-2">
                                        <div class="form-actions text-right">
                                            <el-button class="submit" type="primary" @click="showDialogDataDelete">Eliminar</el-button>
                                        </div>
                                    </div>

                                </div>
                            </el-tab-pane>

                            <el-tab-pane class="mb-3" name="qztray">
                                <span slot="label">Impresión</span>
                                <certificates-qztray></certificates-qztray>
                            </el-tab-pane>

                            <el-tab-pane class="mb-3" name="whatsapp">
                                <span slot="label">WhatsApp Api</span>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card card-dashboard border">
                                            <whatsapp-api-config />
                                        </div>
                                    </div>
                                </div>
                            </el-tab-pane>

                        </el-tabs>
                    </form>
                </template>
            </div>
        </div>
        <el-dialog
            title="Confirmar eliminación de datos"
            :visible.sync="openDialogDataDelete"
            width="30%"
            :close-on-click-modal="false">
            <span>
                <el-alert
                    title="Importante"
                    type="warning"
                    description="Los registros se eliminarán en base de datos"
                    show-icon>
                </el-alert>
            </span>
            <br>
            <span v-if="resolutions.length">
                <div>Seleccione resolución</div>
                <el-select v-model="resolution_id" placeholder="Seleccione">
                    <el-option
                    v-for="(row, index) in resolutions"
                    :key="index"
                    :label="row.name"
                    :value="row.id">
                    <span style="float: left">{{ row.name }}</span>
                    <span style="float: right; color: #8492a6; font-size: 13px">{{ row.prefix }}</span>
                    </el-option>
                </el-select>
            </span>
            <span slot="footer" class="dialog-footer">
                <el-button @click="openDialogDataDelete = false">Cancel</el-button>
                <el-button type="primary" :loading="loading_delete" @click="clickDataDelete" :disabled="!resolution_id">Confirm</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>
import CertificatesQztray from './certificates_qztray.vue'
import WhatsappApiConfig from '../configuration/WhatsappApiConfig.vue'

export default {
    components: {CertificatesQztray, WhatsappApiConfig},
    props: {
        user: Object
    },
    data() {
        return {
            loading_submit: false,
            resource: 'co-advanced-configuration',
            errors: {},
            form: {},
            loading_submit: false,
            activeName: 'general',
            openDialogDataDelete: false,
            resolutions: [],
            resolution_id: null,
            loading_delete: false,
        }
    },
    created() {
        this.getRecord()
    },
    methods: {
        async getRecord() {

            await this.$http.get(`/${this.resource}/record`).then(response => {
                this.form = response.data.data
            })
        },
        initForm() {
            this.errors = {}
            this.form = {
                minimum_salary: 0,
                transportation_allowance: 0,
                radian_imap_encryption: null,
                radian_imap_host: null,
                radian_imap_port: null,
                radian_imap_password: null,
                radian_imap_user: null,
                auto_process_radian_emails: false,
                auto_process_radian_events: false,
                uvt: 0,
                item_tax_included: false,
                validate_min_stock: false,
                validate_discount_code: false,
                discount_code: '',
                custom_remission_footer_enabled: false,
                custom_remission_footer_message: '',
                radian_show_credit_only: true,
                default_format_print: 1,
                foot_note: '',
                head_note: '',
                // notes: '',
            }
        },
        clickSaveEmailRadian()
        {
            if(!this.form.radian_imap_encryption || !this.form.radian_imap_host || !this.form.radian_imap_port || !this.form.radian_imap_password || !this.form.radian_imap_user)
            {
                return this.$message.error('Todos los campos son obligatorios')
            }

            this.submit()
        },
        submit() {

            this.loading_submit = true

            this.$http.post(`/${this.resource}`, this.form).then(response => {
                let data = response.data
                if (data.success) {
                    this.$message.success(data.message)
                    this.getRecord()
                } else {
                    this.$message.error(data.message)
                }

            }).catch(error => {
                if (error.response.status === 422) {
                    this.errors = error.response.data
                } else {
                    console.log(error)
                }
            }).then(() => {
                this.loading_submit = false
            })
        },
        showDialogDataDelete() {
            this.getResolutions();
            this.openDialogDataDelete = true;
        },
        getResolutions() {
            this.$http.get(`/client/configuration/co_type_documents`).then(response => {
                if (response.data.data.length) {
                    this.resolutions = response.data.data
                } else {
                    this.$message.error(data.message)
                }

            }).catch(error => {
                console.log(error)
            })
        },
        clickDataDelete() {
            this.loading_delete = true;
            let formDelete = {
                id: this.resolution_id
            }
            this.$http.post(`/${this.resource}/delete-documents`, formDelete).then(response => {
                let data = response.data
                if (data.success) {
                    this.$message.success(data.message)
                } else {
                    this.$message.error(data.message)
                }

            }).catch(error => {
                if (error.response.status === 422) {
                    this.errors = error.response.data
                } else {
                    console.log(error)
                }
            }).finally(() => {
                this.loading_delete = false;
                this.openDialogDataDelete = false;
            });
        },
        async generateDiscountCode() {
            try {
                const response = await this.$http.post('/co-advanced-configuration/generate-discount-code');
                if (response.data.success) {
                    this.form.discount_code = response.data.discount_code;
                    this.$message.success(response.data.message);
                } else {
                    this.$message.error(response.data.message);
                }
            } catch (error) {
                this.$message.error('Error al generar el código');
            }
        },
        async refreshDiscountCode() {
            try {
                const response = await this.$http.get('/co-advanced-configuration/record');
                this.form.discount_code = response.data.data.discount_code;
                this.$message.success('Código actualizado');
            } catch (error) {
                this.$message.error('No se pudo actualizar el código');
            }
        },
        copyDiscountCode() {
            if (!this.form.discount_code) return;
            // Intentar con clipboard API
            if (navigator.clipboard) {
                navigator.clipboard.writeText(this.form.discount_code)
                    .then(() => {
                        this.$message.success('Código copiado');
                    })
                    .catch(() => {
                        this.$message.error('No se pudo copiar el código');
                    });
            } else {
                // Fallback para navegadores antiguos
                const input = document.createElement('input');
                input.value = this.form.discount_code;
                document.body.appendChild(input);
                input.select();
                try {
                    document.execCommand('copy');
                    this.$message.success('Código copiado');
                } catch (e) {
                    this.$message.error('No se pudo copiar el código');
                }
                document.body.removeChild(input);
            }
        },
    }
}
</script>
