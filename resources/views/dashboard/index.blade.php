@extends('aar-translator::dashboard.layout')

@section('content')
<div x-data="dashboard()" x-init="init()">

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Translation Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Manage, translate, and track all your application strings.</p>
        </div>
        <div class="flex gap-3">
            <button @click="scan()" :disabled="scanning"
                    class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 transition">
                <svg class="w-4 h-4" :class="{ 'animate-spin': scanning }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="scanning ? 'Scanning…' : 'Scan Project'"></span>
            </button>
            <button @click="translateMissing()" :disabled="translating"
                    class="inline-flex items-center gap-2 bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 disabled:opacity-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span x-text="translating ? 'Translating…' : 'Auto-Translate'"></span>
            </button>
            <button @click="deleteDeadKeys()" :disabled="cleaning"
                    class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 disabled:opacity-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <span x-text="cleaning ? 'Cleaning…' : 'Clean Dead Keys'"></span>
            </button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <p class="text-sm text-gray-500 mb-1">Total Keys</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_keys']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <p class="text-sm text-gray-500 mb-1">Dead Keys</p>
            <p class="text-3xl font-bold text-red-600">{{ number_format($stats['dead_keys']) }}</p>
        </div>
        @php $missingTotal = collect($stats['locales'])->sum('missing') @endphp
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <p class="text-sm text-gray-500 mb-1">Missing Translations</p>
            <p class="text-3xl font-bold text-amber-500">{{ number_format($missingTotal) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <p class="text-sm text-gray-500 mb-1">Supported Locales</p>
            <p class="text-3xl font-bold text-indigo-600">{{ count($stats['locales']) }}</p>
        </div>
    </div>

    {{-- Locale Progress --}}
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-8">
        <h2 class="text-lg font-semibold text-gray-800 mb-5">Translation Completion by Locale</h2>
        <div class="space-y-4">
            @foreach($stats['locales'] as $locale => $data)
            <div>
                <div class="flex justify-between items-center mb-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-10 text-center text-xs font-bold uppercase bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">{{ $locale }}</span>
                        <span class="text-sm text-gray-600">{{ $data['translated'] }} / {{ $data['total'] }}</span>
                    </div>
                    <span class="text-sm font-semibold {{ $data['completion'] == 100 ? 'text-emerald-600' : ($data['completion'] > 70 ? 'text-amber-500' : 'text-red-500') }}">
                        {{ $data['completion'] }}%
                    </span>
                </div>
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full progress-bar rounded-full {{ $data['completion'] == 100 ? 'bg-emerald-500' : ($data['completion'] > 70 ? 'bg-amber-400' : 'bg-red-500') }}"
                         style="width: {{ $data['completion'] }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Import/Export --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Export --}}
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Export Translations</h2>
            <div class="flex flex-wrap gap-3">
                @foreach(['json', 'csv', 'zip'] as $fmt)
                <a href="{{ route('aar-translator.export', ['format' => $fmt]) }}"
                   class="inline-flex items-center gap-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    {{ strtoupper($fmt) }}
                </a>
                @endforeach
            </div>
        </div>

        {{-- Import --}}
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Import Translations</h2>
            <form @submit.prevent="importFile($event)" enctype="multipart/form-data" class="flex items-center gap-3">
                @csrf
                <input type="file" name="file" accept=".json,.csv" required
                       class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                <button type="submit"
                        class="shrink-0 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                    Import
                </button>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function dashboard() {
    return {
        scanning: false,
        translating: false,
        cleaning: false,

        init() {},

        async scan() {
            this.scanning = true;
            try {
                const res = await rzTranslator.post('{{ route('aar-translator.scan') }}');
                if (res.success) {
                    rzTranslator.notify(res.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    rzTranslator.notify(res.message ?? 'Scan failed', 'error');
                }
            } catch(e) {
                rzTranslator.notify('Scan failed. Check the console.', 'error');
            } finally {
                this.scanning = false;
            }
        },

        async translateMissing() {
            this.translating = true;
            try {
                const res = await rzTranslator.post('{{ route('aar-translator.translate.missing') }}');
                if (res.success) {
                    rzTranslator.notify(res.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    rzTranslator.notify(res.message ?? 'Translation failed', 'error');
                }
            } catch(e) {
                rzTranslator.notify('Translation failed. Check your provider settings.', 'error');
            } finally {
                this.translating = false;
            }
        },

        async deleteDeadKeys() {
            if (!confirm('Are you sure you want to delete all dead keys? This cannot be undone.')) return;
            this.cleaning = true;
            try {
                const res = await rzTranslator.del('{{ route('aar-translator.keys.dead.destroy') }}');
                if (res.success) {
                    rzTranslator.notify(res.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    rzTranslator.notify('Failed to clean dead keys', 'error');
                }
            } catch(e) {
                rzTranslator.notify('Failed to clean dead keys', 'error');
            } finally {
                this.cleaning = false;
            }
        },

        async importFile(event) {
            const form = event.target;
            const formData = new FormData(form);
            try {
                const res = await fetch('{{ route('aar-translator.import') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': rzTranslator.csrfToken, 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await res.json();
                if (data.success) {
                    rzTranslator.notify(data.message);
                    form.reset();
                } else {
                    rzTranslator.notify(data.message ?? 'Import failed', 'error');
                }
            } catch(e) {
                rzTranslator.notify('Import failed.', 'error');
            }
        },
    };
}
</script>
@endpush
