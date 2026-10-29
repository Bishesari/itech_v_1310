<?php

use App\Contracts\SmsGateway;
use App\Enums\ContactType;
use App\Exceptions\Verification\SmsDeliveryException;
use App\Models\Contact;
use App\Models\Person;
use App\Models\User;
use App\Support\Digits;
use App\Support\PasswordGenerator;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts::auth')]
#[Title('ثبت نام')]
class extends Component
{
    #[Validate('bail|required|digits:11|starts_with:09')]
    public string $mobile = '';

    #[Locked]
    public ?string $verifiedMobile = null;

    #[Validate('required|digits:6')]
    public string $verificationCode = '';

    public int $otpExpiresAt = 0;

    public bool $mobileAlreadyRegistered = false;

    public function submit(): void
    {
        $this->mobile = Digits::toEnglish($this->mobile);
        $this->verifiedMobile = null;

        $this->mobileAlreadyRegistered = false;

        $this->validateOnly('mobile');

        $otpKey = 'registration:otp:'.$this->mobile;
        $expiresAtKey = 'registration:otp:expires-at:'.$this->mobile;

        // اگر OTP هنوز معتبر است، پیامک مجدد نفرست.
        $expiresAt = Cache::get($expiresAtKey);

        if (
            Cache::has($otpKey)
            && is_numeric($expiresAt)
            && (int) $expiresAt > now()->timestamp
        ) {
            $this->otpExpiresAt = (int) $expiresAt;

            Flux::modal('verify-mobile')->show();

            return;
        }

        // پاک‌کردن اطلاعات OTP منقضی‌شده.
        Cache::forget($otpKey);
        Cache::forget($expiresAtKey);

        $mobileKey = 'send-otp:mobile:'.$this->mobile;
        $ipKey = 'send-otp:ip:'.request()->ip();

        // محدودیت درخواست برای هر شماره موبایل.
        if (RateLimiter::tooManyAttempts($mobileKey, 1)) {
            $seconds = RateLimiter::availableIn($mobileKey);

            $this->addError(
                'mobile',
                "برای این شماره اخیراً کد درخواست شده است. {$seconds} ثانیه دیگر تلاش کنید.",
            );

            return;
        }

        // محدودیت درخواست برای هر IP.
        if (RateLimiter::tooManyAttempts($ipKey, 7)) {
            $seconds = RateLimiter::availableIn($ipKey);

            $this->addError(
                'mobile',
                "تعداد درخواست‌ها از این شبکه بیش از حد مجاز است. {$seconds} ثانیه دیگر تلاش کنید.",
            );

            return;
        }

        // بررسی ثبت قبلی شماره.
        $contactExists = Contact::query()
            ->where('type', ContactType::Mobile)
            ->where('value', $this->mobile)
            ->whereHas('people')
            ->exists();

        if ($contactExists) {
            $this->mobileAlreadyRegistered = true;

            return;
        }

        // شمارش درخواست، حتی اگر سرویس پیامک با خطا مواجه شود.
        RateLimiter::hit($mobileKey, 120);
        RateLimiter::hit($ipKey, 120);

        $verificationCode = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(2);

        Cache::put(
            $otpKey,
            Hash::make($verificationCode),
            $expiresAt,
        );

        Cache::put(
            $expiresAtKey,
            $expiresAt->timestamp,
            $expiresAt,
        );

        try {
            app(SmsGateway::class)->sendOtp(
                $this->mobile,
                $verificationCode,
            );
        } catch (SmsDeliveryException $e) {
            Cache::forget($otpKey);
            Cache::forget($expiresAtKey);

            $this->addError(
                'mobile',
                'ارسال کد تأیید با مشکل مواجه شد. لطفاً کمی بعد دوباره تلاش کنید.',
            );

            return;
        }

        $this->otpExpiresAt = $expiresAt->timestamp;

        Flux::toast(
            text: 'کد تأیید به شماره موبایل شما ارسال شد.',
            variant: 'success',
        );

        Flux::modal('verify-mobile')->show();
    }

    public function verifyOtp(): void
    {
        $this->verificationCode = Digits::toEnglish(
            $this->verificationCode,
        );
        $this->mobile = Digits::toEnglish($this->mobile);

        $this->validateOnly('mobile');
        $this->validateOnly('verificationCode');

        if ($this->verifiedMobile !== null && $this->verifiedMobile !== $this->mobile) {
            $this->verifiedMobile = null;
        }

        $otpKey = 'registration:otp:'.$this->mobile;
        $attemptsKey = 'registration:otp:attempts:'.$this->mobile;

        $hashedCode = Cache::get($otpKey);

        if (
            $this->verifiedMobile !== null
            && $this->verifiedMobile !== $this->mobile
        ) {
            $this->verifiedMobile = null;
        }

        if (! is_string($hashedCode)) {
            $this->addError(
                'verificationCode',
                'کد تأیید منقضی شده است یا هنوز درخواستی ثبت نکرده‌اید.',
            );

            return;
        }

        if (RateLimiter::tooManyAttempts($attemptsKey, 5)) {
            $seconds = RateLimiter::availableIn($attemptsKey);

            $this->addError(
                'verificationCode',
                "تعداد تلاش‌ها بیش از حد مجاز است. {$seconds} ثانیه دیگر تلاش کنید.",
            );

            return;
        }

        if (! Hash::check($this->verificationCode, $hashedCode)) {
            RateLimiter::hit($attemptsKey, 300);

            $this->addError(
                'verificationCode',
                'کد تأیید نامعتبر است.',
            );

            return;
        }
        $this->verifiedMobile = $this->mobile;

        Cache::forget($otpKey);
        Cache::forget('registration:otp:expires-at:'.$this->mobile);
        RateLimiter::clear($attemptsKey);

        Flux::toast(
            text: 'شماره موبایل با موفقیت تأیید شد.',
            variant: 'success',
        );
        $this->register();
    }

    public function getOtpRemainingSecondsProperty(): int
    {
        return max(0, $this->otpExpiresAt - now()->timestamp);
    }

    public function clearVerificationErrors(): void
    {
        $this->reset('verificationCode');
        $this->resetValidation('verificationCode');
    }

    public function closeExpiredOtpModal(): void
    {
        Flux::modal('verify-mobile')->close();
    }

    public function register(): void
    {
        $this->mobile = Digits::toEnglish($this->mobile);

        $this->validateOnly('mobile');

        if (
            $this->verifiedMobile === null
            || $this->verifiedMobile !== $this->mobile
        ) {
            $this->verifiedMobile = null;

            $this->addError(
                'mobile',
                'ابتدا شماره موبایل خود را تأیید کنید.',
            );

            return;
        }

        $contactExists = Contact::query()
            ->where('type', ContactType::Mobile)
            ->where('value', $this->verifiedMobile)
            ->whereHas('people')
            ->exists();

        if ($contactExists) {
            $this->verifiedMobile = null;

            $this->addError(
                'mobile',
                'این شماره موبایل قبلاً در سیستم ثبت شده است.',
            );

            return;
        }

        $temporaryPassword = PasswordGenerator::generate();

        $user = DB::transaction(function () use ($temporaryPassword) {
            $person = Person::query()->create([]);

            $contact = Contact::query()->create([
                'type' => ContactType::Mobile,
                'value' => $this->verifiedMobile,
            ]);

            $person->contacts()->attach($contact->id, [
                'verified' => true,
                'active' => true,
            ]);

            return User::query()->create([
                'person_id' => $person->id,
                'username' => $this->verifiedMobile,
                'password' => $temporaryPassword,
                'password_is_temporary' => true,
                'is_active' => true,
            ]);
        });

        try {
            app(SmsGateway::class)->sendPassword(
                mobile: $this->verifiedMobile,
                username: $user->username,
                password: $temporaryPassword,
            );
        } catch (SmsDeliveryException $e) {
            report($e);

            $this->addError(
                'mobile',
                'حساب ساخته شد، اما ارسال پیامک رمز موقت ناموفق بود. لطفاً با پشتیبانی تماس بگیرید.',
            );

            return;
        }

        Auth::login($user);

        request()->session()->regenerate();

        $this->redirectIntended(
            default: route('dashboard'),
            navigate: true,
        );
    }
};
