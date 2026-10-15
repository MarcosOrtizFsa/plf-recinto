<div x-data="monitorSala()" x-init="cargar()" @recinto-resync.window="cargar()"
     class="fixed inset-0 z-40 flex min-h-screen flex-col overflow-hidden bg-[#071a2b] text-white">
    <header class="border-b border-white/10 bg-[#17344d] px-5 py-4 shadow-2xl sm:px-8">
        <div class="mx-auto flex max-w-[1800px] flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-4">
                <div class="grid size-14 shrink-0 place-items-center rounded-full border-4 border-sky-300 bg-white text-sm font-black text-[#17344d] shadow-lg">PLF</div>
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[.3em] text-sky-300">Cámara de Diputados</p>
                    <h1 class="truncate text-xl font-black uppercase sm:text-2xl" x-text="estado.sesion?.nombre ?? 'Monitor público del recinto'"></h1>
                    <p class="text-xs text-slate-300">Toma de asistencia en vivo</p>
                </div>
            </div>
            <div class="flex items-stretch gap-3">
                <div class="min-w-28 rounded-xl bg-emerald-500 px-5 py-2 text-center shadow-lg"><strong class="block text-3xl leading-none" x-text="estado.asistencia?.cantidad ?? 0"></strong><span class="text-xs font-black uppercase">Presentes</span></div>
                <div class="min-w-28 rounded-xl bg-red-500 px-5 py-2 text-center shadow-lg"><strong class="block text-3xl leading-none" x-text="estado.asistencia?.ausentes ?? 0"></strong><span class="text-xs font-black uppercase">Ausentes</span></div>
            </div>
        </div>
    </header>

    <main class="relative flex min-h-0 flex-1 flex-col">
        <section x-show="!estado.activa" class="grid flex-1 place-items-center p-8 text-center">
            <div><div class="mx-auto mb-6 size-5 animate-pulse rounded-full bg-sky-400"></div><p class="text-3xl font-black text-slate-300">Esperando el inicio de la sesión</p></div>
        </section>

        <section x-show="estado.activa && ['asistencia','documento'].includes(estado.vista)" class="flex min-h-0 flex-1 flex-col px-3 pb-4 pt-3 sm:px-7">
            <div class="mx-auto grid min-h-0 w-full max-w-[1800px] flex-1 grid-cols-10 grid-rows-3 gap-x-2 gap-y-2 sm:gap-x-4 sm:gap-y-3">
                <template x-for="legislador in bancasEnGrilla()" :key="legislador.id">
                    <div class="group flex min-h-0 flex-col items-center justify-center text-center">
                        <div class="relative">
                            <div class="grid aspect-square w-[clamp(2.8rem,7.2vw,7.3rem)] place-items-center rounded-full border-[3px] text-[clamp(.8rem,1.7vw,1.5rem)] font-black shadow-xl transition-all duration-500"
                                 :class="colorBanca(legislador.asistencia)" x-text="legislador.banca"></div>
                            <div class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 hidden w-48 -translate-x-1/2 rounded-lg bg-slate-950 px-3 py-2 text-xs font-bold shadow-xl group-hover:block" x-text="legislador.nombre"></div>
                        </div>
                        <span class="mt-1 block w-full max-w-32 truncate text-[clamp(.55rem,.8vw,.72rem)] font-bold text-slate-200" x-text="apellido(legislador.nombre)"></span>
                    </div>
                </template>
            </div>

            <footer class="mx-auto mt-2 w-full max-w-6xl shrink-0">
                <p class="text-center text-base font-black uppercase tracking-wide text-slate-200 sm:text-xl">
                    <span class="text-emerald-400">Presentes <b x-text="estado.asistencia?.cantidad ?? 0"></b></span>,
                    <span class="text-red-400">Ausentes <b x-text="estado.asistencia?.ausentes ?? 0"></b></span>
                    <span class="text-slate-400">de <b x-text="estado.asistencia?.total ?? 30"></b> legisladores</span>
                </p>
                <div class="mt-2 flex items-center gap-3">
                    <div class="relative h-5 flex-1 overflow-hidden rounded-full border border-white/10 bg-slate-700 shadow-inner">
                        <div class="h-full rounded-full bg-gradient-to-r from-sky-400 via-teal-400 to-emerald-500 transition-all duration-700" :style="`width: ${estado.asistencia?.porcentaje ?? 0}%`"></div>
                        <div class="absolute inset-y-0 left-[60%] w-0.5 bg-white"></div>
                    </div>
                    <strong class="w-12 text-right text-sm text-sky-300" x-text="`${Math.round(estado.asistencia?.porcentaje ?? 0)}%`"></strong>
                </div>
                <div class="mt-2 flex min-h-14 items-center justify-center">
                    <div x-cloak x-show="estado.asistencia?.quorum" x-transition.scale.duration.500ms class="rounded-xl border border-emerald-300/70 bg-emerald-400/15 px-12 py-2 text-center shadow-[0_0_35px_rgba(52,211,153,.25)]">
                        <strong class="block text-2xl font-black tracking-[.3em] text-emerald-300 sm:text-3xl">QUÓRUM</strong>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-100">Mayoría reglamentaria alcanzada</span>
                    </div>
                    <div x-show="!estado.asistencia?.quorum" class="text-center text-xs font-bold uppercase tracking-widest text-slate-400">
                        Quórum: 60% · <span x-text="`${Math.max(0, Math.ceil((estado.asistencia?.total ?? 30) * .6) - (estado.asistencia?.cantidad ?? 0))} presentes restantes`"></span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-x-7 gap-y-1 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                    <span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-slate-500"></i>Sin marcar <b x-text="estado.asistencia?.sin_marcar ?? 0"></b></span>
                    <span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-emerald-500"></i>Presente</span>
                    <span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-red-500"></i>Ausente</span>
                    <span class="flex items-center gap-2"><i class="size-2.5 rounded-full bg-amber-400"></i>Justificado <b x-text="estado.asistencia?.justificados ?? 0"></b></span>
                </div>
            </footer>
        </section>

        <section x-show="estado.vista === 'votacion'" class="grid flex-1 place-items-center p-8 text-center"><div><p class="text-xl uppercase tracking-widest text-sky-400">Votación en curso</p><h2 class="mt-5 text-5xl font-black" x-text="estado.votacion?.tema"></h2><p class="mt-8 text-2xl text-slate-300">Esperando los votos de las bancas habilitadas</p></div></section>
        <section x-show="estado.vista === 'resultado' && estado.resultado" class="grid flex-1 place-items-center p-8 text-center"><div class="w-full max-w-5xl"><p class="text-xl uppercase tracking-widest text-sky-400">Resultado final</p><h2 class="mt-4 text-4xl font-black" x-text="estado.resultado?.tema"></h2><div class="mt-10 grid gap-5 md:grid-cols-3"><div class="rounded-2xl bg-emerald-600 p-8"><strong class="text-6xl" x-text="estado.resultado?.afirmativos"></strong><span class="mt-2 block text-xl font-bold">AFIRMATIVOS</span></div><div class="rounded-2xl bg-red-600 p-8"><strong class="text-6xl" x-text="estado.resultado?.negativos"></strong><span class="mt-2 block text-xl font-bold">NEGATIVOS</span></div><div class="rounded-2xl bg-amber-500 p-8 text-slate-950"><strong class="text-6xl" x-text="estado.resultado?.abstenciones"></strong><span class="mt-2 block text-xl font-bold">ABSTENCIONES</span></div></div></div></section>
    </main>
</div>

<script>
    function monitorSala() {
        return {
            estado: { activa: false, vista: 'espera' },
            async cargar() { const r = await axios.get('{{ route('monitor.estado') }}'); this.estado = r.data },
            apellido(nombre) { return nombre?.trim().split(/\s+/).slice(-1)[0] ?? '' },
            colorBanca(estado) {
                return { presente: 'border-emerald-200 bg-emerald-500 text-white shadow-emerald-500/30', ausente: 'border-red-200 bg-red-500 text-white shadow-red-500/30', justificado: 'border-amber-200 bg-amber-400 text-slate-950 shadow-amber-400/30', sin_marcar: 'border-slate-300 bg-slate-500 text-white shadow-slate-950/30' }[estado] ?? 'border-slate-300 bg-slate-500 text-white'
            },
            bancasEnGrilla() {
                const legisladores = this.estado.asistencia?.legisladores ?? []
                return Array.from({ length: 30 }, (_, indice) => legisladores[indice] ?? { id: `vacio-${indice}`, banca: indice + 1, nombre: 'Sin asignar', asistencia: 'sin_marcar' })
            },
        }
    }
</script>
