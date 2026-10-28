<?php

use App\Contracts\SmsGateway;
use App\Enums\ContactType;
use App\Exceptions\Verification\SmsDeliveryException;
use App\Models\Contact;
use App\Support\Digits;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
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

    public function submit(): void
    {
        $this->mobile = Digits::toEnglish($this->mobile);

        $this->validate();
        $mobileKey = 'send-otp:mobile:'.$this->mobile;
        $ipKey = 'send-otp:ip:'.request()->ip();

        if (RateLimiter::tooManyAttempts($mobileKey, 1)) {
            $seconds = RateLimiter::availableIn($mobileKey);

            $this->addError(
                'mobile',
                "برای این شماره اخیراً کد درخواست شده است. {$seconds} ثانیه دیگر تلاش کنید."
            );

            return;
        }

        if (RateLimiter::tooManyAttempts($ipKey, 20)) {
            $seconds = RateLimiter::availableIn($ipKey);

            $this->addError(
                'mobile',
                "تعداد درخواست‌ها از این شبکه بیش از حد مجاز است. {$seconds} ثانیه دیگر تلاش کنید."
            );

            return;
        }

        $contactExists = Contact::query()
            ->where('type', ContactType::Mobile)
            ->where('value', $this->mobile)
            ->whereHas('people')
            ->exists();

        if ($contactExists) {
            $this->addError(
                'mobile',
                'این شماره موبایل قبلاً در سیستم ثبت شده است.'
            );

            return;
        }

        RateLimiter::hit($mobileKey, 120);
        RateLimiter::hit($ipKey, 120);

        $verificationCode = (string) random_int(10000, 99999);
        Cache::put(
            'registration:otp:'.$this->mobile,
            Hash::make($verificationCode),
            now()->addMinutes(2),
        );
        try {
            app(SmsGateway::class)->sendOtp($this->mobile, $verificationCode);
        } catch (SmsDeliveryException $e) {
            Cache::forget('registration:otp:'.$this->mobile);
            $this->addError(
                'mobile',
                'ارسال کد تأیید با مشکل مواجه شد. لطفاً کمی بعد دوباره تلاش کنید.',
            );

            return;
        }
        Flux::toast(
            text: 'کد تأیید به شماره موبایل شما ارسال شد.',
            variant: 'success',
        );
    }
};
