const pantallaRecinto = () => document.body.dataset.pantallaRecinto === '1';
let temporizadorLatido = null;

function actualizarCsrf(token) {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && token) meta.content = token;
    if (token) window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    if (token) {
        document.querySelectorAll('input[name="_token"]').forEach((input) => {
            input.value = token;
        });
    }
}

async function latido() {
    if (!pantallaRecinto()) return false;
    const respuesta = await window.axios.get('/latido', { headers: { Accept: 'application/json' }, __sinReintento: true });
    actualizarCsrf(respuesta.data.csrf_token);
    clearTimeout(temporizadorLatido);
    if (respuesta.data.activa) {
        temporizadorLatido = setTimeout(latido, respuesta.data.intervalo_segundos * 1000);
    }
    return respuesta.data.activa;
}

window.axios.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (token) config.headers['X-CSRF-TOKEN'] = token;
    return config;
});

window.axios.interceptors.response.use(undefined, async (error) => {
    const original = error.config;
    if ([401, 419].includes(error.response?.status) && !original?.__reintentado && !original?.__sinReintento) {
        original.__reintentado = true;
        try {
            await latido();
            return window.axios(original);
        } catch (_) {
            window.dispatchEvent(new CustomEvent('recinto-reauth'));
        }
    }
    return Promise.reject(error);
});

document.addEventListener('DOMContentLoaded', async () => {
    if (!pantallaRecinto()) return;
    try { await latido(); } catch (_) {}

    const resincronizar = () => window.dispatchEvent(new CustomEvent('recinto-resync'));
    window.Echo.channel('monitor-publico').listen('.estado.actualizado', async () => {
        await latido().catch(() => false);
        resincronizar();
    });
    if (document.body.dataset.autenticado === '1') {
        window.Echo.private('recinto').listen('.estado.actualizado', resincronizar);
    }
    window.Echo.connector.pusher.connection.bind('connected', resincronizar);
});
