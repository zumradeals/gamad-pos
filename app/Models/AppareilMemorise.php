<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'device_id', 'token', 'memorized_at', 'revoked_at'])]
#[Hidden(['token'])]
class AppareilMemorise extends Model
{
    use HasFactory;

    protected $table = 'appareils_memorises';

    protected function casts(): array
    {
        return [
            'token' => 'hashed',
            'memorized_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estRevoque(): bool
    {
        return $this->revoked_at !== null;
    }
}
