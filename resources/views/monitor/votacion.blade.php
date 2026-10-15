<section x-show="estado.vista === 'votacion' && estado.votacion_en_vivo" class="flex min-h-0 flex-1 flex-col bg-slate-100 px-5 py-4 text-slate-900">
    <header class="flex shrink-0 items-start justify-between gap-6">
        <div class="min-w-0"><p class="text-xs font-black uppercase tracking-[.25em] text-sky-700">Votación en vivo · En general</p><h2 class="mt-1 truncate text-3xl font-black" x-text="estado.votacion?.tema"></h2></div>
        <div class="shrink-0 text-right text-xl font-black sm:text-2xl">
            <p class="text-emerald-600">AFIRMATIVO: <span x-text="estado.votacion?.afirmativos ?? 0"></span></p>
            <p class="text-red-600">NEGATIVO: <span x-text="estado.votacion?.negativos ?? 0"></span></p>
            <p class="text-amber-600">ABSTENCIÓN: <span x-text="estado.votacion?.abstenciones ?? 0"></span></p>
        </div>
    </header>
    <div class="mt-4 grid min-h-0 flex-1 grid-cols-10 grid-rows-3 gap-3">
        <template x-for="legislador in estado.votacion?.bancas ?? []" :key="legislador.id">
            <div class="relative grid min-h-0 place-items-center rounded-xl border-2 shadow-sm transition-all duration-500" :class="claseVotoPublico(legislador)">
                <span x-show="legislador.asistencia !== 'presente'" class="text-5xl font-light">×</span>
                <span x-show="legislador.asistencia === 'presente' && legislador.voto" class="text-center text-sm font-black uppercase" x-text="etiquetaVoto(legislador.voto)"></span>
                <span class="absolute bottom-1 left-2 text-[10px] font-black opacity-70" x-text="legislador.banca"></span>
                <span class="absolute bottom-1 right-2 max-w-[70%] truncate text-[9px] font-bold opacity-70" x-text="apellido(legislador.nombre)"></span>
            </div>
        </template>
    </div>
    <footer class="mt-4 flex shrink-0 items-center justify-center gap-7 text-xs font-black uppercase text-slate-600">
        <span class="flex items-center gap-2"><i class="size-4 rounded bg-white ring-1 ring-slate-300"></i>Esperando voto</span>
        <span class="flex items-center gap-2"><i class="size-4 rounded bg-emerald-600"></i>Afirmativo</span>
        <span class="flex items-center gap-2"><i class="size-4 rounded bg-red-600"></i>Negativo</span>
        <span class="flex items-center gap-2"><i class="size-4 rounded bg-amber-400"></i>Abstención</span>
        <span class="flex items-center gap-2"><i class="grid size-4 place-items-center rounded bg-white text-sm leading-none ring-1 ring-slate-300">×</i>Ausente</span>
    </footer>
</section>
