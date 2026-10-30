<?php

use App\Contracts\SmsGateway;
use App\Enums\ContactType;
use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Exceptions\Verification\SmsDeliveryException;
use App\Models\Contact;
use App\Models\Person;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Digits;
use App\Support\PasswordGenerator;
use Carbon\Carbon;
use Flux\Flux;
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

    public bool $mobileAlreadyRegistered = false;

    public int $otpExpiresAt = 0;

    #[Validate('required|digits:6')]
    public string $verificationCode = '';

    public function submit(): void
    {
        $this->mobile = Digits::toEnglish($this->mobile);
        $this->verifiedMobile = null;
        $this->mobileAlreadyRegistered = false;

        $this->validateOnly('mobile');

        $contactExists = Contact::query()
            ->where('type', ContactType::Mobile)
            ->where('value', $this->mobile)
            ->whereHas('people')
            ->exists();

        if ($contactExists) {
            $this->mobileAlreadyRegistered = true;

            return;
        }

        $result = app(OtpService::class)->send(
            purpose: OtpPurpose::Registration,
            channel: OtpChannel::Sms,
            recipient: $this->mobile,
            ip: request()->ip(),
        );

        match ($result['status']) {
            'sent' => $this->handleOtpSent($result),

            'active' => $this->handleActiveOtp($result),

            'recipient_limited' => $this->addError(
                'mobile',
                'برای این شماره اخیراً کد درخواست شده است. '
                .($result['seconds'] ?? 0)
                .' ثانیه دیگر تلاش کنید.',
            ),

            'ip_limited' => $this->addError(
                'mobile',
                'تعداد درخواست‌ها از این شبکه بیش از حد مجاز است. '
                .($result['seconds'] ?? 0)
                .' ثانیه دیگر تلاش کنید.',
            ),

            'delivery_failed' => $this->addError(
                'mobile',
                'ارسال کد تأیید با مشکل مواجه شد. لطفاً کمی بعد دوباره تلاش کنید.',
            ),

            default => $this->addError(
                'mobile',
                'ارسال کد تأیید با مشکل مواجه شد. لطفاً دوباره تلاش کنید.',
            ),
        };
    }

    private function handleOtpSent(array $result): void
    {
        $this->otpExpiresAt = $this->resolveOtpTimestamp($result);

        Flux::toast(
            text: 'کد تأیید به شماره موبایل شما ارسال شد.',
            variant: 'success',
        );

        Flux::modal('verify-mobile')->show();
    }

    private function handleActiveOtp(array $result): void
    {
        $this->otpExpiresAt = $this->resolveOtpTimestamp($result);

        Flux::modal('verify-mobile')->show();
    }

    private function resolveOtpTimestamp(array $result): int
    {
        $expiresAt = $result['expires_at'] ?? null;

        if ($expiresAt instanceof DateTimeInterface) {
            return $expiresAt->getTimestamp();
        }

        if (is_numeric($expiresAt)) {
            return (int) $expiresAt;
        }

        if (is_string($expiresAt)) {
            return Carbon::parse($expiresAt)->timestamp;
        }

        return now()->addMinutes(2)->timestamp;
    }

    public function verifyOtp(): void
    {
        $this->verificationCode = Digits::toEnglish(
            $this->verificationCode,
        );

        $this->mobile = Digits::toEnglish($this->mobile);

        $this->validateOnly('mobile');
        $this->validateOnly('verificationCode');

        $result = app(OtpService::class)->verify(
            purpose: OtpPurpose::Registration,
            channel: OtpChannel::Sms,
            recipient: $this->mobile,
            code: $this->verificationCode,
            ip: request()->ip(),
        );

        match ($result['status']) {
            'verified' => $this->handleOtpVerified(),

            'invalid' => $this->addError(
                'verificationCode',
                'کد تأیید نامعتبر است.',
            ),

            'expired' => $this->addError(
                'verificationCode',
                'کد تأیید منقضی شده است یا هنوز درخواستی ثبت نکرده‌اید.',
            ),

            'rate_limited' => $this->addError(
                'verificationCode',
                'تعداد تلاش‌ها بیش از حد مجاز است. '
                .($result['seconds'] ?? 0)
                .' ثانیه دیگر تلاش کنید.',
            ),

            default => $this->addError(
                'verificationCode',
                'بررسی کد تأیید با مشکل مواجه شد. لطفاً دوباره تلاش کنید.',
            ),
        };
    }

    private function handleOtpVerified(): void
    {
        $this->verifiedMobile = $this->mobile;

        Flux::toast(
            text: 'شماره موبایل با موفقیت تأیید شد.',
            variant: 'success',
        );

        $this->register();
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

    public function clearVerificationErrors(): void
    {
        $this->reset('verificationCode');
        $this->resetValidation('verificationCode');
    }

    public function closeExpiredOtpModal(): void
    {
        Flux::modal('verify-mobile')->close();
    }

    public function getOtpRemainingSecondsProperty(): int
    {
        return max(0, $this->otpExpiresAt - now()->timestamp);
    }


};
