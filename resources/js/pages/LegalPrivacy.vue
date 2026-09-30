<script setup lang="ts">
import LegalField from '@/components/legal/LegalField.vue';
import LegalLayout from '@/layouts/LegalLayout.vue';
import type { LegalInfo } from '@/types/legal';

defineProps<{ legal: LegalInfo }>();
</script>

<template>
    <LegalLayout
        title="Política de tratamiento de datos personales"
        :legal="legal"
    >
        <p>
            Esta política explica cómo se recolectan, usan, almacenan y
            protegen los datos personales en {{ legal.product_name }}, en
            cumplimiento de la Ley 1581 de 2012, el Decreto 1377 de 2013
            (compilado en el Decreto 1074 de 2015) y demás normas colombianas
            sobre protección de datos personales.
        </p>

        <section aria-labelledby="p-responsable">
            <h2 id="p-responsable">1. Responsable del tratamiento</h2>
            <ul>
                <li>
                    <strong>Razón social:</strong>
                    <LegalField
                        :value="legal.company_name"
                        label="razón social"
                    />
                </li>
                <li>
                    <strong>NIT:</strong>
                    <LegalField :value="legal.company_nit" label="NIT" />
                </li>
                <li>
                    <strong>Domicilio y dirección:</strong>
                    <LegalField :value="legal.address" label="dirección" />,
                    <LegalField :value="legal.city" label="ciudad" />,
                    {{ legal.country }}
                </li>
                <li>
                    <strong>Correo para asuntos de datos personales:</strong>
                    <LegalField
                        :value="legal.privacy_email"
                        label="correo de privacidad"
                        email
                    />
                </li>
                <li>
                    <strong>Teléfono:</strong>
                    <LegalField :value="legal.phone" label="teléfono" />
                </li>
            </ul>
        </section>

        <section aria-labelledby="p-roles">
            <h2 id="p-roles">2. En qué calidad tratamos sus datos</h2>
            <p>
                {{ legal.product_name }} es un software de gestión de ausencias,
                vacaciones, horas extra y documentos de recursos humanos que se
                ofrece a empresas (en adelante, "la Empresa cliente").
            </p>
            <ul>
                <li>
                    <strong>Como responsable:</strong> tratamos los datos de
                    las personas que contratan el servicio o se registran en
                    nombre de una Empresa cliente (datos de contacto, de
                    facturación y de la cuenta).
                </li>
                <li>
                    <strong>Como encargado:</strong> tratamos los datos de los
                    colaboradores que cada Empresa cliente carga en la
                    plataforma, por cuenta y según las instrucciones de esa
                    Empresa. En este caso, <strong>la Empresa cliente es la
                    responsable</strong> del tratamiento y la encargada de
                    obtener la autorización de sus colaboradores. Si usted es
                    colaborador de una Empresa cliente, puede ejercer sus
                    derechos ante su empleador o ante nosotros, y trasladaremos
                    la solicitud a la Empresa correspondiente.
                </li>
            </ul>
        </section>

        <section aria-labelledby="p-datos">
            <h2 id="p-datos">3. Datos que tratamos</h2>
            <h3>Colaboradores (cargados por la Empresa cliente)</h3>
            <ul>
                <li>
                    Identificación y contacto: nombres, apellidos, número de
                    identificación, correo electrónico, teléfono, género,
                    fecha de nacimiento y fotografía (opcional).
                </li>
                <li>
                    Datos laborales: fecha de ingreso, área, cargo, rol dentro
                    de la plataforma y jefe de área.
                </li>
                <li>
                    Novedades: solicitudes de ausencias, vacaciones, permisos y
                    horas extra, con sus fechas, estados, aprobaciones y los
                    soportes adjuntos que el colaborador o la Empresa carguen.
                </li>
                <li>
                    Documentos de recursos humanos que la Empresa cliente
                    decida gestionar en la plataforma.
                </li>
            </ul>
            <h3>Clientes y contactos comerciales</h3>
            <ul>
                <li>
                    Razón social, NIT, correo de la empresa, nombre y teléfono
                    de la persona de contacto, plan contratado e historial de
                    pagos.
                </li>
                <li>
                    No almacenamos números de tarjeta. Cuando se habiliten los
                    pagos en línea, se procesarán a través de la pasarela de
                    pago elegida, que tiene su propia política de privacidad.
                </li>
            </ul>
            <h3>Datos técnicos</h3>
            <ul>
                <li>
                    Dirección IP, navegador y registros de sesión y de
                    actividad necesarios para la seguridad y la auditoría del
                    sistema.
                </li>
                <li>
                    Cookies técnicas y de preferencia, descritas en la
                    <a href="/legal/cookies">Política de cookies</a>. No
                    usamos cookies publicitarias ni de analítica de terceros.
                </li>
            </ul>
        </section>

        <section aria-labelledby="p-sensibles">
            <h2 id="p-sensibles">4. Datos sensibles y de menores</h2>
            <p>
                Algunos soportes de ausencias (por ejemplo, incapacidades
                médicas) y la fotografía pueden contener datos sensibles, como
                datos de salud o biométricos. Estos datos solo se tratan cuando
                son necesarios para la gestión laboral, con autorización previa
                y expresa del titular. <strong>El titular no está obligado a
                entregar datos sensibles</strong> y puede negarse a hacerlo.
                Se recomienda cargar solo la información estrictamente
                necesaria.
            </p>
            <p>
                La plataforma no está dirigida a menores de edad y no
                recolectamos intencionalmente sus datos.
            </p>
        </section>

        <section aria-labelledby="p-finalidades">
            <h2 id="p-finalidades">5. Finalidades</h2>
            <ul>
                <li>
                    Prestar el servicio: registrar, aprobar y consultar
                    ausencias, vacaciones, permisos, horas extra y documentos.
                </li>
                <li>
                    Enviar notificaciones operativas sobre solicitudes,
                    aprobaciones y la cuenta.
                </li>
                <li>
                    Generar los reportes que solicite la Empresa cliente.
                </li>
                <li>
                    Gestionar la relación comercial: licencias, facturación,
                    soporte y cobro.
                </li>
                <li>
                    Proteger la seguridad del sistema, prevenir fraudes y
                    mantener registros de auditoría.
                </li>
                <li>
                    Cumplir obligaciones legales y atender requerimientos de
                    autoridades competentes.
                </li>
            </ul>
            <p>
                No vendemos datos personales ni los usamos para publicidad de
                terceros.
            </p>
        </section>

        <section aria-labelledby="p-terceros">
            <h2 id="p-terceros">6. Con quién compartimos los datos</h2>
            <ul>
                <li>
                    Con la Empresa cliente a la que pertenece el colaborador y
                    sus usuarios autorizados, según los permisos de cada rol.
                </li>
                <li>
                    Con proveedores que nos prestan servicios de alojamiento,
                    envío de correo, almacenamiento o pagos, que actúan como
                    encargados y están obligados a proteger la información.
                </li>
                <li>
                    Con el proveedor de tipografías web (Bunny Fonts), que
                    recibe la dirección IP del navegador al cargar las fuentes
                    del sitio.
                </li>
                <li>
                    Con el personal de soporte de {{ legal.product_name }}, que
                    puede acceder a la cuenta de una Empresa cliente solo para
                    resolver incidencias o prestar soporte. Esos accesos quedan
                    registrados.
                </li>
                <li>Con autoridades, cuando la ley lo exija.</li>
            </ul>
            <p>
                Si algún proveedor está ubicado fuera de Colombia, la
                transmisión o transferencia internacional se hará conforme al
                artículo 26 de la Ley 1581 de 2012.
            </p>
        </section>

        <section aria-labelledby="p-derechos">
            <h2 id="p-derechos">7. Derechos del titular</h2>
            <p>
                Según el artículo 8 de la Ley 1581 de 2012, usted tiene derecho
                a:
            </p>
            <ul>
                <li>Conocer, actualizar y rectificar sus datos personales.</li>
                <li>
                    Solicitar prueba de la autorización otorgada, salvo cuando
                    la ley no la exija.
                </li>
                <li>Ser informado sobre el uso que se da a sus datos.</li>
                <li>
                    Presentar quejas ante la Superintendencia de Industria y
                    Comercio (SIC) después de haber agotado el trámite de
                    consulta o reclamo ante el responsable o el encargado.
                </li>
                <li>
                    Revocar la autorización o solicitar la supresión de sus
                    datos, cuando no exista un deber legal o contractual de
                    conservarlos.
                </li>
                <li>Acceder gratuitamente a sus datos personales.</li>
            </ul>
        </section>

        <section aria-labelledby="p-procedimiento">
            <h2 id="p-procedimiento">8. Cómo ejercer sus derechos</h2>
            <p>
                Escriba a
                <LegalField
                    :value="legal.privacy_email"
                    label="correo de privacidad"
                    email
                />
                indicando su nombre, su número de identificación, una
                descripción de la solicitud, la dirección o el correo de
                respuesta y los documentos que quiera hacer valer.
            </p>
            <ul>
                <li>
                    <strong>Consultas:</strong> se responden en un máximo de 10
                    días hábiles. Si no es posible hacerlo en ese plazo, le
                    informaremos el motivo y responderemos a más tardar 5 días
                    hábiles después (art. 14, Ley 1581 de 2012).
                </li>
                <li>
                    <strong>Reclamos</strong> (corrección, actualización,
                    supresión o incumplimiento): se responden en un máximo de
                    15 días hábiles, prorrogables hasta 8 días hábiles más con
                    aviso previo. Si el reclamo está incompleto, le pediremos
                    completarlo dentro de los 5 días siguientes. Si pasan 2
                    meses sin respuesta suya, se entenderá que desistió del
                    reclamo (art. 15, Ley 1581 de 2012).
                </li>
            </ul>
        </section>

        <section aria-labelledby="p-seguridad">
            <h2 id="p-seguridad">9. Seguridad</h2>
            <p>
                Aplicamos medidas técnicas y administrativas razonables, entre
                ellas: contraseñas almacenadas con funciones de hash, control
                de acceso por roles, separación de la información por empresa,
                autenticación en dos pasos opcional y registro de auditoría de
                los accesos de soporte. Ningún sistema es completamente infalible; si
                ocurre un incidente de seguridad que afecte sus datos, lo
                informaremos a la SIC y a los afectados según la ley.
            </p>
        </section>

        <section aria-labelledby="p-conservacion">
            <h2 id="p-conservacion">10. Conservación</h2>
            <p>
                Conservamos los datos mientras esté vigente la relación con la
                Empresa cliente y durante el tiempo adicional que exijan las
                normas laborales, contables o tributarias. Al terminar el
                contrato, la Empresa cliente puede pedir la exportación de su
                información. Después, la información se suprime o se anonimiza
                en un plazo de
                <span class="pending">[PENDIENTE: plazo en días]</span>, salvo
                que la ley obligue a conservarla por más tiempo.
            </p>
        </section>

        <section aria-labelledby="p-vigencia">
            <h2 id="p-vigencia">11. Vigencia y cambios</h2>
            <p>
                Esta política rige desde la fecha de actualización indicada al
                inicio. Las bases de datos estarán vigentes mientras se cumplan
                las finalidades descritas. Si hacemos cambios sustanciales, los
                comunicaremos antes de aplicarlos y, cuando corresponda,
                pediremos una nueva autorización.
            </p>
        </section>
    </LegalLayout>
</template>
