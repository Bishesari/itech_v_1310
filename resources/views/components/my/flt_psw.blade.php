@props([
    'name' => 'password',
    'label' => 'کلمه عبور',
    'autocomplete' => 'current-password',
])

@php
    $inputId = $name;
    $errorId = $inputId . '-error';
    $hasError = $errors->has($name);
@endphp

<div
    class="relative"
    x-data="{ showPassword: false }"
>
    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        x-bind:type="showPassword ? 'text' : 'password'"
        wire:model="{{ $name }}"
        dir="ltr"
        autocomplete="{{ $autocomplete }}"
        placeholder=" "
        required
        @if ($hasError)
            aria-invalid="true"
        aria-describedby="{{ $errorId }}"
        @endif
        {{ $attributes->merge([
            'class' => 'peer w-full rounded-lg border-2
                border-stone-300 dark:border-stone-700
                bg-transparent px-10 py-3
                text-center text-stone-700 dark:text-stone-300
                focus:text-blue-600 dark:focus:text-blue-400
                placeholder-transparent
                transition-colors duration-200
                focus:outline-none focus:border-blue-600
                dark:focus:border-blue-400',
        ]) }}
    />

    <label
        for="{{ $inputId }}"
        class="pointer-events-none absolute right-3 top-3.5
            px-2 text-base text-stone-500
            transition-all duration-150
            peer-focus:-top-2.5 peer-focus:right-2.5
            peer-focus:text-sm peer-focus:text-blue-600
            peer-focus:bg-white dark:peer-focus:bg-stone-900
            dark:peer-focus:text-blue-400
            peer-[:not(:placeholder-shown)]:-top-2.5
            peer-[:not(:placeholder-shown)]:right-2.5
            peer-[:not(:placeholder-shown)]:text-sm
            peer-[:not(:placeholder-shown)]:bg-white
            dark:peer-[:not(:placeholder-shown)]:bg-stone-900"
    >
        {{ $label }}
    </label>

    <button
        type="button"
        @click="showPassword = !showPassword"
        :aria-label="showPassword ? 'مخفی کردن کلمه عبور' : 'نمایش کلمه عبور'"
        class="absolute left-3 top-1/2 -translate-y-1/2
            text-stone-500 hover:text-blue-600
            dark:text-stone-400 dark:hover:text-blue-400"
    >
        <flux:icon.eye x-show="!showPassword" class="size-5"/>
        <flux:icon.eye-slash x-show="showPassword" class="size-5"/>

    </button>

    @if ($hasError)
        <div
            id="{{ $errorId }}"
            class="mt-1 text-center font-light! text-red-600 dark:text-red-400"
        >
            <small>{{ $errors->first($name) }}</small>
        </div>
    @endif
</div>
