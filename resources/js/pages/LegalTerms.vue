<script setup lang="ts">
import LegalField from '@/components/legal/LegalField.vue';
import LegalLayout from '@/layouts/LegalLayout.vue';
import type { LegalInfo } from '@/types/legal';

defineProps<{ legal: LegalInfo }>();
</script>

<template>
    <LegalLayout title="Términos y condiciones de uso" :legal="legal">
        <p>
            Estos términos regulan el acceso y el uso de
            {{ legal.product_name }} (en adelante, "la plataforma"), ofrecida
            por
            <LegalField :value="legal.company_name" label="razón social" />,
            identificada con NIT
            <LegalField :value="legal.company_nit" label="NIT" />. Al crear
            una cuenta, contratar una licencia o usar la plataforma, usted
            acepta estos términos. Si no está de acuerdo, no use la
            plataforma.
        </p>

        <section aria-labelledby="t-servicio">
            <h2 id="t-servicio">1. El servicio</h2>
            <p>
                La plataforma permite a las empresas gestionar ausencias,
                vacaciones, permisos, horas extra y documentos de recursos
                humanos de sus colaboradores. Es una herramienta de apoyo: el
                cálculo final de vacaciones, horas extra, recargos y demás
                conceptos laborales, así como su liquidación y pago, son
                responsabilidad de la empresa empleadora, que debe verificarlos
                conforme al Código Sustantivo del Trabajo y demás normas
                aplicables.
            </p>
        </section>

        <section aria-labelledby="t-cuentas">
            <h2 id="t-cuentas">2. Cuentas y usuarios</h2>
            <ul>
                <li>
                    La Empresa cliente es responsable de crear, administrar y
                    desactivar las cuentas de sus colaboradores y de asignar
                    los roles y permisos.
                </li>
                <li>
                    Cada usuario debe mantener la confidencialidad de su
                    contraseña y avisar de inmediato cualquier uso no
                    autorizado de su cuenta.
                </li>
                <li>
                    La información registrada debe ser veraz y estar
                    actualizada.
                </li>
            </ul>
        </section>

        <section aria-labelledby="t-uso">
            <h2 id="t-uso">3. Uso aceptable</h2>
            <p>El usuario se compromete a no:</p>
            <ul>
                <li>
                    Acceder o intentar acceder a información de otras personas
                    o empresas sin autorización.
                </li>
                <li>
                    Cargar contenido ilícito, malicioso o que infrinja derechos
                    de terceros.
                </li>
                <li>
                    Interferir con la seguridad o el funcionamiento de la
                    plataforma, ni hacer ingeniería inversa, extracción masiva
                    de datos o reventa del servicio sin autorización.
                </li>
            </ul>
            <p>
                Podemos suspender cuentas que incumplan estas reglas, avisando
                al titular cuando sea posible.
            </p>
        </section>

        <section aria-labelledby="t-datos">
            <h2 id="t-datos">4. Datos personales</h2>
            <p>
                El tratamiento de datos personales se rige por la
                <a href="/legal/privacy">Política de tratamiento de datos
                personales</a>. Respecto de los datos de sus colaboradores, la
                Empresa cliente actúa como responsable y declara que cuenta con
                la autorización de los titulares para cargarlos en la
                plataforma. {{ legal.product_name }} actúa como encargado y
                trata esos datos únicamente para prestar el servicio.
            </p>
        </section>

        <section aria-labelledby="t-licencias">
            <h2 id="t-licencias">5. Licencias, precios y pagos</h2>
            <ul>
                <li>
                    El acceso se otorga mediante licencias por plan y período.
                    El precio, la duración y las funciones incluidas en cada
                    plan son los que se muestran al momento de la compra.
                </li>
                <li>
                    Los precios se indican en pesos colombianos (COP) o en
                    dólares (USD), según se muestre, e incluyen o excluyen
                    impuestos según se indique expresamente.
                </li>
                <li>
                    Al vencer la licencia, el acceso puede suspenderse hasta
                    su renovación. La información se conserva según la Política
                    de tratamiento de datos.
                </li>
                <li>
                    Los reembolsos y el derecho de retracto se rigen por la
                    <a href="/legal/refunds">Política de reembolsos y
                    retracto</a>.
                </li>
            </ul>
        </section>

        <section aria-labelledby="t-propiedad">
            <h2 id="t-propiedad">6. Propiedad intelectual</h2>
            <p>
                El software, la marca, los logotipos y los diseños de
                {{ legal.product_name }} son de su titular o se usan con
                licencia. La contratación del servicio no transfiere ningún
                derecho de propiedad sobre ellos. La información que cargue la
                Empresa cliente sigue siendo suya.
            </p>
        </section>

        <section aria-labelledby="t-disponibilidad">
            <h2 id="t-disponibilidad">7. Disponibilidad y responsabilidad</h2>
            <p>
                Hacemos esfuerzos razonables para mantener la plataforma
                disponible y segura, pero pueden presentarse interrupciones por
                mantenimiento, fallas de terceros o causas de fuerza mayor. En
                la medida en que lo permita la ley, no respondemos por daños
                indirectos ni por decisiones laborales tomadas con base en
                información registrada incorrectamente por los usuarios. Nada
                de lo anterior limita los derechos que la Ley 1480 de 2011
                (Estatuto del Consumidor) reconoce cuando el cliente tiene la
                calidad de consumidor.
            </p>
        </section>

        <section aria-labelledby="t-cambios">
            <h2 id="t-cambios">8. Cambios a estos términos</h2>
            <p>
                Podemos actualizar estos términos. Los cambios sustanciales se
                informarán con anticipación razonable dentro de la plataforma o
                por correo. Si continúa usando el servicio después de que
                entren en vigor, se entenderá que los acepta.
            </p>
        </section>

        <section aria-labelledby="t-ley">
            <h2 id="t-ley">9. Ley aplicable</h2>
            <p>
                Estos términos se rigen por las leyes de la República de
                Colombia. Las diferencias se intentarán resolver primero de
                forma directa. Si no hay acuerdo, se acudirá a los jueces
                competentes de
                <LegalField :value="legal.city" label="ciudad" />, sin
                perjuicio de los derechos del consumidor ante la
                Superintendencia de Industria y Comercio.
            </p>
        </section>

        <section aria-labelledby="t-contacto">
            <h2 id="t-contacto">10. Contacto</h2>
            <p>
                Soporte:
                <LegalField
                    :value="legal.support_email"
                    label="correo de soporte"
                    email
                />
                · Teléfono:
                <LegalField :value="legal.phone" label="teléfono" /> ·
                Dirección:
                <LegalField :value="legal.address" label="dirección" />,
                <LegalField :value="legal.city" label="ciudad" />.
            </p>
        </section>
    </LegalLayout>
</template>
