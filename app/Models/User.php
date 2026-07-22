<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string $telephone
 * @property string|null $pin
 * @property int|null $entreprise_id
 * @property RoleEnum|null $role
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'telephone', 'pin', 'entreprise_id', 'role'])]
#[Hidden(['password', 'remember_token', 'pin'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
            'role' => RoleEnum::class,
        ];
    }

    /**
     * @return BelongsTo<Entreprise, $this>
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * @return BelongsToMany<PointDeVente, $this>
     */
    public function pointsDeVente(): BelongsToMany
    {
        return $this->belongsToMany(PointDeVente::class, 'point_de_vente_user');
    }

    /**
     * @return HasMany<AppareilMemorise, $this>
     */
    public function appareilsMemorises(): HasMany
    {
        return $this->hasMany(AppareilMemorise::class);
    }

    /**
     * @return HasMany<JournalConnexion, $this>
     */
    public function journalConnexions(): HasMany
    {
        return $this->hasMany(JournalConnexion::class);
    }

    /**
     * Livraisons for which this user is the assigned responsable (livreur).
     *
     * @return HasMany<Livraison, $this>
     */
    public function livraisons(): HasMany
    {
        return $this->hasMany(Livraison::class, 'responsable_user_id');
    }
}
