<script setup lang="ts">
import LegalField from '@/components/legal/LegalField.vue';
import LegalLayout from '@/layouts/LegalLayout.vue';
import type { LegalInfo } from '@/types/legal';

defineProps<{ legal: LegalInfo }>();
</script>

<template>
    <LegalLayout title="Política de reembolsos y retracto" :legal="legal">
        <p>
            Esta política explica cuándo puede pedir la devolución del dinero
            pagado por una licencia de {{ legal.product_name }}. Se aplica
            junto con los
            <a href="/legal/terms">Términos y condiciones</a> y con la Ley 1480
            de 2011 (Estatuto del Consumidor), cuando el cliente tenga la
            calidad de consumidor.
        </p>

        <section aria-labelledby="r-retracto">
            <h2 id="r-retracto">1. Derecho de retracto</h2>
            <p>
                Las licencias se venden a distancia (en línea). Según el
                artículo 47 de la Ley 1480 de 2011, puede retractarse de la
                compra dentro de los <strong>5 días hábiles</strong> siguientes
                a la celebración del contrato.
            </p>
            <p>
                Según ese mismo artículo, el retracto no procede cuando la
                prestación del servicio ya comenzó con su acuerdo. Si activa la
                licencia y empieza a usar la plataforma, se entiende que la
                prestación comenzó.
            </p>
            <p>
                Si el retracto procede, devolveremos el valor pagado en un
                plazo máximo de <strong>30 días calendario</strong> desde su
                solicitud, sin descuentos ni retenciones.
            </p>
        </section>

        <section aria-labelledby="r-reversion">
            <h2 id="r-reversion">2. Reversión del pago</h2>
            <p>
                Si pagó con tarjeta de crédito, débito u otro medio electrónico,
                puede pedir la reversión del pago según el artículo 51 de la Ley
                1480 de 2011 y el Decreto 587 de 2016 cuando: fue víctima de
                fraude, la operación no fue solicitada, el servicio no se
                prestó o no corresponde a lo contratado, o el servicio es
                defectuoso. Debe presentar la queja ante nosotros y avisar al
                emisor de su medio de pago dentro de los
                <strong>5 días hábiles</strong> siguientes a la fecha en que
                conoció la situación.
            </p>
        </section>

        <section aria-labelledby="r-otros">
            <h2 id="r-otros">3. Otros casos</h2>
            <ul>
                <li>
                    <strong>Cobros duplicados o errores de facturación:</strong>
                    se devuelve el valor cobrado de más.
                </li>
                <li>
                    <strong>Falla grave del servicio atribuible a nosotros</strong>
                    que impida usar la plataforma: se aplican la garantía y los
                    derechos de la Ley 1480 de 2011.
                </li>
                <li>
                    <strong>Cancelación anticipada del plan:</strong>
                    <span class="pending"
                        >[PENDIENTE: definir si se reembolsa la parte no usada
                        del período]</span
                    >
                </li>
            </ul>
        </section>

        <section aria-labelledby="r-como">
            <h2 id="r-como">4. Cómo solicitarlo</h2>
            <p>
                Escriba a
                <LegalField
                    :value="legal.support_email"
                    label="correo de soporte"
                    email
                />
                con el nombre o la razón social y el NIT, la fecha de pago, el
                identificador de la transacción y el motivo. Le confirmaremos
                que recibimos la solicitud y le responderemos dentro de los 15
                días hábiles siguientes. El reembolso se hace por el mismo
                medio de pago usado en la compra.
            </p>
        </section>

        <section aria-labelledby="r-sic">
            <h2 id="r-sic">5. Quejas</h2>
            <p>
                Si no queda satisfecho con la respuesta, puede acudir a la
                Superintendencia de Industria y Comercio (www.sic.gov.co).
            </p>
        </section>
    </LegalLayout>
</template>
