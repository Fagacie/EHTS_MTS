<?php

namespace App\Services\Auth;

use App\Models\User;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationService
{
    public function __construct(
        protected Google2FA $google2fa = new Google2FA,
    ) {}

    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function qrCodeDataUri(User $user, string $secret): string
    {
        $otpAuthUrl = $this->google2fa->getQRCodeUrl(
            config('etms.brand_name', 'Ecohydrotech Solution'),
            $user->email,
            $secret,
        );

        $options = new QROptions([
            'outputBase64' => true,
            'scale' => 6,
            'addQuietzone' => true,
        ]);

        return (new QRCode($options))->render($otpAuthUrl);
    }

    public function verifyCode(string $secret, string $code): bool
    {
        $normalized = preg_replace('/\s+/', '', trim($code)) ?? '';

        if ($normalized === '' || ! ctype_digit($normalized) || strlen($normalized) !== 6) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($secret, $normalized, 1);
    }

    /**
     * @return list<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(4).'-'.Str::random(4));
        }

        return $codes;
    }

    public function verifyAndConsumeRecoveryCode(User $user, string $code): bool
    {
        $normalized = strtoupper(trim($code));
        $codes = $user->two_factor_recovery_codes ?? [];

        if (! is_array($codes) || $codes === []) {
            return false;
        }

        $remaining = [];
        $matched = false;

        foreach ($codes as $storedCode) {
            if (! $matched && hash_equals(strtoupper((string) $storedCode), $normalized)) {
                $matched = true;

                continue;
            }
            $remaining[] = $storedCode;
        }

        if (! $matched) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => $remaining,
        ])->save();

        return true;
    }
}
