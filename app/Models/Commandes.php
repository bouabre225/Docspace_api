<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Commandes extends Model
{
    use HasUuid;
    protected $table = 'commandes';
    
    protected $fillable = [
        'acheteur_id',
        'vendeur_id',
        'annonce_id',
        'quantite',
        'montant',
        'statut',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'quantite' => 'integer',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    public function acheteur()
    {
        return $this->belongsTo(User::class, 'acheteur_id');
    }

    public function vendeur()
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }

    public function annonce()
    {
        return $this->belongsTo(Annonce::class, 'annonce_id');
    }

    public function paiement()
    {
        return $this->hasOne(Paiement::class, 'commande_id');
    }
}
