<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Str;

class GmailAddress
{
    public static function normalize(mixed $email): string
    {
        return Str::lower(trim((string) $email));
    }

    public static function isAllowed(mixed $email): bool
    {
        $normalizedEmail = self::normalize($email);

        return filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) !== false
            && Str::endsWith($normalizedEmail, '@gmail.com');
    }

    public static function message(): string
    {
        return 'Only real Gmail accounts ending in @gmail.com are allowed for alumni portal accounts.';
    }

    public static function validationRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! self::isAllowed($value)) {
                $fail(self::message());
            }
        };
    }
}
