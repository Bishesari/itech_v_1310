<div class="w-88">
    <flux:card variant="soft" size="lg">
        <flux:card.header>
            <flux:card.heading>{{__('فرم ثبت نام')}}</flux:card.heading>
            <flux:card.subheading>{{__('برای شروع، شماره موبایل خود را وارد کنید.')}}</flux:card.subheading>
        </flux:card.header>


        <form wire:submit="submit" autocomplete="off">
            <flux:card.body class="space-y-6">
                {{-- موبایل --}}
                <x-my.flt_lbl name="mobile" label="{{ __('شماره موبایل') }}" required dir="ltr"
                              :attributes="$attributes->merge(['maxlength' => 11, 'inputmode' => 'numeric'])"
                              class="tracking-widest font-semibold text-lg!" autofocus
                />
            </flux:card.body>

            <flux:card.footer>
                <flux:button type="submit" variant="filled" color="blue" class="cursor-pointer">
                    {{__('ثبت نام')}}
                </flux:button>

                <flux:text class="text-center mt-5">
                    <span>{{ __('حساب کاربری داشته اید؟') }}</span>
                    <flux:link :href="route('login')" wire:navigate class="mr-2 ">
                        <span>{{ __('وارد شوید!') }}</span>
                    </flux:link>
                </flux:text>
            </flux:card.footer>
        </form>
    </flux:card>

    <flux:modal name="verify-mobile" @close="clearVerificationErrors()" class="w-80">
        <div
            x-data="{
            expired: false,
            expiresAt: @js($otpExpiresAt),
            remaining: 0,
            timer: null,

            init() {
                this.updateRemaining();

                this.timer = setInterval(() => {
                    this.updateRemaining();
                }, 1000);
            },

            updateRemaining() {
                this.remaining = Math.max(
                    0,
                    this.expiresAt - Math.floor(Date.now() / 1000)
                );
                if (this.remaining === 0 && !this.expired) {
                    this.expired = true;
                    $wire.closeExpiredOtpModal();
                }
            },

            formatTime() {
                const minutes = Math.floor(this.remaining / 60);
                const seconds = this.remaining % 60;

                return String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');
            },
        }"
            x-effect="
            if (expiresAt !== @js($otpExpiresAt)) {
                expiresAt = @js($otpExpiresAt);
                updateRemaining();
            }
        "
            x-on:alpine:destroy="clearInterval(timer)"
            class="space-y-6"
        >
            <form wire:submit="verifyOtp" class="space-y-8">
                <div class="max-w-64 mx-auto space-y-2">
                    <flux:heading size="lg" class="text-center">
                        {{ __('تأیید شماره موبایل') }}
                    </flux:heading>

                    <flux:text class="text-center">
                        {{ __('کد ارسال‌شده را وارد کنید.') }}
                    </flux:text>
                </div>

                <flux:otp wire:model="verificationCode" label="کد تأیید" label:sr-only :error:icon="false"
                          error:class="text-center" class="mx-auto" dir="ltr">
                    <flux:otp.input autofocus/>
                    <flux:otp.input />
                    <flux:otp.input />
                    <flux:otp.separator />
                    <flux:otp.input />
                    <flux:otp.input />
                    <flux:otp.input />
                </flux:otp>

                <flux:text class="text-center" aria-live="polite">
                    <span x-show="remaining > 0">{{__('زمان باقی‌مانده:')}}
                        <span dir="ltr" class="font-semibold tabular-nums font-mono" x-text="formatTime()"></span>
                    </span>
                    <span x-show="remaining <= 0">{{__('کد تأیید منقضی شده است.')}}</span>
                </flux:text>

                <flux:button variant="primary" type="submit" class="w-full cursor-pointer">{{__('تأیید کد')}}</flux:button>
            </form>
        </div>
    </flux:modal>
</div>
