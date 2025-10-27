<?php

namespace Modules\Accounting\Models;

use App\Models\Tenant\ModelTenant;
use Hyn\Tenancy\Traits\UsesTenantConnection;

class ThirdParty extends ModelTenant
{
    use UsesTenantConnection;

    protected $table = 'third_parties';

    protected $fillable = [
        'name',
        'type',
        'document',
        'document_type',   // tipo de documento
        'address',
        'phone',
        'email',
        'origin_id',      // id de origen (por ejemplo, cliente o proveedor)
    ];

    // Relación con los detalles de asientos contables
    public function journalEntryDetails()
    {
        return $this->hasMany(JournalEntryDetail::class, 'third_party_id');
    }
    public function getTypeName()
    {
        switch ($this->type) {
            case 'customers':
                return 'Clientes';
            case 'suppliers':
                return 'Proveedores';
            case 'employee':
                return 'Empleados';
            case 'seller':
                return 'Vendedores';
            default:
                return ucfirst($this->type);
        }
    }
}