<x-layouts.app title="Banca {{ $banca->numero }} — Sistema del Recinto">
    <div x-data="bancaTactil()" x-init="cargar()" @recinto-resync.window="cargar()" class="fixed inset-0 z-40 flex flex-col overflow-hidden bg-slate-100">
        <header class="flex items-center justify-between bg-slate-950 px-7 py-5 text-white">
            <div><p class="text-sm font-bold uppercase tracking-widest text-sky-400">Banca {{ $banca->numero }}</p><h1 class="mt-1 text-3xl font-black">{{ $legislador->nombre }}</h1></div>
            <div class="max-w-xl text-right"><p class="text-xs font-bold uppercase tracking-widest text-slate-400">Sesión parlamentaria</p><strong class="mt-1 block text-xl" x-text="estado.sesion?.nombre ?? 'Sin sesión asignada'"></strong><small class="text-slate-400" x-text="estado.sesion?.numero ?? ''"></small></div>
        </header>

        <main class="min-h-0 flex-1 p-6">
            <section x-cloak x-show="estado.votacion" class="mx-auto flex h-full max-w-6xl flex-col rounded-3xl bg-white p-7 shadow-2xl ring-2 ring-sky-500">
                <div class="text-center"><p class="text-sm font-black uppercase tracking-[.3em] text-sky-700">Votación directa · En general</p><h2 class="mt-3 text-3xl font-black" x-text="estado.votacion?.tema"></h2><p class="mt-3 text-xl font-bold tabular-nums text-slate-500" x-text="restante"></p></div>

                <div x-show="!estado.votacion?.voto || cambiando" class="mt-8 grid min-h-0 flex-1 gap-5 md:grid-cols-3">
                    <template x-for="opcion in opciones"><button @click="votar(opcion.valor)" :disabled="enviando || !estado.habilitada" class="min-h-40 rounded-3xl text-2xl font-black text-white shadow-xl transition active:scale-95 disabled:opacity-40" :class="opcion.clase" x-text="opcion.texto"></button></template>
                </div>

                <div x-show="estado.votacion?.voto && !cambiando" class="grid flex-1 place-items-center text-center">
                    <div><p class="text-lg font-bold uppercase tracking-widest text-slate-500">Voto registrado</p><strong class="mt-4 block text-5xl font-black uppercase" :class="colorVoto(estado.votacion?.voto)" x-text="textoVoto(estado.votacion?.voto)"></strong><p class="mt-5 text-slate-500">Espere la finalización de la votación.</p><button @click="cambiando = true" class="mt-8 rounded-2xl border-2 border-sky-600 px-10 py-4 text-xl font-black text-sky-700">Cambiar voto</button></div>
                </div>
            </section>

            <section x-cloak x-show="!estado.votacion && estado.documento" class="h-full overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"><div class="flex items-center justify-between p-4"><strong x-text="estado.documento?.titulo"></strong><a :href="estado.documento?.url" target="_blank" class="rounded-lg bg-slate-100 px-4 py-2 font-bold">Abrir</a></div><iframe :src="estado.documento?.url" class="w-full border-t" style="height: calc(100% - 64px)" title="Documento de la sesión"></iframe></section>
            <section x-cloak x-show="!estado.votacion && !estado.documento" class="grid h-full place-items-center text-center"><div><h2 class="text-3xl font-black">{{ $legislador->nombre }}</h2><p class="mt-2 text-xl text-slate-500">Banca {{ $banca->numero }}</p><p class="mt-4 text-lg font-bold text-slate-500" x-text="estado.sesion?.nombre ?? ''"></p></div></section>
        </main>
    </div>
    <script>
        function bancaTactil() {
            return {
                estado: {}, enviando: false, cambiando: false, restante: '',
                opciones: [{ valor: 'afirmativo', texto: 'AFIRMATIVO', clase: 'bg-emerald-600' }, { valor: 'negativo', texto: 'NEGATIVO', clase: 'bg-red-600' }, { valor: 'abstencion', texto: 'ABSTENCIÓN', clase: 'bg-amber-500' }],
                async cargar() { const r = await axios.get('{{ route('bancas.estado', $banca) }}'); this.estado = r.data; if (!this.estado.votacion?.voto) this.cambiando = false; this.actualizarTiempo() },
                actualizarTiempo() { if (!this.estado.votacion?.finaliza_en) { this.restante = ''; return } const s = Math.max(0, Math.ceil((new Date(this.estado.votacion.finaliza_en) - new Date()) / 1000)); this.restante = 'Tiempo restante: ' + Math.floor(s / 60).toString().padStart(2, '0') + ':' + (s % 60).toString().padStart(2, '0'); if (s > 0) setTimeout(() => this.actualizarTiempo(), 1000); else this.cargar() },
                async votar(opcion) { this.enviando = true; try { await axios.put('/bancas/{{ $banca->id }}/votaciones/' + this.estado.votacion.id + '/voto', { opcion }); this.cambiando = false; await this.cargar() } finally { this.enviando = false } },
                textoVoto(voto) { return { afirmativo: 'AFIRMATIVO', negativo: 'NEGATIVO', abstencion: 'ABSTENCIÓN' }[voto] ?? '' },
                colorVoto(voto) { return { afirmativo: 'text-emerald-600', negativo: 'text-red-600', abstencion: 'text-amber-600' }[voto] ?? '' },
            }
        }
    </script>
</x-layouts.app>
