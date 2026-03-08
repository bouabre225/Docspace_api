<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class KycAudit extends Model
{
    use HasUuid;
    
    protected $table = 'kyc_audits';

    protected $fillable = [
        'user_id',
        'admin_id',
        'document_id',
        'ancien_statut',
        'nouveau_statut',
        'commentaire',
        'ip_address',
    ];
}
