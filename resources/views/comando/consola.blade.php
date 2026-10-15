@php
    $documentosComando = $documentos->values()->map(fn ($documento, $indice) => [
        'id' => $documento->id,
        'titulo' => $documento->titulo,
        'es_sumario' => $indice === 0,
        'preview_url' => route('configuracion.documentos.preview', $documento),
        'mostrar_url' => $sesionActiva ? route('comando.documentos.mostrar', [$sesionActiva, $documento]) : null,
    ]);
@endphp

<div x-data="consolaRecinto(@js($estadoInicial), @js($documentosComando))" x-init="iniciarReloj()"
     @recinto-resync.window="cargarEstado()"
     class="fixed inset-0 z-40 flex min-h-screen flex-col overflow-hidden bg-slate-950 text-slate-100">
    <header class="flex h-[72px] shrink-0 items-center justify-between border-b border-emerald-800 bg-emerald-950 px-4 shadow-xl">
        <div class="min-w-0">
            <p class="text-[10px] font-black uppercase tracking-[.28em] text-emerald-400">PLF · Secretaría Legislativa</p>
            <h1 class="truncate text-lg font-black" x-text="estado.activa ? estado.sesion.nombre : 'Sistema del Recinto'"></h1>
            <p class="text-xs text-emerald-200/60" x-text="estado.activa ? `Sesión ${estado.sesion.numero}` : 'Ecosistema detenido'"></p>
        </div>

        <nav class="flex items-center gap-2" aria-label="Módulos del comando">
            <button @click="window.location.reload()" class="rounded-lg border border-emerald-700 px-4 py-2 text-sm font-bold hover:bg-emerald-900">↻ Sumario</button>
            <button @click="panel = panel === 'asistencia' ? null : 'asistencia'" :class="panel === 'asistencia' ? 'bg-emerald-500 text-emerald-950' : 'border border-emerald-700 hover:bg-emerald-900'" class="rounded-lg px-4 py-2 text-sm font-bold">♟ Asistencia</button>
            <button @click="panel = panel === 'voto' ? null : 'voto'" :class="panel === 'voto' ? 'bg-emerald-500 text-emerald-950' : 'border border-emerald-700 hover:bg-emerald-900'" class="rounded-lg px-4 py-2 text-sm font-bold">✓ Voto</button>
            @if($sesionActiva)
                @if($estadoInicial['suspendida'] ?? false)
                    <form method="POST" action="{{ route('comando.sesiones.reanudar', $sesionActiva) }}">@csrf<button class="rounded-lg bg-emerald-400 px-4 py-2 text-sm font-black text-emerald-950 hover:bg-emerald-300">● Encender recinto</button></form>
                @else
                    <form method="POST" action="{{ route('comando.sesiones.suspender', $sesionActiva) }}" onsubmit="return confirm('¿Suspender el recinto por cuarto intermedio? La sesión continuará en curso.')">@csrf<button class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-black text-slate-950 hover:bg-amber-300">○ Suspender recinto</button></form>
                @endif
            @else
                <button @click="panel = 'inicio'" class="rounded-lg bg-emerald-400 px-4 py-2 text-sm font-black text-emerald-950 hover:bg-emerald-300">● Iniciar sesión</button>
            @endif
        </nav>
    </header>

    <div class="grid min-h-0 flex-1" :class="panel === 'asistencia' ? 'grid-cols-[minmax(300px,400px)_1fr_320px]' : 'grid-cols-[1fr_320px]'">
        <aside x-show="panel === 'asistencia'" x-transition class="flex min-h-0 flex-col border-r border-slate-700 bg-slate-100 text-slate-900">
            <div class="flex items-center justify-between bg-amber-400 px-4 py-3">
                <div><strong class="text-lg">Presentes <span x-text="estado.asistencia?.cantidad ?? 0"></span></strong><span class="mx-2">|</span><strong>Ausentes <span x-text="estado.asistencia?.ausentes ?? 0"></span></strong></div>
                <button @click="panel = null" class="text-2xl leading-none" aria-label="Cerrar asistencia">×</button>
            </div>
            <div class="grid grid-cols-[1fr_44px_44px] border-b bg-white text-xs font-black uppercase"><span class="px-3 py-2"><b x-text="estado.asistencia?.total ?? 30"></b> legisladores</span><span class="bg-emerald-600 py-2 text-center text-white">P</span><span class="bg-red-500 py-2 text-center text-white">A</span></div>
            <div class="min-h-0 flex-1 overflow-y-auto">
                <template x-for="legislador in estado.legisladores ?? []" :key="legislador.id">
                    <div class="grid min-h-11 grid-cols-[1fr_44px_44px] items-stretch border-b border-slate-300 bg-white text-sm">
                        <button @click="marcar(legislador, 'sin_marcar')" class="flex min-w-0 items-center gap-2 px-3 text-left hover:bg-slate-50" title="Reiniciar este legislador">
                            <span class="grid size-6 shrink-0 place-items-center rounded-full text-xs text-white" :class="iconoEstado(legislador.asistencia)">●</span>
                            <span class="truncate"><b x-text="legislador.banca"></b> · <span x-text="legislador.nombre"></span></span>
                        </button>
                        <button @click="marcar(legislador, 'presente')" class="grid place-items-center bg-emerald-600 hover:bg-emerald-500" aria-label="Marcar presente"><span class="size-5 rounded-full border-2 border-white" :class="legislador.asistencia === 'presente' ? 'bg-white ring-2 ring-emerald-200' : ''"></span></button>
                        <button @click="marcar(legislador, 'ausente')" class="grid place-items-center bg-red-500 hover:bg-red-400" aria-label="Marcar ausente"><span class="size-5 rounded-full border-2 border-white" :class="legislador.asistencia === 'ausente' ? 'bg-white ring-2 ring-red-200' : ''"></span></button>
                    </div>
                </template>
            </div>
            <button @click="reiniciarAsistencia()" class="border-t border-slate-300 bg-slate-200 px-4 py-3 text-sm font-black hover:bg-slate-300">↺ Reiniciar toda la asistencia a cero</button>
        </aside>

        <main class="relative flex min-w-0 flex-col bg-slate-900">
            <div class="flex h-12 shrink-0 items-center justify-between border-b border-slate-700 bg-black px-4">
                <strong class="truncate text-lg uppercase tracking-wide" x-text="documentoActivo?.titulo ?? 'Vista previa documental'"></strong>
                <span class="rounded-full px-3 py-1 text-xs font-bold" :class="estado.activa ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-700 text-slate-300'" x-text="estado.activa ? 'RECINTO ACTIVO' : 'RECINTO EN ESPERA'"></span>
            </div>

            <div class="relative min-h-0 flex-1 bg-slate-800">
                <iframe x-show="documentoActivo" :src="documentoActivo?.preview_url" class="h-full w-full border-0 bg-white" title="Vista previa del documento"></iframe>
                <div x-show="!documentoActivo" class="grid h-full place-items-center p-8 text-center text-slate-400"><div><div class="mb-4 text-6xl">▤</div><p class="text-xl font-bold">Seleccione un PDF de la biblioteca</p><p class="mt-2 text-sm">Las bancas permanecerán esperando hasta que publique un documento.</p></div></div>

                <div x-show="seleccionados.length > 1" class="absolute bottom-5 left-1/2 flex -translate-x-1/2 gap-3 rounded-full bg-slate-950/90 p-2 shadow-2xl">
                    <template x-for="documento in documentosSeleccionados()" :key="documento.id"><button @click="publicar(documento)" class="grid size-16 place-items-center rounded-full border-2 border-amber-400 bg-amber-500 text-[10px] font-black uppercase text-slate-950 shadow-lg" x-text="documento.es_sumario ? 'Sumario' : 'Despacho'"></button></template>
                </div>
            </div>

            @include('comando.votacion')

            <section x-show="panel === 'inicio'" x-transition class="absolute inset-0 z-30 grid place-items-center bg-slate-950/95 p-8">
                <div class="w-full max-w-2xl rounded-2xl border border-slate-700 bg-slate-900 p-7"><div class="flex justify-between"><div><p class="text-xs font-black uppercase tracking-[.25em] text-emerald-400">Inicio del ecosistema</p><h2 class="mt-2 text-2xl font-black">Seleccione la sesión parlamentaria</h2></div><button @click="panel = null" class="text-3xl">×</button></div><div class="mt-6 space-y-3">@forelse($sesiones as $sesion)<form method="POST" action="{{ route('comando.sesiones.activar', $sesion) }}" class="flex items-center justify-between rounded-xl border border-slate-700 p-4 hover:border-emerald-500">@csrf<div><strong>{{ $sesion->nombre }}</strong><small class="block text-slate-400">{{ $sesion->numero }}</small></div><button class="rounded-lg bg-emerald-500 px-5 py-2 font-black text-emerald-950">Iniciar</button></form>@empty<p class="text-slate-400">No hay sesiones preparadas para iniciar.</p>@endforelse</div></div>
            </section>
        </main>

        <aside class="flex min-h-0 flex-col border-l border-slate-700 bg-slate-100 text-slate-900">
            <div class="border-b border-slate-300 bg-white px-4 py-4"><p class="text-[10px] font-black uppercase tracking-[.2em] text-slate-500">Biblioteca de sesión</p><h2 class="text-lg font-black">Documentos PDF</h2></div>
            <div class="min-h-0 flex-1 overflow-y-auto">
                <template x-for="(documento, indice) in documentos" :key="documento.id">
                    <label class="flex cursor-pointer gap-3 border-b border-slate-300 p-4 hover:bg-white">
                        <input type="checkbox" :checked="seleccionados.includes(documento.id)" @change="alternarDocumento(documento, $event.target.checked)" class="mt-1 size-5 accent-emerald-600">
                        <span class="text-red-600">▧</span>
                        <span class="min-w-0"><strong class="block break-words text-sm" x-text="documento.es_sumario ? `SUMARIO · ${documento.titulo}` : documento.titulo"></strong><small class="text-slate-500" x-text="documento.es_sumario ? 'Documento obligatorio' : `Despacho ${indice}`"></small></span>
                    </label>
                </template>
                <div x-show="documentos.length === 0" class="p-6 text-center text-sm text-slate-500">No hay documentos activos asociados a esta sesión.</div>
            </div>
            <a href="{{ route('configuracion.documentos.index') }}" class="border-t border-slate-300 bg-white px-4 py-3 text-center text-sm font-bold hover:bg-slate-50">Administrar documentos</a>
        </aside>
    </div>
</div>

<script>
    function consolaRecinto(estadoInicial, documentos) {
        return {
            estado: estadoInicial,
            documentos,
            panel: estadoInicial.activa ? 'asistencia' : null,
            seleccionados: estadoInicial.documento?.id ? [estadoInicial.documento.id] : [],
            documentoActivo: estadoInicial.documento?.id ? documentos.find(d => d.id === estadoInicial.documento.id) : null,
            votacionSeleccionadaId: estadoInicial.votacion?.id ?? estadoInicial.votaciones_comando?.find(v => v.estado === 'configurada')?.id ?? estadoInicial.votaciones_comando?.[0]?.id ?? null,
            modoVotacion: 'directo',
            duracionVotacion: 60,
            ahora: Date.now(),
            iniciarReloj() { setInterval(() => { this.ahora = Date.now(); const activa = this.votacionSeleccionada(); if (activa?.estado === 'abierta' && activa.finaliza_en && new Date(activa.finaliza_en).getTime() <= this.ahora) this.cargarEstado() }, 1000) },
            async cargarEstado() {
                const respuesta = await axios.get('{{ route('comando.estado') }}')
                this.estado = respuesta.data
                if (!this.votacionSeleccionadaId) this.votacionSeleccionadaId = respuesta.data.votacion?.id ?? respuesta.data.votaciones_comando?.[0]?.id ?? null
                if (respuesta.data.documento?.id) this.documentoActivo = this.documentos.find(d => d.id === respuesta.data.documento.id) ?? this.documentoActivo
            },
            async marcar(legislador, estado) {
                const anterior = legislador.asistencia
                legislador.asistencia = estado
                try {
                    await axios.put('{{ $sesionActiva ? route('comando.asistencia', $sesionActiva) : '#' }}', { usuario_id: legislador.id, estado }, { headers: { Accept: 'application/json' } })
                    await this.cargarEstado()
                } catch (error) { legislador.asistencia = anterior; throw error }
            },
            async reiniciarAsistencia() {
                if (!confirm('¿Reiniciar toda la asistencia a cero?')) return
                await axios.put('{{ $sesionActiva ? route('comando.asistencia.reset', $sesionActiva) : '#' }}', {}, { headers: { Accept: 'application/json' } })
                await this.cargarEstado()
            },
            iconoEstado(estado) { return { presente: 'bg-emerald-600', ausente: 'bg-red-500', justificado: 'bg-amber-500', sin_marcar: 'bg-slate-400' }[estado] ?? 'bg-slate-400' },
            async alternarDocumento(documento, activo) {
                if (activo) {
                    if (!this.seleccionados.includes(documento.id)) this.seleccionados.push(documento.id)
                    await this.publicar(documento)
                } else {
                    this.seleccionados = this.seleccionados.filter(id => id !== documento.id)
                    if (this.documentoActivo?.id === documento.id) this.documentoActivo = this.documentosSeleccionados().at(-1) ?? null
                }
            },
            async publicar(documento) {
                this.documentoActivo = documento
                if (documento.mostrar_url) await axios.put(documento.mostrar_url, {}, { headers: { Accept: 'application/json' } })
            },
            documentosSeleccionados() { return this.documentos.filter(documento => this.seleccionados.includes(documento.id)).slice(0, 2) },
            votacionSeleccionada() { return this.estado.votaciones_comando?.find(v => v.id === this.votacionSeleccionadaId) ?? null },
            votacionesListas() { return this.estado.votaciones_comando?.filter(v => v.estado === 'configurada') ?? [] },
            legisladoresHabilitados() { return this.estado.legisladores?.filter(l => l.asistencia === 'presente' && l.banca_activa) ?? [] },
            tiempoVotacion() {
                const final = this.votacionSeleccionada()?.finaliza_en
                if (!final) return ''
                const segundos = Math.max(0, Math.ceil((new Date(final).getTime() - this.ahora) / 1000))
                const minutos = Math.floor(segundos / 60).toString().padStart(2, '0')
                return minutos + ':' + (segundos % 60).toString().padStart(2, '0')
            },
            colorVoto(voto) { return { afirmativo: 'text-emerald-700', negativo: 'text-red-700', abstencion: 'text-amber-700' }[voto] ?? 'text-slate-400' },
            async iniciarVotacion() {
                const votacion = this.votacionSeleccionada()
                if (!votacion) return
                await axios.post('/comando/votaciones/' + votacion.id + '/iniciar', { metodo: this.modoVotacion, duracion_segundos: this.duracionVotacion }, { headers: { Accept: 'application/json' } })
                await this.cargarEstado()
            },
            async votoAsistido(legislador, opcion) {
                const votacion = this.votacionSeleccionada()
                await axios.put('/comando/votaciones/' + votacion.id + '/voto', { usuario_id: legislador.id, opcion }, { headers: { Accept: 'application/json' } })
                await this.cargarEstado()
            },
            async cerrarVotacion() {
                const votacion = this.votacionSeleccionada()
                if (!votacion || !confirm('¿Finalizar la recepción de votos?')) return
                await axios.post('/comando/votaciones/' + votacion.id + '/cerrar', {}, { headers: { Accept: 'application/json' } })
                await this.cargarEstado()
            },
            async reiniciarVotacion() {
                const votacion = this.votacionSeleccionada()
                if (!votacion || !confirm('¿Reiniciar completamente esta votación? Se eliminarán todos los votos registrados y quedará lista para volver a iniciarse.')) return
                await axios.put('/comando/votaciones/' + votacion.id + '/reiniciar', {}, { headers: { Accept: 'application/json' } })
                await this.cargarEstado()
            },
            async alternarVotacionEnVivo() {
                const votacion = this.votacionSeleccionada()
                if (!votacion) return
                await axios.post('/comando/votaciones/' + votacion.id + '/visibilidad', { visible: !this.estado.votacion_en_vivo }, { headers: { Accept: 'application/json' } })
                await this.cargarEstado()
            },
            async publicarResultado() {
                const votacion = this.votacionSeleccionada()
                if (!votacion || !confirm('¿Mostrar el resultado final en la pantalla pública?')) return
                await axios.post('/comando/votaciones/' + votacion.id + '/publicar', {}, { headers: { Accept: 'application/json' } })
                await this.cargarEstado()
            },
            async despublicarResultado() {
                const votacion = this.votacionSeleccionada()
                if (!votacion || !confirm('¿Retirar el resultado del monitor y volver a Asistencia?')) return
                await axios.post('/comando/votaciones/' + votacion.id + '/despublicar', {}, { headers: { Accept: 'application/json' } })
                await this.cargarEstado()
            },
        }
    }
</script>
