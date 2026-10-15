<section x-show="panel === 'voto'" x-transition class="absolute inset-0 z-20 flex bg-slate-100 text-slate-900">
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex min-h-20 items-center justify-between gap-4 border-b bg-white px-6 py-3">
            <div class="min-w-0"><p class="text-xs font-black uppercase tracking-[.25em] text-sky-700">Votación en el recinto</p><h2 class="truncate text-2xl font-black" x-text="votacionSeleccionada()?.tema ?? 'Seleccione un tema listo para votar'"></h2></div>
            <div class="flex shrink-0 items-center gap-2"><button x-show="votacionSeleccionada()" @click="reiniciarVotacion()" class="rounded-xl border border-red-300 bg-white px-5 py-3 font-black text-red-700">Reiniciar votación</button><button x-show="votacionSeleccionada()?.estado === 'cerrada' && !votacionSeleccionada()?.resultado_publicado" @click="publicarResultado()" class="rounded-xl bg-sky-700 px-5 py-3 font-black text-white">Publicar en monitor</button><button @click="panel = null" class="grid size-11 place-items-center rounded-xl border text-2xl">×</button></div>
        </header>

        <div x-show="votacionSeleccionada()?.estado === 'configurada'" class="grid flex-1 place-items-center p-8">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-7 shadow-xl">
                <h3 class="text-xl font-black">Iniciar votación EN GENERAL</h3><p class="mt-2 text-slate-500">Seleccione cómo se recibirán los votos.</p>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <button @click="modoVotacion = 'asistido'" :class="modoVotacion === 'asistido' ? 'border-sky-600 bg-sky-50 ring-2 ring-sky-200' : 'border-slate-200'" class="rounded-2xl border p-5 text-left"><strong class="block text-lg">A viva voz</strong><span class="mt-1 block text-sm text-slate-500">Secretaría registra cada voto desde COMANDO.</span></button>
                    <button @click="modoVotacion = 'directo'" :class="modoVotacion === 'directo' ? 'border-sky-600 bg-sky-50 ring-2 ring-sky-200' : 'border-slate-200'" class="rounded-2xl border p-5 text-left"><strong class="block text-lg">Bancas digitales</strong><span class="mt-1 block text-sm text-slate-500">Cada legislador vota desde su pantalla táctil.</span></button>
                </div>
                <label class="mt-6 block text-sm font-bold">Tiempo de votación en segundos</label><input x-model.number="duracionVotacion" type="number" min="10" max="3600" class="mt-2 w-full rounded-xl border px-4 py-3">
                <button @click="iniciarVotacion()" class="mt-6 w-full rounded-xl bg-emerald-600 px-5 py-4 text-lg font-black text-white">Iniciar votación</button>
            </div>
        </div>

        <div x-show="votacionSeleccionada()?.estado === 'abierta'" class="flex min-h-0 flex-1 flex-col">
            <div class="flex items-center justify-between bg-slate-900 px-6 py-3 text-white">
                <div><span class="text-sm text-slate-400">Modalidad</span><strong class="ml-2 uppercase" x-text="votacionSeleccionada()?.metodo === 'asistido' ? 'A viva voz' : 'Bancas digitales'"></strong></div>
                <div class="flex items-center gap-3">
                    <strong class="text-2xl tabular-nums" x-text="tiempoVotacion()"></strong>
                    <button @click="alternarVotacionEnVivo()" :class="estado.votacion_en_vivo ? 'bg-amber-400 text-slate-950' : 'bg-sky-600 text-white'" class="rounded-lg px-4 py-2 font-black" x-text="estado.votacion_en_vivo ? 'Despublicar votación' : 'Publicar votación en vivo'"></button>
                    <button @click="cerrarVotacion()" class="rounded-lg bg-red-600 px-5 py-2 font-black">Finalizar votación</button>
                </div>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <div class="mx-auto max-w-5xl overflow-hidden rounded-xl border bg-white">
                    <div class="grid grid-cols-[1fr_260px_90px] bg-slate-100 px-4 py-3 text-xs font-black uppercase text-slate-600"><span>Legislador/a</span><span>Voto EN GENERAL</span><span>Estado</span></div>
                    <template x-for="legislador in legisladoresHabilitados()" :key="legislador.id">
                        <div class="grid min-h-14 grid-cols-[1fr_260px_90px] items-center border-t px-4">
                            <strong class="truncate"><span x-text="legislador.banca + ') '"></span><span x-text="legislador.nombre"></span></strong>
                            <div x-show="votacionSeleccionada()?.metodo === 'asistido'" class="flex gap-1">
                                <button @click="votoAsistido(legislador, 'afirmativo')" class="grid size-10 place-items-center bg-emerald-600 text-white"><span class="size-5 rounded-full border-2 border-white" :class="legislador.voto === 'afirmativo' ? 'bg-white' : ''"></span></button>
                                <button @click="votoAsistido(legislador, 'abstencion')" class="grid size-10 place-items-center bg-amber-400"><span class="size-5 rounded-full border-2 border-white" :class="legislador.voto === 'abstencion' ? 'bg-white' : ''"></span></button>
                                <button @click="votoAsistido(legislador, 'negativo')" class="grid size-10 place-items-center bg-red-500 text-white"><span class="size-5 rounded-full border-2 border-white" :class="legislador.voto === 'negativo' ? 'bg-white' : ''"></span></button>
                            </div>
                            <span x-show="votacionSeleccionada()?.metodo === 'directo'" class="text-sm font-bold uppercase" :class="colorVoto(legislador.voto)" x-text="legislador.voto ?? 'Esperando'"></span>
                            <span class="text-center text-xl" :class="legislador.voto ? 'text-emerald-600' : 'text-slate-300'" x-text="legislador.voto ? '●' : '○'"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div x-show="votacionSeleccionada()?.estado === 'cerrada'" class="grid flex-1 place-items-center p-8">
            <div class="w-full max-w-3xl rounded-2xl bg-white p-8 text-center shadow-xl">
                <p class="text-sm font-black uppercase tracking-[.25em] text-slate-500">Resultado final</p>
                <div class="mt-7 grid grid-cols-3 gap-4"><div class="rounded-xl bg-emerald-600 p-6 text-white"><strong class="text-5xl" x-text="votacionSeleccionada()?.afirmativos ?? 0"></strong><span class="block font-black">AFIRMATIVOS</span></div><div class="rounded-xl bg-red-600 p-6 text-white"><strong class="text-5xl" x-text="votacionSeleccionada()?.negativos ?? 0"></strong><span class="block font-black">NEGATIVOS</span></div><div class="rounded-xl bg-amber-400 p-6"><strong class="text-5xl" x-text="votacionSeleccionada()?.abstenciones ?? 0"></strong><span class="block font-black">ABSTENCIONES</span></div></div>
                <button x-show="!votacionSeleccionada()?.resultado_publicado" @click="publicarResultado()" class="mt-7 rounded-xl bg-sky-700 px-8 py-4 text-lg font-black text-white">Mostrar resultado en pantalla pública</button>
                <div x-show="votacionSeleccionada()?.resultado_publicado" class="mt-7"><p class="font-black text-emerald-700">Resultado publicado en el monitor</p><button @click="despublicarResultado()" class="mt-4 rounded-xl bg-amber-400 px-7 py-3 font-black text-slate-950">Despublicar y volver a Asistencia</button></div>
            </div>
        </div>
    </div>
    <aside class="w-72 shrink-0 border-l bg-white p-4">
        <div class="rounded-xl bg-amber-400 p-4"><strong class="text-3xl" x-text="votacionesListas().length"></strong><span class="ml-2 text-lg">temas listos para votar</span></div>
        <div class="mt-4 space-y-2"><template x-for="votacion in estado.votaciones_comando ?? []" :key="votacion.id"><button @click="votacionSeleccionadaId = votacion.id" :class="votacionSeleccionadaId === votacion.id ? 'border-sky-600 bg-sky-50' : 'border-slate-200'" class="w-full rounded-xl border p-4 text-left"><strong class="block text-sm" x-text="votacion.tema"></strong><span class="mt-2 block text-xs font-bold uppercase text-slate-500" x-text="votacion.tipo + ' · ' + votacion.estado"></span></button></template></div>
    </aside>
</section>
