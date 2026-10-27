@props([
    'name' => '',
    'label' => '',
    'alpineLabel' => null,
    'type' => 'text',
    'id' => null,
    'model' => null,
    'modelModifier' => '',
    'readonly' => false,
    'disabled' => false,
    'required' => false,
    'autocomplete' => 'off',
    'dir' => 'rtl',
])

@php
    $inputId = $id ?? $name;
    $errorId = $inputId . '-error';
    $hasError = $errors->has($name);
@endphp

<div class="relative">
    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        wire:model{{ $modelModifier !== '' ? '.' . $modelModifier : '' }}="{{ $model ?? $name }}"
        dir="{{ $dir }}"
        autocomplete="{{ $autocomplete }}"
        placeholder=" "
        @readonly($readonly)
        @disabled($disabled)
        @required($required)
        @if ($hasError)
            aria-invalid="true" aria-describedby="{{ $errorId }}"
        @endif
        {{ $attributes->merge([
    'class' => 'peer w-full rounded-lg border-2
        border-stone-300 dark:border-stone-700
        bg-transparent
        px-3 py-3
        text-center text-stone-700 dark:text-stone-300
        focus:text-blue-600 dark:focus:text-blue-400
        placeholder-transparent
        transition-colors duration-200
        focus:outline-none focus:border-blue-600
        dark:focus:border-blue-400
        disabled:cursor-not-allowed
        disabled:opacity-60',
        ])
         }}
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
        @if ($alpineLabel)
            <span x-text="{{ $alpineLabel }}"></span>
        @else
            {{ $label }}
        @endif
    </label>

    @if ($hasError)

        <div
            id="{{ $errorId }}"
            class="mt-1 text-center font-light! text-red-600 dark:text-red-400"
        >
            <small>{{ $errors->first($name) }}</small>

        </div>
    @endif
</div>
