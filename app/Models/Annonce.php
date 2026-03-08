<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Annonce extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'int';
    public $incrementing = true;

    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'vendeur_id', 'titre', 'description', 'categorie',
        'etat', 'prix_vendeur', 'quantite', 'pays_expedition', 'statut'
    ];

    protected $casts = [
        'prix_vendeur' => 'decimal:2',
        'frais_protection' => 'decimal:2',
        'prix_total' => 'decimal:2',
        'quantite' => 'integer'
    ];

    public function vendeur()
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }

    public function user()
    {
        return $this->vendeur();
    }

    public function commandes()
    {
        return $this->hasMany(Commandes::class, 'annonce_id');
    }

    public function avis()
    {
        // Pas de hasManyThrough car types UUID/integer incompatibles
        // On passe par les commandes de l'annonce
        $commandeIds = $this->commandes()->pluck('id');
        return Avis::whereIn('commande_id', $commandeIds);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function images()
    {
        return $this->hasMany(AnnonceImage::class);
    }

    public function noteMoyenne()
    {
        $commandeIds = $this->commandes()->pluck('id');
        $avis = Avis::whereIn('commande_id', $commandeIds)->get();

        if ($avis->count() === 0) return 0;

        return round(
            ($avis->sum('note_vendeur') + $avis->sum('note_conformite')) / 2 / $avis->count(),
            1
        );
    }
}
