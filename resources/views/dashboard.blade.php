<x-layouts.app title="Inicio — Sistema del Recinto">
    <section class="rounded-2xl bg-gradient-to-br from-slate-950 to-slate-800 p-8 text-white shadow-xl">
        <p class="text-sm font-bold uppercase tracking-widest text-sky-400">Panel principal</p>
        <h1 class="mt-3 text-3xl font-black">Bienvenido, {{ auth()->user()->nombre }}</h1>
        <p class="mt-3 max-w-2xl text-slate-300">La base del sistema está operativa. Los módulos del recinto se habilitarán en las próximas etapas.</p>
    </section>
    @can('viewAny', App\Models\User::class)
        <a href="{{ route('usuarios.index') }}" class="mt-6 flex max-w-md items-center justify-between rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:ring-sky-400"><span><strong class="block text-lg">Administrar usuarios</strong><small class="text-slate-500">Roles, estados y bancas</small></span><span class="text-2xl text-sky-600">→</span></a>
    @endcan
</x-layouts.app>
