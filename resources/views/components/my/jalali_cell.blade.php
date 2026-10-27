@props(['date'=>''])

<div class="leading-tight">
    <div>{{ explode(' ', $date)[0] }}</div>
    <div class="text-xs">
        {{ substr($date, 11, 5) }}
        <span class="text-stone-500">{{ substr($date, 17) }}</span>
    </div>
</div>
