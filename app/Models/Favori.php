<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Favori extends Model
{
    use HasUuid;

    protected $table = 'favoris';

    protected $fillable = ['user_id', 'annonce_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function annonce()
    {
        return $this->belongsTo(Annonce::class);
    }
}
