<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Paiement extends Model
{
    use HasUuid;
    
    protected $table = 'paiements';

    //public $timestamps = false;

    protected $fillable = [
        'commande_id',
        'montant',
        'moyen',
        'statut',
        'date_paiement',
        'provider_reference',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'datetime',
    ];

    //public $timestamps = true;

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }
}
