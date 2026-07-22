<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dette_fournisseur_id', 'montant'])]
class VersementFournisseur extends Model
{
    use HasFactory;

    protected $table = 'versements_fournisseur';

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<DetteFournisseur, $this>
     */
    public function detteFournisseur(): BelongsTo
    {
        return $this->belongsTo(DetteFournisseur::class);
    }
}
