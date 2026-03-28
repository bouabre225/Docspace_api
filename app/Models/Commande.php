<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Commande extends Model
{
    use HasUuid;
    
    protected $fillable = [
        'acheteur_id',
        'vendeur_id',
        'annonce_id',
        'quantite',
        'montant',
        'statut',
        'adresse_livraison',
        'telephone_livraison',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'quantite' => 'integer',
        'created_at' => 'datetime',
    ];

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
        return $this->belongsTo(Annonce::class);
    }

    public function paiement()
    {
        return $this->hasOne(Paiement::class);
    }

    public function litige()
    {
        return $this->hasOne(Litige::class);
    }

    public function avis()
    {
        return $this->hasOne(Avis::class);
    }
}
