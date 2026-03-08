<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class KycDocument extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'type_document',
        'fichier',
        'statut',
        'date_validation'
    ];

    protected $casts = [
        'date_validation' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
