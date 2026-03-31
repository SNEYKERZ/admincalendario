<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-4xl space-y-8 p-8">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Política de Privacidad
                </h1>
                <p class="mt-2 text-gray-500 dark:text-gray-400">
                    Última actualización:
                    {{ new Date().toLocaleDateString('es-CO') }}
                </p>
            </div>

            <div class="prose prose-lg dark:prose-invert max-w-none space-y-6">
                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        1. Información que Recopilamos
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Recopilamos diferentes tipos de información para
                        proporcionar y mejorar nuestros servicios:
                    </p>
                    <ul
                        class="mt-4 list-disc space-y-2 pl-6 text-gray-600 dark:text-gray-400"
                    >
                        <li>
                            <strong>Información de registro:</strong> Nombre,
                            correo electrónico, teléfono, identificación
                        </li>
                        <li>
                            <strong>Información laboral:</strong> Fecha de
                            contratación, departamento, cargo
                        </li>
                        <li>
                            <strong>Datos de uso:</strong> Cómo interactúa con
                            el sistema
                        </li>
                        <li>
                            <strong>Información de ausencias:</strong> Tipos,
                            fechas, duraciones de solicitudes
                        </li>
                    </ul>
                </section>

                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        2. Cómo Utilizamos su Información
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Utilizamos la información recopilada para:
                    </p>
                    <ul
                        class="mt-4 list-disc space-y-2 pl-6 text-gray-600 dark:text-gray-400"
                    >
                        <li>Gestionar solicitudes de ausencia y vacaciones</li>
                        <li>Mantener registros de tiempo de empleados</li>
                        <li>Generar reportes y estadísticas</li>
                        <li>Comunicarnos con usted sobre sus solicitudes</li>
                        <li>Cumplir con obligaciones legales y fiscales</li>
                    </ul>
                </section>

                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        3. Compartir Información
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        No vendemos su información personal. Compartimos datos
                        únicamente con:
                    </p>
                    <ul
                        class="mt-4 list-disc space-y-2 pl-6 text-gray-600 dark:text-gray-400"
                    >
                        <li>
                            <strong>Proveedores de servicios:</strong> Empresas
                            que nos ayudan a operar el sistema
                        </li>
                        <li>
                            <strong>Administradores:</strong> Personal de RRHH
                            autorizado
                        </li>
                        <li>
                            <strong>Cumplimiento legal:</strong> Cuando sea
                            requerido por ley
                        </li>
                    </ul>
                </section>

                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        4. Seguridad
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Implementamos medidas de seguridad apropiadas para
                        proteger su información personal, incluyendo
                        encriptación, controles de acceso y auditorías de
                        seguridad regulares. Sin embargo, ningún sistema es 100%
                        seguro y no podemos garantizar seguridad absoluta.
                    </p>
                </section>

                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        5. Retención de Datos
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Conservamos su información mientras su cuenta esté
                        activa o según sea necesario para proporcionar
                        servicios. Los datos de ausencia se mantienen según las
                        regulaciones laborales aplicables. Puede solicitar la
                        eliminación de sus datos en cualquier momento.
                    </p>
                </section>

                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        6. Sus Derechos (GDPR/LCPD)
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Dependiendo de su ubicación, usted tiene derecho a:
                    </p>
                    <ul
                        class="mt-4 list-disc space-y-2 pl-6 text-gray-600 dark:text-gray-400"
                    >
                        <li>
                            <strong>Acceso:</strong> Obtener una copia de sus
                            datos
                        </li>
                        <li>
                            <strong>Rectificación:</strong> Corregir datos
                            incorrectos
                        </li>
                        <li>
                            <strong>Eliminación:</strong> Solicitar eliminación
                            de sus datos
                        </li>
                        <li>
                            <strong>Portabilidad:</strong> Recibir sus datos en
                            formato estructurado
                        </li>
                        <li>
                            <strong>Oposición:</strong> Objetar al procesamiento
                            de sus datos
                        </li>
                    </ul>
                </section>

                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        7. Cookies y Tecnologías Similares
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Utilizamos cookies y tecnologías similares para mejorar
                        su experiencia, analizar el uso del sitio y para
                        propósitos de seguridad. Puede configurar su navegador
                        para rechazar cookies, aunque esto puede afectar algunas
                        funcionalidades.
                    </p>
                </section>

                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        8. Cambios a esta Política
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Podemos actualizar esta política periódicamente. Le
                        notificaremos sobre cambios significativos publicando la
                        nueva política en el sistema. Le recomendamos revisar
                        esta política regularmente.
                    </p>
                </section>

                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        9. Contacto
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Si tiene preguntas sobre esta Política de Privacidad o
                        desea ejercer sus derechos, contacte al administrador
                        del sistema o envíe un correo a privacidad@empresa.com.
                    </p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
