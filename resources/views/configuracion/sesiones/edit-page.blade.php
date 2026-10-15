<x-layouts.app title="Editar sesión — Configuración">
    <div class="mx-auto max-w-5xl">
        <div class="mb-6">
            <p class="text-sm font-bold uppercase tracking-widest text-sky-700">Configuración</p>
            <h1 class="mt-2 text-3xl font-black">Editar sesión parlamentaria</h1>
        </div>
        <form method="POST" action="{{ route('configuracion.sesiones.update', $sesion) }}" class="rounded-2xl bg-white p-7 shadow-sm ring-1 ring-slate-200">
            @csrf
            @method('PUT')
            @include('configuracion.sesiones._form', ['boton' => 'Guardar cambios'])
        </form>
        @include('configuracion.sesiones.votaciones')
    </div>
</x-layouts.app>
