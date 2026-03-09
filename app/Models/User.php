<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Concerns\HasUuid;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'google_id',
        'avatar',
        'role',
        'type_compte',
        'nom',
        'email',
        'mot_de_passe',
        'telephone',
        'pays',
        'devise',
        'adresse',
        'two_factor_secret',
        'verifie_kyc',
        'badge_verifie',
        'note_moyenne',
        'statut',
        'two_factor_enable_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'mot_de_passe',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verifie_kyc' => 'boolean',
            'badge_verifie' => 'boolean',
            'note_moyenne' => 'decimal:1',
            'two_factor_enable_at' => 'datetime',
            'created_at' => 'datetime',
            'update_at' => 'datetime'
        ];
    }

    /**
     * Mutator hash auto le password
     */
    public function setMotDePasseAttribute($value)
    {
        /*if (is_string($value) && str_starts_with('$2y$')) {
            $this->attributes['mot_de_passe'] = $value;
            return;
        }*/

        $this->attributes['mot_de_passe'] = bcrypt($value);
    }

    /**
     * Statut
     */
    public function isActive() 
    {
        return $this->statut === 'actif';
    }

    /**
     * 
     */
    public function has2faEnabled() 
    {
        return !empty($this->two_factor_secret);
    }
    public function kycDocuments()
    {
        return $this->hasMany(KycDocument::class);
    }

    public function annonces()
    {
        return $this->hasMany(Annonce::class, 'vendeur_id');
    }

    public function commandesAcheteur()
    {
        return $this->hasMany(Commande::class, 'acheteur_id');
    }

    public function commandesVendeur()
    {
        return $this->hasMany(Commande::class, 'vendeur_id');
    }

    public function messagesEnvoyes()
    {
        return $this->hasMany(Message::class, 'expediteur_id');
    }

    public function messagesRecus()
    {
        return $this->hasMany(Message::class, 'recepteur_id');
    }

    
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
