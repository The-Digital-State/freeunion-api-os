<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OTPHP\TOTP;
use Throwable;

/**
 * Class Mfa
 *
 * @property int $id
 * @property int $user_id
 * @property Collection<int, string> $enabled
 * @property Collection<int, string> $otp_passwords
 * @property string $totp_secret
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User $user
 */
class Mfa extends Model
{
    protected $fillable = ['user_id'];

    protected $casts = [
        'enabled' => 'collection',
        'otp_passwords' => 'encrypted:collection',
        'totp_secret' => 'encrypted',
    ];

    /**
     * @return BelongsTo<User, Mfa>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addType(string $method): void
    {
        $methods = $this->enabled->values();
        $this->enabled = $methods->add($method)->sort()->unique()->values();
        $this->save();
    }

    public function delType(string $method): void
    {
        $methods = $this->enabled->values();
        $this->enabled = $methods->diff([$method])->values();
        $this->save();
    }

    public function generateOtpPasswords(): void
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $count = 10;
        $length = 10;
        /** @var Collection<int, string> */
        $passwords = new Collection();

        for ($index = 0; $index < $count; $index++) {
            $passwords->add(self::randomString($length, $alphabet));
        }

        $this->otp_passwords = $passwords;
        $this->save();
    }

    public function generateTotpSecret(): void
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $length = 32;

        $this->totp_secret = self::randomString($length, $alphabet);
        $this->save();
    }

    public function verify(string $method, string $password): bool
    {
        return match ($method) {
            'otp' => $this->verifyOtp($password),
            'totp' => $this->verifyTotp($password),
            default => false,
        };
    }

    public function verifyOtp(string $password): bool
    {
        if ($this->otp_passwords->contains($password)) {
            $passwords = $this->otp_passwords->values();
            $this->otp_passwords = $passwords->diff([$password])->values();
            $this->save();

            return true;
        }

        return false;
    }

    public function verifyTotp(string $password): bool
    {
        if ($this->totp_secret !== '') {
            return TOTP::create($this->totp_secret)->verify($password);
        }

        return false;
    }

    public static function randomString(
        int $length = 16,
        string $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
    ): string {
        mt_srand();
        $alphabetSize = mb_strlen($alphabet);
        $shuffled = str_shuffle($alphabet);
        $string = '';

        while (mb_strlen($string) < $length) {
            try {
                $start = random_int(0, $alphabetSize);
            } catch (Throwable) {
                $start = mb_strlen($string) % $alphabetSize;
            }

            $string .= mb_substr($shuffled, $start, 1);
        }

        return $string;
    }
}
