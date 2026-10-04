<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Litige extends Model
{
    use HasUuid;

    public $timestamps = true;        // ← la table n'a pas updated_at
    const CREATED_AT = 'date_signalement'; // ← created_at s'appelle date_signalement
    const UPDATED_AT = null;

    protected $fillable = [
        'commande_id',
        'acheteur_id',
        'motif',
        'preuves',
        'statut',
        'date_signalement', 
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function acheteur()
    {
        return $this->belongsTo(User::class, 'acheteur_id');
    }
}