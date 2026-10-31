<flux:sidebar.nav>
    <flux:sidebar.group :heading="__('منوی موسس')" class="grid">
        <flux:sidebar.item
            icon="home"
            :href="route('dashboard')"
            :current="request()->routeIs('dashboard')"
            wire:navigate
        >
            {{ __('داشبورد') }}
        </flux:sidebar.item>
    </flux:sidebar.group>
</flux:sidebar.nav>
