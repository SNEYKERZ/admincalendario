<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-4xl space-y-8 p-8">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Términos y Condiciones
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
                        1. Aceptación de Términos
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Al acceder y utilizar este sistema, usted acepta estar
                        sujeto a estos términos y condiciones. Si no está de
                        acuerdo con alguno de estos términos, no debe utilizar
                        el sistema.
                    </p>
                </section>

                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        2. Uso del Sistema
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Este sistema está destinado exclusivamente para la
                        gestión de ausencias y vacaciones de empleados dentro
                        del ámbito laboral. El usuario se compromete a:
                    </p>
                    <ul
                        class="mt-4 list-disc space-y-2 pl-6 text-gray-600 dark:text-gray-400"
                    >
                        <li>
                            Utilizar el sistema únicamente para fines legítimos
                        </li>
                        <li>
                            No intentar acceder a información de otros usuarios
                            sin autorización
                        </li>
                        <li>
                            Mantener la confidencialidad de sus credenciales de
                            acceso
                        </li>
                        <li>
                            Reportar cualquier uso no autorizado de su cuenta
                        </li>
                    </ul>
                </section>

                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        3. Políticas de Privacidad
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Recopilamos y almacenamos información personal necesaria
                        para la gestión de ausencias y vacaciones. Esta
                        información incluye:
                    </p>
                    <ul
                        class="mt-4 list-disc space-y-2 pl-6 text-gray-600 dark:text-gray-400"
                    >
                        <li>Nombre completo e información de contacto</li>
                        <li>
                            Información laboral (fecha de contratación,
                            departamento)
                        </li>
                        <li>Historial de ausencias y vacaciones</li>
                        <li>Datos de uso del sistema</li>
                    </ul>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        Sus datos serán tratados de acuerdo con las leyes de
                        protección de datos aplicables y no serán compartidos
                        con terceros sin su consentimiento, excepto cuando sea
                        necesario para la operación del sistema.
                    </p>
                </section>

                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        4. Derechos del Usuario
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Como usuario, usted tiene derecho a:
                    </p>
                    <ul
                        class="mt-4 list-disc space-y-2 pl-6 text-gray-600 dark:text-gray-400"
                    >
                        <li>
                            Acceder a sus datos personales almacenados en el
                            sistema
                        </li>
                        <li>Rectificar datos incorrectos</li>
                        <li>Solicitar la eliminación de sus datos</li>
                        <li>Oponerse al tratamiento de sus datos</li>
                        <li>Exportar sus datos en formato legible</li>
                    </ul>
                </section>

                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        5. Limitación de Responsabilidad
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        El sistema se proporciona "tal cual" sin garantías de
                        ningún tipo. No garantizamos que el sistema esté libre
                        de errores o disponible continuamente. No seremos
                        responsables por cualquier interrupción del servicio.
                    </p>
                </section>

                <section
                    class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        6. Contacto
                    </h2>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Si tiene preguntas sobre estos términos, contacte al
                        administrador del sistema o envíe un correo a
                        soporte@empresa.com.
                    </p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
