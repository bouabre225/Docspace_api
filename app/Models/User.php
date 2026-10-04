<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Concerns\HasUuid;

class User extends Authenticatable implements CanResetPasswordContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens, HasUuid, CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nom',
        'email',
        'mot_de_passe',
        'telephone',
        'fcm_token',
        'pays',
        'devise',
        'adresse',
        'avatar',
        'google_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'mot_de_passe',
        'two_factor_secret',
        'fcm_token',
        'google_id',
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
            'updated_at' => 'datetime'
        ];
    }

    /**
     * Mutator hash auto le password
     */
    public function setMotDePasseAttribute($value)
    {
        $this->attributes['mot_de_passe'] = \Illuminate\Support\Facades\Hash::make($value);
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
    public function getAuthPassword(): string
    {
        return $this->mot_de_passe;
    }

    public function getAuthPasswordName(): string
    {
        return 'mot_de_passe';
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

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
