import { createInertiaApp, router } from '@inertiajs/vue3';
import axios from 'axios';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import Toast, { POSITION, useToast } from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import '../css/app.css';
import { initializeTheme } from '@/composables/useAppearance';

/**
 * Mensajes amables por defecto para respuestas de error comunes. Las páginas
 * muestran `error.response.data.message`; si el servidor no envía uno (o envía
 * el genérico de Laravel), se sustituye por un texto comprensible.
 */
const FRIENDLY_MESSAGES: Record<number, string> = {
    403: 'No tienes permisos para realizar esta acción.',
    404: 'El recurso solicitado no existe o fue eliminado.',
    419: 'Tu sesión expiró. Recarga la página e inténtalo de nuevo.',
    429: 'Demasiadas solicitudes. Espera un momento e inténtalo de nuevo.',
    500: 'Ocurrió un error inesperado en el servidor.',
    503: 'El servicio no está disponible en este momento. Inténtalo más tarde.',
};
const GENERIC_SERVER_MESSAGES = new Set([
    'This action is unauthorized.',
    'Forbidden',
    'Not Found',
    'CSRF token mismatch.',
    'Server Error',
    'Service Unavailable',
    'Too Many Attempts.',
]);

axios.interceptors.response.use(undefined, (error) => {
    const status: number | undefined = error?.response?.status;
    if (status && FRIENDLY_MESSAGES[status]) {
        const data = error.response.data;
        if (data && typeof data === 'object') {
            if (!data.message || GENERIC_SERVER_MESSAGES.has(data.message)) {
                data.message = FRIENDLY_MESSAGES[status];
            }
        } else {
            error.response.data = { message: FRIENDLY_MESSAGES[status] };
        }
    }
    return Promise.reject(error);
});

const appName = import.meta.env.VITE_APP_NAME || 'Calendar App';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),

    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });

        vueApp
            .use(plugin)
            .use(Toast, {
                position: POSITION.BOTTOM_RIGHT,
                timeout: 3500,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: false,
                hideProgressBar: false,
                maxToasts: 4,
                newestOnTop: true,
                transition: 'Vue-Toastification__fade',
            })
            .mount(el);

        // 419 (token CSRF caducado) fuera del login: el servidor vuelve a la
        // página con errors.csrf; se avisa con un toast.
        router.on('error', (event) => {
            const message = event.detail.errors?.csrf;
            if (message) {
                useToast().error(message);
            }
        });
    },

    progress: {
        color: '#4f5bd5',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
