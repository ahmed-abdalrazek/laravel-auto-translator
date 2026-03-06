@extends('aar-translator::dashboard.layout')

@section('content')
<div x-data="keysPage()" x-init="init()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Translation Keys</h1>
            <p class="mt-1 text-sm text-gray-500">Browse, search, and edit all translation strings.</p>
        </div>
        <a href="{{ route('aar-translator.index') }}"
           class="text-sm text-indigo-600 hover:text-indigo-800 transition">← Dashboard</a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 mb-5">
        <form method="GET" action="{{ route('aar-translator.keys') }}" class="flex flex-wrap items-center gap-3">
            {{-- Search --}}
            <div class="relative flex-1 min-w-48">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search keys..."
                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none">
                <svg class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
            </div>

            {{-- Group Filter --}}
            <select name="group" class="border border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-2 focus:ring-indigo-400 outline-none">
                <option value="">All Groups</option>
                @foreach($groups as $g)
                <option value="{{ $g }}" @selected(request('group') === $g)>{{ $g }}</option>
                @endforeach
            </select>

            {{-- Missing Filter --}}
            <label class="flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" name="missing" value="1" @checked(request('missing'))
                       class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                Missing translations
            </label>

            {{-- Dead Filter --}}
            <label class="flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" name="dead" value="1" @checked(request('dead'))
                       class="w-4 h-4 text-red-500 rounded border-gray-300">
                Dead keys only
            </label>

            <button type="submit"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                Filter
            </button>
            @if(request()->hasAny(['search','group','missing','dead']))
            <a href="{{ route('aar-translator.keys') }}" class="text-sm text-gray-500 hover:text-gray-700 transition">Clear</a>
            @endif
        </form>
    </div>

    {{-- Keys Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left px-4 py-3 font-semibold text-gray-600 w-36">Group</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">Key</th>
                        @foreach($locales as $locale)
                        <th class="text-left px-4 py-3 font-semibold text-gray-600">
                            <span class="inline-block bg-indigo-100 text-indigo-700 text-xs px-2 py-0.5 rounded uppercase">{{ $locale }}</span>
                        </th>
                        @endforeach
                        <th class="text-right px-4 py-3 font-semibold text-gray-600 w-24">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($keys as $key)
                    <tr class="hover:bg-gray-50 transition {{ $key->is_dead ? 'opacity-50' : '' }}"
                        x-data="keyRow({{ $key->id }})">
                        <td class="px-4 py-3">
                            <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded font-mono max-w-[120px] truncate">
                                {{ $key->group }}
                            </span>
                            @if($key->is_dead)
                            <span class="ml-1 inline-block bg-red-100 text-red-600 text-xs px-1.5 py-0.5 rounded">dead</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 max-w-xs truncate">{{ $key->key }}</td>

                        @foreach($locales as $locale)
                        @php $value = $key->values->firstWhere('locale', $locale) @endphp
                        <td class="px-4 py-3 max-w-xs">
                            <div x-data="{
                                editing: false,
                                val: {{ json_encode($value?->value ?? '') }},
                                saving: false,
                                valueId: {{ $value?->id ?? 'null' }},
                                async save() {
                                    if (!this.valueId) {
                                        rzTranslator.notify('No value record – refresh and try again.', 'error');
                                        return;
                                    }
                                    this.saving = true;
                                    const res = await rzTranslator.put(
                                        '{{ url(config('aar-translator.dashboard.path', 'admin/translations') . '/values') }}/' + this.valueId,
                                        { value: this.val }
                                    );
                                    this.saving = false;
                                    if (res.success) {
                                        this.editing = false;
                                        rzTranslator.notify('Saved!');
                                    } else {
                                        rzTranslator.notify('Failed to save', 'error');
                                    }
                                }
                            }">
                                <span x-show="!editing" @dblclick="editing = true"
                                      :class="val === '' || val === null ? 'text-red-400 italic' : 'text-gray-800'"
                                      class="block truncate cursor-pointer text-xs"
                                      :title="val || 'Missing – double-click to add'">
                                    <span x-text="val || 'Missing'"></span>
                                </span>
                                <div x-show="editing" x-cloak class="flex items-center gap-1">
                                    <input type="text" x-model="val"
                                           @keyup.enter="save()"
                                           @keyup.escape="editing = false"
                                           class="border border-indigo-300 rounded px-2 py-0.5 text-xs w-full focus:ring-1 focus:ring-indigo-400 outline-none">
                                    <button @click="save()" :disabled="saving"
                                            class="text-emerald-600 hover:text-emerald-800 shrink-0 disabled:opacity-50">✓</button>
                                    <button @click="editing = false"
                                            class="text-gray-400 hover:text-gray-600 shrink-0">✕</button>
                                </div>

                                @if($value?->is_auto_translated)
                                <span class="inline-block mt-0.5 bg-blue-50 text-blue-500 text-[10px] px-1 rounded">auto</span>
                                @endif
                            </div>
                        </td>
                        @endforeach

                        <td class="px-4 py-3 text-right">
                            <button @click="deleteKey()"
                                    class="text-red-400 hover:text-red-600 transition text-xs">
                                Delete
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 3 + count($locales) }}" class="text-center py-12 text-gray-400">
                            No translation keys found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($keys->hasPages())
        <div class="border-t border-gray-100 px-4 py-3">
            {{ $keys->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function keyRow(keyId) {
    return {
        async deleteKey() {
            if (!confirm('Delete this key and all its translations?')) return;
            const res = await rzTranslator.del(`{{ url(config('aar-translator.dashboard.path', 'admin/translations') . '/keys') }}/${keyId}`);
            if (res.success) {
                rzTranslator.notify('Key deleted');
                this.$el.remove();
            } else {
                rzTranslator.notify('Failed to delete key', 'error');
            }
        },
    };
}
</script>
@endpush
