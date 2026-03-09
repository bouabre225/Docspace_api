<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Notification extends Model
{
    use HasUuid;
    
    protected $fillable = [
        'user_id',
        'type',
        'canal',
        'contenu',
        'lu'
    ];

    protected $casts = [
        'lu' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
