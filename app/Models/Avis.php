<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Avis extends Model
{
    use HasFactory, HasUuid;

    protected $keyType = 'int';
    public $incrementing = true;

    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'commande_id', 'vendeur_id', 'note_vendeur', 'note_conformite', 'commentaire'
    ];

    public function commande()
    {
        return $this->belongsTo(Commandes::class, 'commande_id');
    }

    public function vendeur()
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }
}
