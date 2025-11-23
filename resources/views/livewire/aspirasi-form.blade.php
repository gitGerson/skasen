<div wire:poll.5s class="max-w-2xl mx-auto p-6 bg-white rounded shadow">
    <form wire:submit="submit" class="space-y-6">
        <div class="flex space-x-4">
            <div class="w-1/3">
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <select id="user_id" wire:model="user_id"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select User</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('user_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="w-1/3">
                <label for="tujuan_id" class="block text-sm font-medium text-gray-700 mb-1">Tujuan</label>
                <select id="tujuan_id" wire:model="tujuan_id"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select Tujuan</option>
                    @foreach($tujuans as $tujuan)
                        <option value="{{ $tujuan->id }}">{{ $tujuan->name }}</option>
                    @endforeach
                </select>
                @error('tujuan_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="w-1/3">
                <label for="kategori_id" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                <select id="kategori_id" wire:model="kategori_id"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select Kategori</option>
                    @foreach($kategoris as $kategori)
                        <option value="{{ $kategori->id }}">{{ $kategori->name }}</option>
                    @endforeach
                </select>
                @error('kategori_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div>
            <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
            <textarea id="keterangan" wire:model="keterangan"
                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
            @error('keterangan') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="image_path" class="block text-sm font-medium text-gray-700 mb-1">Image Path</label>
            <input type="text" id="image_path" wire:model="image_path"
                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            @error('image_path') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center">
            <label class="inline-flex items-center">
                <input type="checkbox" wire:model="is_anonymous" value="1" class="form-checkbox text-blue-600">
                <span class="ml-2 text-sm text-gray-700">Anonymous</span>
            </label>
            @error('is_anonymous') <span class="text-red-500 text-xs ml-2">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <input type="text" id="status" wire:model="status"
                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            @error('status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <button wire:click="submit" wire:confirm="Are you sure you want to create this post?" type="submit" class="w-full py-2 px-4 bg-blue-600 text-white rounded-md hover:bg-blue-700">Submit
            <div wire:loading wire:target="submit">
                <img src="/spinner.svg" alt="Loading..." class="inline w-5 h-5 ml-2">
            </div>
        </button>
    </form>
</div>