<x-layouts.app title="Cargar PDF — Sistema del Recinto">
    <div class="mx-auto max-w-2xl">
        <div class="mb-6"><p class="text-sm font-bold uppercase tracking-widest text-sky-700">Configuración</p><h1 class="mt-2 text-3xl font-black">Cargar documento PDF</h1><p class="mt-2 text-slate-500">El archivo se guardará fuera del directorio público.</p></div>
        <form method="POST" action="{{ route('configuracion.documentos.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-2xl bg-white p-7 shadow-sm ring-1 ring-slate-200">
            @csrf
            @if ($errors->any())<div class="rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>@endif
            <div><label for="titulo" class="mb-2 block text-sm font-bold">Título</label><input id="titulo" name="titulo" value="{{ old('titulo') }}" required class="w-full rounded-xl border border-slate-300 px-4 py-3"></div>
            <div><label for="archivo" class="mb-2 block text-sm font-bold">Archivo PDF</label><input id="archivo" name="archivo" type="file" accept="application/pdf,.pdf" required class="w-full rounded-xl border border-dashed border-slate-400 bg-slate-50 p-5 file:mr-4 file:rounded-lg file:border-0 file:bg-sky-600 file:px-4 file:py-2 file:font-bold file:text-white"><p class="mt-2 text-xs text-slate-500">Máximo {{ round(config('recinto.documentos.tamano_maximo_kb') / 1024) }} MB. Se valida extensión y contenido MIME.</p></div>
            <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:justify-end"><a href="{{ route('configuracion.documentos.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-center font-bold">Cancelar</a><button class="rounded-xl bg-sky-600 px-6 py-3 font-bold text-white">Guardar PDF</button></div>
        </form>
    </div>
</x-layouts.app>
