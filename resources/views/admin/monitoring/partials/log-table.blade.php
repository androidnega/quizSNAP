@if(isset($rows))
<div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50"><tr>
            @foreach($columns as $col)
                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ $col['label'] }}</th>
            @endforeach
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    @foreach($columns as $col)
                        <td class="px-4 py-3 text-gray-700">{{ data_get($row, $col['key']) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}" class="px-4 py-8 text-center text-gray-500">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if(method_exists($rows, 'links'))
<div class="mt-4">{{ $rows->links() }}</div>
@endif
@endif
