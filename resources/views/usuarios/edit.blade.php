<x-layouts.app title="Editar usuario — Sistema del Recinto">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6"><h1 class="text-3xl font-black">Editar usuario</h1><p class="mt-1 text-slate-500">{{ $usuario->nombre }} · {{ $usuario->email }}</p></div>
        <form method="POST" action="{{ route('usuarios.update', $usuario) }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">@csrf @method('PUT') @include('usuarios._form', ['boton' => 'Guardar cambios'])</form>
        @can('delete', $usuario)<form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-5" onsubmit="return confirm('¿Desactivar este usuario?')">@csrf @method('DELETE')<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"><div><strong class="text-amber-900">Desactivar acceso</strong><p class="text-sm text-amber-700">El historial legislativo se conservará.</p></div><button class="rounded-xl bg-amber-700 px-5 py-3 font-bold text-white">Desactivar usuario</button></div></form>@endcan
    </div>
</x-layouts.app>
