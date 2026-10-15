<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sistema del Recinto' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased" data-pantalla-recinto="{{ request()->routeIs('comando.*', 'bancas.*', 'monitor.*') ? '1' : '0' }}" data-autenticado="{{ auth()->check() ? '1' : '0' }}" x-data="{ reautenticar: false }" @recinto-reauth.window="reautenticar = true">
    @unless(request()->routeIs('comando.*', 'bancas.*', 'monitor.*'))
    <header class="border-b border-slate-800 bg-slate-950 text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <span class="grid size-11 place-items-center rounded-xl bg-sky-500 text-lg font-black">PLF</span>
                <span><strong class="block">Sistema del Recinto</strong><small class="text-slate-400">Cámara de Diputados</small></span>
            </a>
            @auth
                <div class="flex items-center gap-4">
                    <div class="hidden text-right sm:block"><span class="block text-sm font-semibold">{{ auth()->user()->nombre }}</span><span class="text-xs text-slate-400">{{ auth()->user()->rol->nombre }}</span></div>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-slate-700 px-4 py-2 text-sm font-semibold hover:bg-slate-800">Salir</button></form>
                </div>
            @endauth
        </div>
    </header>

    @auth
        <nav class="border-b bg-white">
            <div class="mx-auto flex max-w-7xl gap-2 px-4 py-3 sm:px-6">
                <a href="{{ route('dashboard') }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('dashboard') ? 'bg-sky-100 text-sky-800' : 'text-slate-600 hover:bg-slate-100' }}">Inicio</a>
                @can('viewAny', App\Models\User::class)
                    <a href="{{ route('usuarios.index') }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('usuarios.*') ? 'bg-sky-100 text-sky-800' : 'text-slate-600 hover:bg-slate-100' }}">Usuarios</a>
                @endcan
                @can('viewAny', App\Models\Documento::class)
                    <a href="{{ route('configuracion.documentos.index') }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('configuracion.*') ? 'bg-sky-100 text-sky-800' : 'text-slate-600 hover:bg-slate-100' }}">Configuración</a>
                @endcan
                @if(auth()->user()->puedeConfigurarRecinto())
                    <a href="{{ route('comando.index') }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('comando.*') ? 'bg-sky-100 text-sky-800' : 'text-slate-600 hover:bg-slate-100' }}">Comando</a>
                @elseif(auth()->user()->banca_id)
                    <a href="{{ route('bancas.show', auth()->user()->banca_id) }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('bancas.*') ? 'bg-sky-100 text-sky-800' : 'text-slate-600 hover:bg-slate-100' }}">Mi banca</a>
                @endif
            </div>
        </nav>
    @endauth
    @endunless

    <main class="{{ request()->routeIs('comando.*', 'bancas.*', 'monitor.*') ? '' : 'mx-auto max-w-7xl px-4 py-8 sm:px-6' }}">
        @if (session('estado'))<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{{ session('estado') }}</div>@endif
        {{ $slot }}
    </main>
    <div x-cloak x-show="reautenticar" class="fixed inset-0 z-50 grid place-items-center bg-slate-950/80 p-6"><div class="max-w-md rounded-2xl bg-white p-7 text-center shadow-2xl"><h2 class="text-xl font-black">Es necesario reautenticar</h2><p class="mt-3 text-slate-600">La información visible se conservará. Abra el acceso en otra pestaña y luego reintente.</p><a href="{{ route('login') }}" target="_blank" class="mt-5 inline-block rounded-xl bg-sky-600 px-5 py-3 font-bold text-white">Abrir acceso</a><button @click="reautenticar = false; window.dispatchEvent(new CustomEvent('recinto-resync'))" class="mt-3 block w-full rounded-xl border px-5 py-3 font-bold">Reintentar</button></div></div>
</body>
</html>
