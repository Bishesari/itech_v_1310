<flux:card variant="soft" size="lg">
    <flux:card.header>
        <flux:card.heading>{{__('فرم ثبت نام')}}</flux:card.heading>
        <flux:card.subheading>{{__('برای شروع، شماره موبایل خود را وارد کنید.')}}</flux:card.subheading>

        <flux:card.actions>
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-2 w-16 mt-1.5 items-center justify-center rounded-md">
                        <x-logo class="text-zinc-700 dark:text-zinc-300 animate-pulse" />
                    </span>
                <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
            </a>
        </flux:card.actions>
    </flux:card.header>

    <form wire:submit="submit" autocomplete="off">
    <flux:card.body class="space-y-6">

        {{-- موبایل --}}
        <x-my.flt_lbl name="number" label="{{ __('شماره موبایل') }}" required dir="ltr"
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
