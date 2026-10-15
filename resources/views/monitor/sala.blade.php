<div x-data="monitorSala()" x-init="cargar()" @recinto-resync.window="cargar()"
     class="fixed inset-0 z-40 flex min-h-screen flex-col overflow-hidden bg-[#071a2b] text-white">
    <header x-show="estado.activa" class="border-b border-white/10 bg-[#17344d] px-5 py-4 shadow-2xl sm:px-8">
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
            <div>
                <div class="mx-auto grid size-32 place-items-center rounded-full border-4 border-sky-300 bg-white text-4xl font-black text-[#17344d] shadow-2xl shadow-sky-950/40">PLF</div>
                <p class="mt-7 text-3xl font-black tracking-[.35em] text-white">RECINTO</p>
            </div>
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

        @include('monitor.votacion')
        <section x-show="estado.vista === 'resultado' && estado.resultado" class="flex min-h-0 flex-1 flex-col bg-slate-100 px-5 py-4 text-slate-900">
            <header class="flex shrink-0 items-start justify-between gap-6"><div class="min-w-0"><p class="text-xs font-black uppercase tracking-[.25em] text-sky-700">Resultado final · En general</p><h2 class="mt-1 truncate text-3xl font-black" x-text="estado.resultado?.tema"></h2></div><div class="shrink-0 text-right text-2xl font-black"><p class="text-emerald-600">AFIRMATIVO: <span x-text="estado.resultado?.afirmativos"></span></p><p class="text-red-600">NEGATIVO: <span x-text="estado.resultado?.negativos"></span></p><p class="text-amber-600">ABSTENCIÓN: <span x-text="estado.resultado?.abstenciones"></span></p></div></header>
            <div class="mt-4 grid min-h-0 flex-1 grid-cols-10 grid-rows-3 gap-3"><template x-for="legislador in estado.resultado?.bancas ?? []" :key="legislador.id"><div class="relative grid min-h-0 place-items-center rounded-xl border-2 shadow-sm" :class="claseVotoPublico(legislador)"><span x-show="legislador.asistencia !== 'presente'" class="text-5xl font-light">×</span><span x-show="legislador.asistencia === 'presente' && legislador.voto" class="text-sm font-black uppercase" x-text="etiquetaVoto(legislador.voto)"></span><span class="absolute bottom-1 left-2 text-[10px] font-black opacity-70" x-text="legislador.banca"></span><span class="absolute bottom-1 right-2 max-w-[70%] truncate text-[9px] font-bold opacity-70" x-text="apellido(legislador.nombre)"></span></div></template></div>
        </section>
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
            claseVotoPublico(legislador) {
                if (legislador.asistencia !== 'presente') return 'border-slate-300 bg-white text-slate-700'
                return { afirmativo: 'border-emerald-700 bg-emerald-600 text-white', negativo: 'border-red-700 bg-red-600 text-white', abstencion: 'border-amber-500 bg-amber-400 text-slate-950' }[legislador.voto] ?? 'border-slate-300 bg-white text-slate-800'
            },
            etiquetaVoto(voto) { return { afirmativo: 'Sí', negativo: 'No', abstencion: 'Abs.' }[voto] ?? '' },
            bancasEnGrilla() {
                const legisladores = this.estado.asistencia?.legisladores ?? []
                return Array.from({ length: 30 }, (_, indice) => legisladores[indice] ?? { id: `vacio-${indice}`, banca: indice + 1, nombre: 'Sin asignar', asistencia: 'sin_marcar' })
            },
        }
    }
</script>
