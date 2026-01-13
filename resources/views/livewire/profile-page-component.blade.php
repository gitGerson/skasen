<x-filament::section>
    <form wire:submit.prevent="submit" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center justify-center gap-3 pt-4">
            <x-filament::button type="button" color="gray" tag="a" :href="filament()->getCurrentPanel()->getUrl()">
                Kembali
            </x-filament::button>

            <x-filament::button type="submit">
                Simpan
            </x-filament::button>
        </div>
    </form>
</x-filament::section>