<?php

use App\Contracts\SmsGateway;
use App\Enums\ContactType;
use App\Enums\Nationality;
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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('layouts::auth')]
#[Title('بازیابی کلمه عبور')]
class extends Component
{
    public string $stage = 'identity';

    public Nationality $nationality = Nationality::Iranian;

    public string $identity = '';

    public string $mobile = '';

    public array $availableMobiles = [];

    public ?int $selectedContactId = null;

    public int $otpExpiresAt = 0;

    public string $verificationCode = '';

    #[Locked]
    public ?int $recoveryUserId = null;

    #[Locked]
    public ?int $recoveryContactId = null;

    #[Locked]
    public ?string $otpRecipient = null;

    public function submitIdentity(): void
    {
        $this->resetValidation();

        $this->identity = trim(Digits::toEnglish($this->identity));

        $this->validate([
            'nationality' => ['bail', 'required', Rule::enum(Nationality::class)],
            'identity' => ['bail', 'required', 'digits_between:10,20'],
        ]);

        $person = Person::query()
            ->where('nationality', $this->nationality->value)
            ->where('identity', $this->identity)
            ->first();

        if (! $person) {
            $this->stage = 'mobile';

            return;
        }

        $user = $person->user;

        if (! $user) {
            $this->stage = 'support';

            return;
        }

        $contacts = $person->contacts()
            ->where('contacts.type', ContactType::Mobile->value)
            ->with('people.user')
            ->get();

        $this->availableMobiles = [];

        foreach ($contacts as $contact) {
            $users = $contact->people
                ->pluck('user')
                ->filter()
                ->unique('id')
                ->values();

            if (
                $users->count() !== 1
                || $users->first()->id !== $user->id
            ) {
                continue;
            }

            $this->availableMobiles[] = [
                'id' => $contact->id,
                'masked' => $this->maskMobile($contact->value),
            ];
        }

        if (count($this->availableMobiles) === 0) {
            $this->stage = 'support';

            return;
        }

        if (count($this->availableMobiles) === 1) {
            $this->selectedContactId = $this->availableMobiles[0]['id'];

            $this->sendSelectedMobileOtp();

            return;
        }

        $this->stage = 'choose-mobile';
    }

    public function sendSelectedMobileOtp(): void
    {
        $this->resetValidation();

        $this->validate([
            'selectedContactId' => [
                'bail',
                'required',
                'integer',
                Rule::in(array_column($this->availableMobiles, 'id')),
            ],
        ]);

        $person = Person::query()
            ->where('nationality', $this->nationality->value)
            ->where('identity', $this->identity)
            ->first();

        if (! $person || ! $person->user) {
            $this->stage = 'support';

            return;
        }

        $contact = $person->contacts()
            ->where('contacts.id', $this->selectedContactId)
            ->where('contacts.type', ContactType::Mobile->value)
            ->with('people.user')
            ->first();

        if (! $contact) {
            $this->addError('selectedContactId', 'شماره انتخاب‌شده معتبر نیست.');

            return;
        }

        $users = $contact->people
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        if (
            $users->count() !== 1
            || $users->first()->id !== $person->user->id
        ) {
            $this->stage = 'support';

            return;
        }

        $this->sendOtp($contact, $person->user);
    }

    public function submitMobile(): void
    {
        $this->resetValidation();

        $this->mobile = Digits::toEnglish($this->mobile);

        $this->validate([
            'mobile' => 'required|digits:11|starts_with:09',
        ]);

        $contact = Contact::query()
            ->where('type', ContactType::Mobile)
            ->where('value', $this->mobile)
            ->with('people.user')
            ->first();

        if (! $contact) {
            $this->stage = 'not-found';

            return;
        }

        $users = $contact->people
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        if ($users->count() !== 1) {
            $this->stage = 'support';

            return;
        }

        $this->sendOtp($contact, $users->first());
    }

    private function sendOtp(Contact $contact, User $user): void
    {
        $this->recoveryUserId = $user->id;
        $this->recoveryContactId = $contact->id;
        $this->otpRecipient = $contact->value;

        try {
            $result = app(OtpService::class)->send(
                purpose: OtpPurpose::PasswordReset,
                channel: OtpChannel::Sms,
                recipient: $contact->value,
                ip: request()->ip(),
            );
        } catch (SmsDeliveryException $e) {
            report($e);

            $this->addError(
                'recovery',
                'ارسال کد تأیید با مشکل مواجه شد. لطفاً کمی بعد دوباره تلاش کنید.',
            );

            return;
        }

        match ($result['status']) {
            'sent' => $this->handleOtpSent($result),

            'active' => $this->handleActiveOtp($result),

            'recipient_limited' => $this->addError(
                'recovery',
                'برای این شماره اخیراً کد درخواست شده است. '
                .($result['seconds'] ?? 0)
                .' ثانیه دیگر تلاش کنید.',
            ),

            'ip_limited' => $this->addError(
                'recovery',
                'تعداد درخواست‌ها از این شبکه بیش از حد مجاز است. '
                .($result['seconds'] ?? 0)
                .' ثانیه دیگر تلاش کنید.',
            ),

            default => $this->addError(
                'recovery',
                'ارسال کد تأیید با مشکل مواجه شد. لطفاً دوباره تلاش کنید.',
            ),
        };
    }

    private function handleOtpSent(array $result): void
    {
        $this->otpExpiresAt = $this->resolveOtpTimestamp($result);
        $this->stage = 'otp';

        Flux::toast(
            text: 'کد تأیید ارسال شد.',
            variant: 'success',
        );

        Flux::modal('verify-mobile')->show();
    }

    private function handleActiveOtp(array $result): void
    {
        $this->otpExpiresAt = $this->resolveOtpTimestamp($result);
        $this->stage = 'otp';

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
        $this->verificationCode = trim(
            Digits::toEnglish($this->verificationCode)
        );

        $this->validate([
            'verificationCode' => ['bail', 'required', 'digits:6'],
        ]);

        if (
            $this->recoveryUserId === null
            || $this->recoveryContactId === null
            || $this->otpRecipient === null
        ) {
            $this->addError(
                'verificationCode',
                'اطلاعات درخواست بازیابی معتبر نیست. دوباره شروع کنید.',
            );

            return;
        }

        $result = app(OtpService::class)->verify(
            purpose: OtpPurpose::PasswordReset,
            channel: OtpChannel::Sms,
            recipient: $this->otpRecipient,
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
                'کد تأیید منقضی شده است یا درخواست معتبری وجود ندارد.',
            ),

            'rate_limited' => $this->addError(
                'verificationCode',
                'تعداد تلاش‌ها بیش از حد مجاز است. '
                .($result['seconds'] ?? 0)
                .' ثانیه دیگر تلاش کنید.',
            ),

            default => $this->addError(
                'verificationCode',
                'بررسی کد تأیید با مشکل مواجه شد.',
            ),
        };
    }

    private function handleOtpVerified(): void
    {
        $user = User::query()
            ->whereKey($this->recoveryUserId)
            ->whereHas('person.contacts', function ($query) {
                $query
                    ->whereKey($this->recoveryContactId)
                    ->where('type', ContactType::Mobile);
            })
            ->first();

        if (! $user) {
            $this->stage = 'support';

            return;
        }

        $temporaryPassword = PasswordGenerator::generate();

        try {
            app(SmsGateway::class)->sendPassword(
                mobile: $this->otpRecipient,
                username: $user->username,
                password: $temporaryPassword,
            );
        } catch (SmsDeliveryException $e) {
            report($e);

            $this->addError(
                'recovery',
                'ارسال رمز جدید ناموفق بود. رمز قبلی شما تغییر نکرده است. '
                .'لطفاً دوباره فرایند بازیابی را انجام دهید.',
            );

            $this->stage = 'delivery-failed';

            Flux::modal('verify-mobile')->close();

            return;
        }

        $user->forceFill([
            'password' => $temporaryPassword,
            'password_is_temporary' => true,
        ])->save();

        Flux::modal('verify-mobile')->close();

        $this->reset('verificationCode');

        $this->stage = 'completed';

        Flux::toast(
            text: 'رمز عبور جدید به شماره موبایل شما ارسال شد.',
            variant: 'success',
        );
    }

    private function maskMobile(string $mobile): string
    {
        return substr($mobile, -4).'****'.substr($mobile, 0, 3);
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

    public function startOver(): void
    {
        $this->reset([
            'stage',
            'nationality',
            'identity',
            'mobile',
            'availableMobiles',
            'selectedContactId',
            'otpExpiresAt',
            'verificationCode',
            'recoveryUserId',
            'recoveryContactId',
            'otpRecipient',
        ]);

        $this->stage = 'identity';
        $this->resetValidation();
    }
};
