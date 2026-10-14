<x-layouts.app title="Nuevo usuario — Sistema del Recinto">
    <div class="mx-auto max-w-4xl"><div class="mb-6"><h1 class="text-3xl font-black">Nuevo usuario</h1><p class="mt-1 text-slate-500">Asigne el rol y, si corresponde, una banca disponible.</p></div><form method="POST" action="{{ route('usuarios.store') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">@csrf @include('usuarios._form', ['boton' => 'Crear usuario'])</form></div>
</x-layouts.app>
