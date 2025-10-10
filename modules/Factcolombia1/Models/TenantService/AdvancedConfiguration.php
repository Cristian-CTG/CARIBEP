<?php

namespace Modules\Factcolombia1\Models\TenantService;

use Illuminate\Database\Eloquent\Model;
use Hyn\Tenancy\Traits\UsesTenantConnection;

class AdvancedConfiguration extends Model
{

    use  UsesTenantConnection;

    protected $table = 'co_advanced_configuration';

    public const QUANTITY_UVT_LIMIT = 5;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'minimum_salary',
        'transportation_allowance',

        'radian_imap_encryption',
        'radian_imap_host',
        'radian_imap_port',
        'radian_imap_password',
        'radian_imap_user',
        'auto_process_radian_emails',
        'auto_process_radian_events',
        'uvt',
        'item_tax_included',
        'validate_min_stock',
        'digital_certificate_qztray',
        'private_certificate_qztray',
        'enable_qz_tray',
        'validate_discount_code',
        'discount_code',
        'custom_remission_footer_enabled',
        'custom_remission_footer_message',
        'enable_seller_views',
        'radian_show_credit_only',
        'default_format_print',
        'foot_note',
        'head_note',
        'notes',
        'default_tax_id',
    ];

    protected $casts = [
        'uvt' => 'float',
        'item_tax_included' => 'bool',
        'enable_qz_tray' => 'bool',
        'validate_min_stock' => 'bool',
        'validate_discount_code' => 'bool',
        'custom_remission_footer_enabled' => 'bool',
        'enable_seller_views' => 'bool',
        'radian_show_credit_only' => 'bool',
        'default_format_print' => 'integer',
        'auto_process_radian_emails' => 'bool',
        'auto_process_radian_events' => 'bool',
    ];


    /**
     * Use in resource and collection
     *
     * @return array
     */
    public function getRowResource(){

        return [
            'id' => $this->id,
            'minimum_salary' => $this->minimum_salary,
            'transportation_allowance' => $this->transportation_allowance,

            'radian_imap_encryption' => $this->radian_imap_encryption,
            'radian_imap_host' => $this->radian_imap_host,
            'radian_imap_port' => $this->radian_imap_port,
            'radian_imap_password' => $this->radian_imap_password,
            'radian_imap_user' => $this->radian_imap_user,
            'auto_process_radian_emails' => $this->auto_process_radian_emails,
            'auto_process_radian_events' => $this->auto_process_radian_events,
            'uvt' => $this->uvt,
            'item_tax_included' => $this->item_tax_included,
            'validate_min_stock' => $this->validate_min_stock,
            'validate_discount_code' => $this->validate_discount_code,
            'discount_code' => $this->discount_code,
            'custom_remission_footer_enabled' => $this->custom_remission_footer_enabled,
            'custom_remission_footer_message' => $this->custom_remission_footer_message,
            'enable_seller_views' => $this->enable_seller_views,
            'radian_show_credit_only' => $this->radian_show_credit_only,
            'default_format_print' => $this->default_format_print,
            'foot_note' => $this->foot_note,
            'head_note' => $this->head_note,
            'notes' => $this->notes,
            'default_tax_id' => $this->default_tax_id,
        ];

    }


    public function scopeSelectImapColumns($query)
    {
        return $query->select([
            'radian_imap_encryption',
            'radian_imap_host',
            'radian_imap_port',
            'radian_imap_password',
            'radian_imap_user',
        ]);
    }


    /**
     *
     * Configuracion para forms
     *
     * @param  array $columns
     * @return AdvancedConfiguration
     */
    public static function getPublicConfiguration($columns = [])
    {
        $query = self::query();

        if(!empty($columns)) $query->select($columns);

        return $query->firstOrFail();
    }


    /**
     *
     * Limite de la uvt para validar registro de documento en pos
     *
     * @return float
     */
    public function getLimitUvt()
    {
        return $this->uvt * self::QUANTITY_UVT_LIMIT;
    }

    /**
     * Obtener unicamente los noombre del archivos de Qz Tray
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSelectCertificateQzTray($query)
    {
        return $query->select('enable_qz_tray', 'digital_certificate_qztray', 'private_certificate_qztray');
    }
}
