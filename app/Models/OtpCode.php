<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = ['identifier', 'email', 'type', 'code', 'purpose', 'expires_at', 'used_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    /** Generate a fresh 6-digit OTP, invalidating any existing ones for this context. */
    public static function generate(
        string $identifier,
        string $email,
        string $type,
        string $purpose = 'reset'
    ): self {
        // Invalidate previous codes
        static::where('identifier', $identifier)
              ->where('type', $type)
              ->where('purpose', $purpose)
              ->whereNull('used_at')
              ->update(['used_at' => now()]);

        return static::create([
            'identifier' => $identifier,
            'email'      => $email,
            'type'       => $type,
            'code'       => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'purpose'    => $purpose,
            'expires_at' => now()->addMinutes(3),
        ]);
    }

    /** Find the latest valid (unused, non-expired) OTP for this context. */
    public static function findValid(
        string $identifier,
        string $type,
        string $code,
        string $purpose = 'reset'
    ): ?self {
        return static::where('identifier', $identifier)
                     ->where('type', $type)
                     ->where('purpose', $purpose)
                     ->where('code', $code)
                     ->whereNull('used_at')
                     ->where('expires_at', '>', now())
                     ->latest()
                     ->first();
    }

    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}
