<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" dir="rtl">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">

        <div class="bg-background flex min-h-svh flex-col items-center md:justify-center gap-6 p-5">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                        <span class="flex h-2 w-20 -mb-3 items-center justify-center rounded-md">
                            <x-logo class="text-zinc-700 dark:text-zinc-300 animate-pulse" />
                        </span>
                <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
            </a>
            {{ $slot }}
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
