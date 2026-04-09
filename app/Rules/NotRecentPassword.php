<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class NotRecentPassword implements ValidationRule
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->user->wasPasswordUsedRecently($value)) {
            $fail('You cannot use your last 5 passwords.');
        }
    }
}
