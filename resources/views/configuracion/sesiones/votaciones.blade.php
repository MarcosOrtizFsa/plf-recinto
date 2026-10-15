<section class="mt-7 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
    <header class="border-b border-slate-300 bg-slate-200 px-6 py-4">
        <h2 class="text-xl font-black text-slate-700">Configuración de votación</h2>
        <p class="mt-1 text-sm text-slate-500">En esta etapa todas las votaciones son EN GENERAL y corresponden a un único artículo.</p>
    </header>

    <form method="POST" action="{{ route('configuracion.sesiones.votaciones.store', $sesion) }}" class="grid items-end gap-4 p-6 md:grid-cols-[1fr_220px_auto]">
        @csrf
        <div>
            <label for="tema_votacion" class="mb-2 block text-sm font-bold">Tema a votar</label>
            <input id="tema_votacion" name="tema" value="{{ old('tema') }}" required maxlength="255" placeholder="Ingrese el tema de la votación" class="w-full rounded-xl border border-slate-300 px-4 py-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-bold">Cantidad de artículos</label>
            <div class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 font-black text-slate-700">EN GENERAL · 1 artículo</div>
        </div>
        <button class="rounded-xl bg-sky-600 px-6 py-3 font-black text-white hover:bg-sky-500">Agregar voto</button>
    </form>

    <div class="border-t border-slate-200">
        @forelse($sesion->votaciones as $votacion)
            <article class="grid items-center gap-4 border-b border-slate-200 px-6 py-4 last:border-0 md:grid-cols-[1fr_220px_auto]">
                <div><small class="font-bold uppercase tracking-wider text-slate-400">Tema a votar</small><strong class="mt-1 block text-slate-800">{{ $votacion->tema }}</strong></div>
                <div><small class="font-bold uppercase tracking-wider text-slate-400">Tipo</small><strong class="mt-1 block text-sky-700">EN GENERAL · {{ $votacion->cantidad_articulos }} artículo</strong></div>
                @if($votacion->estado === 'configurada')
                    <form method="POST" action="{{ route('configuracion.sesiones.votaciones.destroy', [$sesion, $votacion]) }}" onsubmit="return confirm('¿Eliminar esta configuración de votación?')">@csrf @method('DELETE')<button class="grid size-11 place-items-center rounded-xl border border-red-200 text-2xl text-red-600 hover:bg-red-50" aria-label="Eliminar votación">×</button></form>
                @else
                    <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-black uppercase text-slate-500">{{ $votacion->estado }}</span>
                @endif
            </article>
        @empty
            <p class="p-6 text-center text-sm text-slate-500">Todavía no hay votaciones configuradas para esta sesión.</p>
        @endforelse
    </div>
</section>
