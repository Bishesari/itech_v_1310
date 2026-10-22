
<div class="mx-auto flex w-full max-w-md flex-col gap-6">
    <x-auth-header
        title="ورود یا ثبت‌نام"
        description="شماره موبایل و رمز عبور حساب خود را وارد کنید."
    />

    <x-auth-session-status
        class="text-center"
        :status="session('status')"
    />

    <form wire:submit="login" class="flex flex-col gap-5">
        <flux:input
            wire:model="mobile"
            name="mobile"
            label="شماره موبایل"
            type="tel"
            inputmode="tel"
            autocomplete="tel"
            placeholder="09123456789"
            required
            autofocus
        />

        <flux:input
            wire:model="password"
            name="password"
            label="رمز عبور"
            type="password"
            autocomplete="current-password"
            placeholder="رمز عبور"
            viewable
            required
        />

        <flux:checkbox
            wire:model="remember"
            name="remember"
            label="مرا به خاطر بسپار"
        />

        <flux:button
            type="submit"
            variant="primary"
            class="w-full"
            wire:loading.attr="disabled"
            wire:target="login"
        >
            ورود
        </flux:button>
    </form>

    @if (count($matchingAccounts) > 1)
        <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="font-medium">
                انتخاب حساب کاربری
            </div>

            <p class="text-sm text-zinc-500">
                این شماره و رمز عبور با چند حساب مطابقت دارد.
                حساب موردنظر را انتخاب کنید.
            </p>

            @foreach ($matchingAccounts as $account)
                <flux:button
                    wire:key="account-{{ $account['id'] }}"
                    type="button"
                    variant="outline"
                    class="w-full"
                    wire:click="selectAccount({{ $account['id'] }})"
                    wire:loading.attr="disabled"
                    wire:target="selectAccount"
                >
                    {{ $account['label'] }}
                </flux:button>
            @endforeach
        </div>
    @endif

    <p class="text-center text-sm text-zinc-500">
        ثبت‌نام با کد یک‌بارمصرف پس از پیاده‌سازی تأیید موبایل فعال خواهد شد.
    </p>
</div>
