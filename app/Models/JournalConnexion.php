<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'telephone', 'appareil_memorise_id', 'resultat', 'ip'])]
class JournalConnexion extends Model
{
    use HasFactory;

    public const RESULTAT_SUCCES = 'succes';

    public const RESULTAT_ECHEC = 'echec';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<AppareilMemorise, $this>
     */
    public function appareilMemorise(): BelongsTo
    {
        return $this->belongsTo(AppareilMemorise::class);
    }
}
