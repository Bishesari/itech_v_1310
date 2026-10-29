<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SmsGateway;
use App\Exceptions\Verification\SmsDeliveryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;

final class OtpService
{
    private const int OTP_TTL_MINUTES = 2;

    private const int SEND_LIMIT_PER_MOBILE = 1;

    private const int SEND_LIMIT_PER_IP = 7;

    private const int SEND_DECAY_SECONDS = 120;

    private const int VERIFY_LIMIT = 5;

    private const int VERIFY_DECAY_SECONDS = 300;

    public function __construct(
        private readonly SmsGateway $smsGateway,
    ) {}

    /**
     * درخواست OTP برای یک شماره و یک کاربرد مشخص.
     *
     * status:
     * - active: کد قبلی هنوز معتبر است.
     * - sent: کد جدید ارسال شده است.
     * - mobile_limited: محدودیت شماره موبایل.
     * - ip_limited: محدودیت IP.
     */
    public function send(
        string $purpose,
        string $mobile,
        string $ip,
    ): array {
        $this->validatePurpose($purpose);

        $otpKey = $this->otpKey($purpose, $mobile);
        $expiresAtKey = $this->expiresAtKey($purpose, $mobile);

        $expiresAt = Cache::get($expiresAtKey);

        // تا زمان اعتبار OTP فعلی، پیامک جدید ارسال نشود.
        if (
            Cache::has($otpKey)
            && is_numeric($expiresAt)
            && (int) $expiresAt > now()->timestamp
        ) {
            return [
                'status' => 'active',
                'expires_at' => (int) $expiresAt,
            ];
        }

        // پاک‌کردن OTP منقضی‌شده.
        $this->forgetOtp($purpose, $mobile);

        // کلیدهای محدودیت ارسال مطابق رفتار فعلی ثبت‌نام هستند.
        $mobileKey = 'send-otp:mobile:'.$mobile;
        $ipKey = 'send-otp:ip:'.$ip;

        if (RateLimiter::tooManyAttempts(
            $mobileKey,
            self::SEND_LIMIT_PER_MOBILE,
        )) {
            return [
                'status' => 'mobile_limited',
                'seconds' => RateLimiter::availableIn($mobileKey),
            ];
        }

        if (RateLimiter::tooManyAttempts(
            $ipKey,
            self::SEND_LIMIT_PER_IP,
        )) {
            return [
                'status' => 'ip_limited',
                'seconds' => RateLimiter::availableIn($ipKey),
            ];
        }

        // شمارش درخواست حتی در صورت شکست ارسال پیامک.
        RateLimiter::hit(
            $mobileKey,
            self::SEND_DECAY_SECONDS,
        );

        RateLimiter::hit(
            $ipKey,
            self::SEND_DECAY_SECONDS,
        );

        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

        Cache::put(
            $otpKey,
            Hash::make($code),
            $expiresAt,
        );

        Cache::put(
            $expiresAtKey,
            $expiresAt->timestamp,
            $expiresAt,
        );

        try {
            $this->smsGateway->sendOtp($mobile, $code);
        } catch (SmsDeliveryException $e) {
            $this->forgetOtp($purpose, $mobile);

            throw $e;
        }

        return [
            'status' => 'sent',
            'expires_at' => $expiresAt->timestamp,
        ];
    }

    /**
     * بررسی OTP و مصرف آن پس از تأیید موفق.
     *
     * status:
     * - verified
     * - invalid
     * - expired
     * - rate_limited
     */
    public function verify(
        string $purpose,
        string $mobile,
        string $code,
    ): array {
        $this->validatePurpose($purpose);

        $otpKey = $this->otpKey($purpose, $mobile);
        $attemptsKey = $this->attemptsKey($purpose, $mobile);

        $hashedCode = Cache::get($otpKey);

        if (! is_string($hashedCode)) {
            return ['status' => 'expired'];
        }

        if (RateLimiter::tooManyAttempts(
            $attemptsKey,
            self::VERIFY_LIMIT,
        )) {
            return [
                'status' => 'rate_limited',
                'seconds' => RateLimiter::availableIn($attemptsKey),
            ];
        }

        if (! Hash::check($code, $hashedCode)) {
            RateLimiter::hit(
                $attemptsKey,
                self::VERIFY_DECAY_SECONDS,
            );

            return ['status' => 'invalid'];
        }

        // OTP پس از تأیید قابل استفاده مجدد نیست.
        $this->forgetOtp($purpose, $mobile);

        RateLimiter::clear($attemptsKey);

        return ['status' => 'verified'];
    }

    public function expiresAt(
        string $purpose,
        string $mobile,
    ): ?int {
        $this->validatePurpose($purpose);

        $expiresAt = Cache::get(
            $this->expiresAtKey($purpose, $mobile),
        );

        if (
            ! Cache::has($this->otpKey($purpose, $mobile))
            || ! is_numeric($expiresAt)
            || (int) $expiresAt <= now()->timestamp
        ) {
            $this->forgetOtp($purpose, $mobile);

            return null;
        }

        return (int) $expiresAt;
    }

    public function forgetOtp(
        string $purpose,
        string $mobile,
    ): void {
        $this->validatePurpose($purpose);

        Cache::forget($this->otpKey($purpose, $mobile));
        Cache::forget($this->expiresAtKey($purpose, $mobile));
    }

    private function otpKey(
        string $purpose,
        string $mobile,
    ): string {
        return "{$purpose}:otp:{$mobile}";
    }

    private function expiresAtKey(
        string $purpose,
        string $mobile,
    ): string {
        return "{$purpose}:otp:expires-at:{$mobile}";
    }

    private function attemptsKey(
        string $purpose,
        string $mobile,
    ): string {
        return "{$purpose}:otp:attempts:{$mobile}";
    }

    private function validatePurpose(string $purpose): void
    {
        if (! in_array($purpose, [
            'registration',
            'password-recovery',
        ], true)) {
            throw new InvalidArgumentException(
                'Unsupported OTP purpose.',
            );
        }
    }
}
