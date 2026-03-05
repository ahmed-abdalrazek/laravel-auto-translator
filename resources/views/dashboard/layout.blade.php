<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Translations Manager – rz/laravel-auto-translator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .progress-bar { transition: width 0.4s ease; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

{{-- Navbar --}}
<nav class="bg-indigo-700 text-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
        <div class="flex items-center gap-3">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
            </svg>
            <span class="font-bold text-lg tracking-wide">Translation Manager</span>
        </div>
        <div class="flex items-center gap-4 text-sm">
            <a href="{{ route('rz-translator.index') }}" class="hover:text-indigo-200 transition">Dashboard</a>
            <a href="{{ route('rz-translator.keys') }}" class="hover:text-indigo-200 transition">Keys</a>
            <a href="{{ route('rz-translator.export') }}" class="hover:text-indigo-200 transition">Export</a>
        </div>
    </div>
</nav>

{{-- Page Content --}}
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @yield('content')
</main>

{{-- Toast Notification --}}
<div x-data="{ show: false, message: '', type: 'success' }"
     x-on:notify.window="show = true; message = $event.detail.message; type = $event.detail.type ?? 'success'; setTimeout(() => show = false, 3500)"
     x-show="show"
     x-transition
     x-cloak
     :class="type === 'error' ? 'bg-red-600' : 'bg-green-600'"
     class="fixed bottom-6 right-6 text-white px-5 py-3 rounded-lg shadow-lg text-sm z-50">
    <span x-text="message"></span>
</div>

<script>
    window.rzTranslator = {
        csrfToken: document.querySelector('meta[name=csrf-token]').getAttribute('content'),
        notify(message, type = 'success') {
            window.dispatchEvent(new CustomEvent('notify', { detail: { message, type } }));
        },
        async post(url, data = {}) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });
            return res.json();
        },
        async del(url) {
            const res = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
            });
            return res.json();
        },
        async put(url, data = {}) {
            const res = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });
            return res.json();
        },
    };
</script>

@stack('scripts')
</body>
</html>
