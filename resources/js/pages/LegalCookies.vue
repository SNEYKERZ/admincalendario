<script setup lang="ts">
import LegalField from '@/components/legal/LegalField.vue';
import LegalLayout from '@/layouts/LegalLayout.vue';
import type { LegalInfo } from '@/types/legal';

defineProps<{
    legal: LegalInfo;
    cookies: { session: string; session_minutes: number };
}>();
</script>

<template>
    <LegalLayout title="Política de cookies" :legal="legal">
        <p>
            Las cookies son pequeños archivos que el sitio guarda en su
            navegador. {{ legal.product_name }} usa
            <strong>únicamente cookies técnicas y de preferencia</strong>,
            necesarias para iniciar sesión, proteger los formularios y recordar
            ajustes de la interfaz.
            <strong>No usamos cookies de publicidad, de analítica ni de
            seguimiento de terceros</strong>, y no creamos perfiles de
            navegación.
        </p>

        <section aria-labelledby="c-lista">
            <h2 id="c-lista">1. Cookies y almacenamiento que usamos</h2>
            <div class="overflow-x-auto">
                <table>
                    <caption class="sr-only">
                        Lista de cookies y almacenamiento local
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Nombre</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Finalidad</th>
                            <th scope="col">Duración</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <code>{{ cookies.session }}</code>
                            </td>
                            <td>Técnica, propia</td>
                            <td>Mantener la sesión iniciada del usuario.</td>
                            <td>
                                {{ cookies.session_minutes }} minutos de
                                inactividad
                            </td>
                        </tr>
                        <tr>
                            <td><code>XSRF-TOKEN</code></td>
                            <td>Técnica, propia</td>
                            <td>
                                Proteger los formularios contra solicitudes
                                falsificadas (CSRF).
                            </td>
                            <td>
                                {{ cookies.session_minutes }} minutos
                            </td>
                        </tr>
                        <tr>
                            <td><code>remember_web_*</code></td>
                            <td>Técnica, propia</td>
                            <td>
                                Recordar la sesión solo si el usuario marca
                                "Recordarme" al iniciar sesión.
                            </td>
                            <td>Hasta cerrar sesión o un máximo de 400 días</td>
                        </tr>
                        <tr>
                            <td><code>appearance</code></td>
                            <td>Preferencia, propia</td>
                            <td>Recordar el modo claro u oscuro elegido.</td>
                            <td>1 año</td>
                        </tr>
                        <tr>
                            <td><code>sidebar_state</code></td>
                            <td>Preferencia, propia</td>
                            <td>
                                Recordar si el menú lateral está abierto o
                                cerrado.
                            </td>
                            <td>7 días</td>
                        </tr>
                        <tr>
                            <td>
                                <code>appearance</code> (almacenamiento local)
                            </td>
                            <td>Preferencia, propia</td>
                            <td>Recordar el tema de la interfaz.</td>
                            <td>Hasta que se borre en el navegador</td>
                        </tr>
                        <tr>
                            <td>
                                <code>super_admin_context</code>
                                (almacenamiento de sesión)
                            </td>
                            <td>Técnica, propia</td>
                            <td>
                                Uso interno de administradores de la
                                plataforma.
                            </td>
                            <td>Hasta cerrar la pestaña</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section aria-labelledby="c-terceros">
            <h2 id="c-terceros">2. Servicios de terceros</h2>
            <p>
                Las tipografías del sitio se cargan desde Bunny Fonts
                (fonts.bunny.net). Este servicio recibe la dirección IP del
                navegador para entregar los archivos, pero no instala cookies.
            </p>
        </section>

        <section aria-labelledby="c-consentimiento">
            <h2 id="c-consentimiento">3. ¿Por qué no hay un aviso de cookies?</h2>
            <p>
                Las cookies descritas son necesarias para que el servicio
                funcione o guardan una preferencia que usted mismo eligió. No se
                usan para rastrearlo ni para publicidad, por eso no pedimos un
                consentimiento aparte. Si en el futuro incorporamos cookies de
                analítica o de publicidad, pediremos su autorización antes de
                activarlas y actualizaremos esta política.
            </p>
        </section>

        <section aria-labelledby="c-control">
            <h2 id="c-control">4. Cómo controlarlas</h2>
            <p>
                Puede borrar o bloquear las cookies desde la configuración de
                su navegador. Si bloquea las cookies técnicas, no podrá iniciar
                sesión en la plataforma.
            </p>
        </section>

        <section aria-labelledby="c-contacto">
            <h2 id="c-contacto">5. Contacto</h2>
            <p>
                Si tiene preguntas, escriba a
                <LegalField
                    :value="legal.privacy_email"
                    label="correo de privacidad"
                    email
                />. Consulte también la
                <a href="/legal/privacy">Política de tratamiento de datos
                personales</a>.
            </p>
        </section>
    </LegalLayout>
</template>
