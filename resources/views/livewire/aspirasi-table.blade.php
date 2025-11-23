<div wire:poll.5s class="max-w-5xl mx-auto mt-8">
    <div class="bg-white shadow rounded overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tujuan</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Anonymous</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($aspirasis as $aspirasi)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $aspirasi->id }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $aspirasi->user->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $aspirasi->tujuan->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $aspirasi->kategori->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $aspirasi->keterangan }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $aspirasi->status }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $aspirasi->is_anonymous ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $aspirasi->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-4 text-center text-sm text-gray-500">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
