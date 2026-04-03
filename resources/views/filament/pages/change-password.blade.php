<x-filament::page>
    <x-filament::section>
        <form wire:submit="submit" class="space-y-6">
            {{ $this->form }}

            <div class="flex items-center justify-center gap-3 pt-4">
                <x-filament::button
                    type="button"
                    color="gray"
                    tag="a"
                    :href="\Jeffgreco13\FilamentBreezy\Pages\MyProfilePage::getUrl(panel: 'admin')"
                >
                    Kembali
                </x-filament::button>

                <x-filament::button type="submit">
                    Simpan
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament::page>
