<div class="mb-6 flex items-end justify-between gap-4">
    <div><p class="text-sm font-bold uppercase tracking-widest text-sky-700">Configuración</p><h1 class="mt-2 text-3xl font-black">Sesiones parlamentarias</h1><p class="mt-2 text-sm text-slate-500">El cierre definitivo y la reactivación se administran únicamente desde aquí.</p></div>
    <a href="{{ route('configuracion.sesiones.create') }}" class="rounded-xl bg-sky-600 px-5 py-3 font-bold text-white">Nueva sesión</a>
</div>
<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-4">Sesión</th><th class="px-5 py-4">Inicio</th><th class="px-5 py-4">Estado</th><th class="px-5 py-4">Acciones</th></tr></thead>
        <tbody class="divide-y">
            @forelse($sesiones as $sesion)
                <tr>
                    <td class="px-5 py-4"><strong>{{ $sesion->nombre }}</strong><span class="block text-slate-500">{{ $sesion->numero }}</span></td>
                    <td class="px-5 py-4">{{ $sesion->fecha_inicio_programada?->format('d/m/Y H:i') ?? 'Sin definir' }}</td>
                    <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-black uppercase {{ $sesion->estado === 'en_curso' ? 'bg-emerald-100 text-emerald-800' : ($sesion->estado === 'finalizada' ? 'bg-slate-200 text-slate-700' : 'bg-sky-100 text-sky-800') }}">{{ str_replace('_', ' ', $sesion->estado) }}</span></td>
                    <td class="px-5 py-4"><div class="flex items-center justify-end gap-3">
                        <a class="font-bold text-sky-700" href="{{ route('configuracion.sesiones.edit', $sesion) }}">Editar</a>
                        @if($sesion->estado === 'en_curso')
                            <form method="POST" action="{{ route('configuracion.sesiones.finalizar', $sesion) }}" onsubmit="return confirm('¿Finalizar definitivamente esta sesión parlamentaria?')">@csrf<button class="rounded-lg bg-red-600 px-3 py-2 text-xs font-black text-white">Finalizar</button></form>
                        @elseif($sesion->estado === 'finalizada')
                            <form method="POST" action="{{ route('configuracion.sesiones.reactivar', $sesion) }}" onsubmit="return confirm('¿Reactivar esta sesión finalizada?')">@csrf<button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-black text-white">Reactivar</button></form>
                        @endif
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-10 text-center text-slate-500">No hay sesiones configuradas.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="border-t p-5">{{ $sesiones->links() }}</div>
</div>
