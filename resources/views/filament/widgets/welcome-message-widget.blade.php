<x-filament-widgets::widget class="fi-account-widget">
    <x-filament::section>
        <div class="space-y-2">
            <div class="space-y-1">
                <h2 class="welcome-message-heading text-gray-950">
                    {{ $greeting }}, {{ $displayName }}.
                </h2>

                <p class="max-w-3xl text-sm leading-6 text-gray-600">
                    {{ $message }}
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
