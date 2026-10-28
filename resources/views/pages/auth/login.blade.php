<x-layouts::auth :title="__('ورود')">

    <flux:card variant="soft" size="sm" class="w-85">
        <flux:card.header>
            <flux:card.heading class="text-center">{{__('فرم ورود به حساب')}}</flux:card.heading>
            <flux:card.subheading class="text-center">{{__('برای شروع، شماره موبایل خود را وارد کنید.')}}</flux:card.subheading>
        </flux:card.header>
        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />
        <x-passkey-verify />

        <flux:card.body class="space-y-6">
            <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5" autocomplete="off"
                  x-data="{ loading: false }"
                  @submit="loading = true"
            >
                @csrf
                <!-- User Name -->
                <x-my.flt_lbl name="username" label="{{ __('نام کاربری') }}" required dir="ltr" maxlength="25"
                              class="tracking-widest font-semibold text-lg!" autofocus
                />

                <!-- Password -->
                <x-my.flt_psw name="password" label="{{ __('کلمه عبور') }}" required dir="ltr" maxlength="30"
                              class="tracking-widest font-semibold text-lg!"
                />

                <flux:card.footer class=" pt-0">
                    <!-- Remember Me -->
                    <div class="flex justify-between mb-5 mx-2">
                        <flux:field variant="inline">
                            <flux:checkbox wire:model="remember" class="cursor-pointer"/>
                            <flux:label class="cursor-pointer">{{__('بخاطرسپاری')}}</flux:label>
                        </flux:field>


                        <flux:link class="text-sm" :href="route('dashboard')" wire:navigate tabindex="-1"
                                   x-data="{ loading: false }"
                                   @click="loading = true"
                        >
                            <span x-show="!loading">{{ __('ریست کلمه عبور') }}</span>
                            <flux:icon.loading x-show="loading" class="inline size-4 text-yellow-500 ml-8"/>

                        </flux:link>
                    </div>


                    <div class="flex items-center justify-end">
                        <flux:button type="submit" variant="filled" color="sky" class="w-full cursor-pointer"
                                     data-test="login-button" x-bind:disabled="loading" :loading="false">
                            <span x-show="!loading">
                                {{ __('ورود') }}
                            </span>
                            <span x-show="loading" class="flex items-center justify-center gap-2">
                                <span>{{ __('منتظر بمانید، در حال پردازش  ... !') }}</span>
                                <flux:icon.loading class="size-5 animate-spin" />
                            </span>
                        </flux:button>
                    </div>




                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-5 flex justify-between">
                        <span class="pt-1.5 mr-2">{{ __('حساب کاربری ندارید؟') }}</span>

                        <flux:button :href="route('register')" wire:navigate variant="ghost" icon:trailing="arrow-up-left"
                                     size="sm" class="cursor-pointer"
                                     x-data="{ loading: false }"
                                     @click="loading = true"
                        >
                            <span x-show="!loading">{{ __('ثبت نام کنید') }} </span>
                            <flux:icon.loading x-show="loading" class="inline size-4 text-yellow-500 ml-5"/>
                        </flux:button>
                    </div>
                </flux:card.footer>
            </form>
        </flux:card.body>
    </flux:card>
</x-layouts::auth>
