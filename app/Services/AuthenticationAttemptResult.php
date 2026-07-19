<?php

namespace App\Services;

use App\Models\AppareilMemorise;
use App\Models\User;

final readonly class AuthenticationAttemptResult
{
    public function __construct(
        public bool $succes,
        public ?User $user,
        public ?AppareilMemorise $appareil,
    ) {}
}
