<x-layouts.app title="Ingresar — Sistema del Recinto">
    <div class="mx-auto mt-8 max-w-md rounded-2xl bg-white p-8 shadow-xl shadow-slate-200/70">
        <div class="mb-8 text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-sky-600 text-xl font-black text-white">PLF</span>
            <h1 class="mt-5 text-2xl font-bold">Acceso al recinto</h1>
            <p class="mt-2 text-sm text-slate-500">Ingrese con las credenciales asignadas por el administrador.</p>
        </div>
        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf
            <div><label for="email" class="mb-2 block text-sm font-semibold">Correo electrónico</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100"></div>
            <div><label for="password" class="mb-2 block text-sm font-semibold">Contraseña</label><input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100"></div>
            <label class="flex items-center gap-3 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" class="size-5 rounded border-slate-300 text-sky-600"> Mantener la sesión iniciada</label>
            @if ($errors->any())<div class="rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>@endif
            <button class="min-h-12 w-full rounded-xl bg-sky-600 px-5 py-3 font-bold text-white hover:bg-sky-700 focus:ring-4 focus:ring-sky-200">Ingresar</button>
        </form>
    </div>
</x-layouts.app>
