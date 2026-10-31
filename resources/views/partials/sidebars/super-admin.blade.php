<flux:sidebar.item icon="squares-2x2" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                   x-data="{ loading: false }"
                   @click="loading = true"
>
    <span>{{ __('داشبرد') }}</span>
    <flux:icon.loading x-show="loading" class="inline absolute left-2 top-2 size-3.5 text-stone-500"/>
</flux:sidebar.item>

