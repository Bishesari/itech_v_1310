<?php

use App\Models\Mobile;
use App\Models\User;
use App\Support\MobileNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::auth')] class extends Component
{
    public string $mobile = '';

    public string $password = '';

    public bool $remember = false;

    public array $matchingAccounts = [];

    public function login(): void
    {
        $this->validate([
            'mobile' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['boolean'],
        ]);
        $number = MobileNumber::normalize($this->mobile);

        if ($number === null) {
            throw ValidationException::withMessages([
                'mobile' => 'شماره موبایل معتبر وارد کنید.',
            ]);
        }

        $key = 'mobile-login:'.hash(
            'sha256',
            $number.'|'.request()->ip()
        );

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'mobile' => 'تعداد تلاش‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.',
            ]);
        }

        RateLimiter::hit($key, 60);

        $mobile = Mobile::query()
            ->where('number', $number)
            ->with('users.person')
            ->first();

        $accounts = $mobile?->users
            ->filter(fn (User $user) => filled($user->password)
                && Hash::check($this->password, $user->password)
            )
            ->values() ?? collect();

        if ($accounts->isEmpty()) {
            throw ValidationException::withMessages([
                'password' => 'شماره موبایل یا رمز عبور نادرست است.',
            ]);
        }

        if ($accounts->count() === 1) {
            $this->authenticate($accounts->first(), $key);

            return;
        }

        $this->matchingAccounts = $accounts
            ->map(fn (User $user) => [
                'id' => $user->id,
                'label' => $this->accountLabel($user),
            ])
            ->all();
    }

    public function selectAccount(int $userId): void
    {
        $number = MobileNumber::normalize($this->mobile);

        if ($number === null) {
            $this->reset('matchingAccounts');

            return;
        }

        $key = 'mobile-login:'.hash(
            'sha256',
            $number.'|'.request()->ip()
        );

        $mobile = Mobile::query()
            ->where('number', $number)
            ->first();

        $user = $mobile?->users()
            ->whereKey($userId)
            ->with('person')
            ->first();

        if (
            ! $user
            || blank($user->password)
            || ! Hash::check($this->password, $user->password)
        ) {
            $this->reset('matchingAccounts');

            throw ValidationException::withMessages([
                'password' => 'اطلاعات ورود معتبر نیست. دوباره تلاش کنید.',
            ]);
        }

        $this->authenticate($user, $key);
    }

    private function authenticate(User $user, string $key): void
    {
        Auth::guard('web')->login($user, $this->remember);

        request()->session()->regenerate();

        RateLimiter::clear($key);

        $redirectTo = redirect()
            ->intended(route('dashboard'))
            ->getTargetUrl();

        $this->redirect($redirectTo, navigate: true);
    }

    private function accountLabel(User $user): string
    {
        $name = trim(implode(' ', array_filter([
            $user->person?->first_name_fa,
            $user->person?->last_name_fa,
        ])));

        return $name !== ''
            ? $name
            : 'حساب تکمیل‌نشده شماره '.$user->id;
    }
};
