<div class="w-85">
    <flux:card variant="soft" size="sm">
        <flux:card.header>
            <flux:card.heading class="text-center">
                {{ __('بازیابی کلمه عبور') }}
            </flux:card.heading>

            <flux:card.subheading class="text-center">
                {{ __('برای بازیابی حساب کاربری، اطلاعات زیر را وارد کنید.') }}
            </flux:card.subheading>
        </flux:card.header>

        <flux:card.body class="space-y-6">

            {{-- مرحله اول: اطلاعات هویتی --}}
            @if ($stage === 'identity')
                <form wire:submit="submitIdentity" class="space-y-5" autocomplete="off">

                    {{-- نوع تابعیت --}}
                    <flux:field class="mb-7">
                        <flux:label>{{ __('تابعیت') }}</flux:label>

                        <flux:radio.group wire:model="nationality" variant="cards" class="max-sm:flex-col">
                            @foreach (\App\Enums\Nationality::cases() as $type)
                                <flux:radio
                                    value="{{ $type->value }}"
                                    label="{{ $type->label() }}"
                                    class="cursor-pointer"
                                />
                            @endforeach
                        </flux:radio.group>

                        <flux:error name="nationality" class="-mt-5! text-xs font-light!"/>
                    </flux:field>

                    <x-my.flt_lbl
                        name="identity"
                        label="{{ __('کد ملی / شناسه هویتی') }}"
                        required
                        dir="ltr"
                        :attributes="$attributes->merge(['maxlength' => 20, 'inputmode' => 'numeric'])"
                        class="tracking-widest text-base! font-instrument-sans"
                        autofocus
                    />

                    <flux:button type="submit" variant="filled" color="yellow" class="w-full cursor-pointer">
                        {{ __('ادامه') }}
                    </flux:button>
                </form>
            @endif

            {{-- مرحله دوم: دریافت شماره موبایل --}}
            @if ($stage === 'mobile')
                <form wire:submit="submitMobile" class="space-y-5" autocomplete="off">

                    <flux:callout icon="information-circle" color="violet">
                        <flux:callout.text>{{ __('اطلاعات هویتی در پیدا نشد. شماره موبایل خود را وارد کنید.') }}</flux:callout.text>
                    </flux:callout>

                    <x-my.flt_lbl x-data x-effect="if ($wire.stage === 'mobile') {$nextTick(() => $refs.mobileInput?.focus())}"
                                  x-ref="mobileInput" name="mobile"
                                  label="{{ __('شماره موبایل') }}"
                                  required
                                  dir="ltr"
                                  :attributes="$attributes->merge(['maxlength' => 11, 'inputmode' => 'numeric'])"
                                  class="tracking-widest text-base!"
                    />

                    <flux:error name="recovery"/>

                    <flux:button type="submit" variant="filled" color="blue" class="w-full cursor-pointer">
                        {{ __('ارسال کد تأیید') }}
                    </flux:button>
                </form>
            @endif

            {{-- مرحله سوم: انتخاب شماره موبایل --}}
            @if ($stage === 'choose-mobile')
                <form wire:submit="sendSelectedMobileOtp" class="space-y-5">

                    <flux:text class="text-center">
                        {{ __('یک شماره موبایل برای دریافت کد تأیید انتخاب کنید.') }}
                    </flux:text>

                    <flux:radio.group
                        wire:model="selectedContactId"
                        variant="cards"
                        class="max-sm:flex-col flex-wrap"
                    >
                        @foreach ($availableMobiles as $item)
                            <flux:radio
                                wire:key="mobile-{{ $item['id'] }}"
                                value="{{ $item['id'] }}"
                                label="{{ $item['masked'] }}"
                                class="cursor-pointer text-center tracking-widest font-instrument-sans"
                            />
                        @endforeach
                    </flux:radio.group>

                    <flux:error name="selectedContactId"/>
                    <flux:error name="recovery"/>

                    <flux:button type="submit" variant="filled" color="blue" class="w-full cursor-pointer">
                        {{ __('ارسال کد تأیید') }}
                    </flux:button>
                </form>
            @endif

            {{-- حساب پیدا نشد --}}
            @if ($stage === 'not-found')
                <flux:callout icon="information-circle" color="blue">
                    <flux:callout.heading>
                        {{ __('حساب کاربری پیدا نشد') }}
                    </flux:callout.heading>

                    <flux:callout.text>
                        {{ __('شماره موبایل واردشده به حساب کاربری متصل نیست.') }}
                    </flux:callout.text>
                </flux:callout>

                <flux:button :href="route('register')" wire:navigate variant="filled" color="blue" class="w-full">
                    {{ __('ثبت نام') }}
                </flux:button>
            @endif

            {{-- نیاز به پشتیبانی --}}
            @if ($stage === 'support')
                <flux:callout icon="exclamation-triangle" color="orange" class="text-center">
                    <flux:callout.text class="font-bold">
                        {{ __('خطا کد : 100 - بازیابی امکانپذیر نیست!') }}
                    </flux:callout.text>
                    <flux:callout.text>
                        {{ __('لطفاً با پشتیبانی تماس بگیرید.') }}
                    </flux:callout.text>
                    <flux:callout.text class="font-instrument-sans tracking-widest font-bold">
                        {{ __('09034336111') }}
                    </flux:callout.text>
                </flux:callout>
            @endif

            {{-- خطای ارسال رمز جدید --}}
            @if ($stage === 'delivery-failed')
                <flux:callout icon="exclamation-triangle" color="danger">
                    <flux:callout.heading>
                        {{ __('ارسال رمز جدید ناموفق بود') }}
                    </flux:callout.heading>

                    <flux:callout.text>
                        {{ __('رمز قبلی شما تغییر نکرده است. لطفاً دوباره تلاش کنید.') }}
                    </flux:callout.text>
                </flux:callout>

                <flux:error name="recovery"/>
            @endif

            {{-- بازیابی موفق --}}
            @if ($stage === 'completed')
                <flux:callout icon="check-circle" color="green">
                    <flux:callout.heading>
                        {{ __('بازیابی موفق') }}
                    </flux:callout.heading>

                    <flux:callout.text>
                        {{ __('رمز عبور جدید برای شما پیامک شد. اکنون می‌توانید وارد حساب کاربری خود شوید.') }}
                    </flux:callout.text>
                </flux:callout>

                <flux:button :href="route('login')" wire:navigate variant="filled" color="blue" class="w-full">
                    {{ __('ورود به حساب کاربری') }}
                </flux:button>
            @endif

            {{-- خطاهای عمومی --}}
            @if (in_array($stage, ['identity', 'mobile', 'choose-mobile']))
                <flux:error name="recovery"/>
            @endif
        </flux:card.body>

        <flux:card.footer class="pt-0">
            @if ($stage != 'identity')
                <flux:button
                    type="button"
                    wire:click="startOver"
                    variant="ghost"
                    class="w-full cursor-pointer"
                >
                    {{ __('شروع مجدد') }}
                </flux:button>
            @endif

            <div class="text-sm text-zinc-600 dark:text-zinc-400 flex justify-between">
                <span class="pt-1.5 mr-2">{{ __('رمز خود را به یاد آوردید؟') }}</span>

                <flux:button :href="route('login')" wire:navigate variant="ghost" icon:trailing="arrow-left-end-on-rectangle"
                             size="sm" class="cursor-pointer"
                             x-data="{ loading: false }"
                             @click="loading = true"
                >
                    <span x-show="!loading">{{ __('وارد شوید!') }}</span>
                    <flux:icon.loading x-show="loading" class="inline size-4 text-yellow-500 ml-5"/>
                </flux:button>
            </div>
        </flux:card.footer>
    </flux:card>

    {{-- مودال تأیید OTP --}}
    @if ($stage === 'otp')
        <flux:modal
            name="verify-mobile"
            @close="clearVerificationErrors()"
            class="w-80"
        >
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

                    <flux:otp
                        wire:model="verificationCode"
                        label="کد تأیید"
                        label:sr-only
                        :error:icon="false"
                        error:class="text-center"
                        class="mx-auto"
                        dir="ltr"
                    >
                        <flux:otp.input autofocus/>
                        <flux:otp.input/>
                        <flux:otp.input/>
                        <flux:otp.separator/>
                        <flux:otp.input/>
                        <flux:otp.input/>
                        <flux:otp.input/>
                    </flux:otp>

                    <flux:text class="text-center" aria-live="polite">
                        <span x-show="remaining > 0">
                            {{ __('زمان باقی‌مانده:') }}
                            <span
                                dir="ltr"
                                class="font-semibold tabular-nums font-mono"
                                x-text="formatTime()"
                            ></span>
                        </span>

                        <span x-show="remaining <= 0">
                            {{ __('کد تأیید منقضی شده است.') }}
                        </span>
                    </flux:text>

                    <flux:button
                        variant="primary"
                        type="submit"
                        class="w-full cursor-pointer"
                    >
                        {{ __('تأیید کد') }}
                    </flux:button>
                </form>
            </div>
        </flux:modal>
    @endif
</div>
