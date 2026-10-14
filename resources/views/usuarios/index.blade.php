<x-layouts.app title="Usuarios — Sistema del Recinto">
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div><h1 class="text-3xl font-black">Usuarios</h1><p class="mt-1 text-slate-500">Administración exclusiva del usuario Root.</p></div>
        <a href="{{ route('usuarios.create') }}" class="rounded-xl bg-sky-600 px-5 py-3 text-center font-bold text-white hover:bg-sky-700">Nuevo usuario</a>
    </div>
    <form class="mb-5 flex gap-2"><input name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre o correo" class="min-h-11 flex-1 rounded-xl border border-slate-300 bg-white px-4"><button class="rounded-xl border border-slate-300 bg-white px-5 font-semibold">Buscar</button></form>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-4">Usuario</th><th class="px-5 py-4">Rol</th><th class="px-5 py-4">Banca</th><th class="px-5 py-4">Estado</th><th class="px-5 py-4 text-right">Acciones</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse ($usuarios as $usuario)
                <tr><td class="px-5 py-4"><strong class="block">{{ $usuario->nombre }}</strong><span class="text-slate-500">{{ $usuario->email }}</span></td><td class="px-5 py-4">{{ $usuario->rol->nombre }}</td><td class="px-5 py-4">{{ $usuario->banca?->nombre ?? '—' }}</td><td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold {{ $usuario->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</span></td><td class="px-5 py-4 text-right"><a href="{{ route('usuarios.edit', $usuario) }}" class="font-bold text-sky-700 hover:underline">Editar</a></td></tr>
            @empty<tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No se encontraron usuarios.</td></tr>@endforelse
        </tbody></table></div>
        <div class="border-t px-5 py-4">{{ $usuarios->links() }}</div>
    </div>
</x-layouts.app>
