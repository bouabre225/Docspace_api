<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\HasUuid;

class Notification extends Model
{
    use HasUuid;
    
    protected $fillable = [
        'user_id',
        'type',           // message | commande | litige | systeme
        'canal',          // push | email | sms
        'reference_type', // commande | litige | message | annonce | kyc
        'reference_id',
        'contenu',
        'metadata',       // JSON : données additionnelles
        'lu',
        'sent_at',
    ];

    protected $casts = [
        'lu'       => 'boolean',
        'metadata' => 'array',
        'sent_at'  => 'datetime',    
    ];

    //Relations    

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    //Scopes 

    public function scopeNonLues($query)
    {
        return $query->where('lu', false);
    }

    public function scopeParCanal($query, string $canal)
    {
        return $query->where('canal', $canal);
    }

    public function scopeParType($query, string $type)
    {
        return $query->where('type', $type);
    }

    //Helpers 
    public function marquerLue(): void
    {
        $this->update(['lu' => true]);
    }
}