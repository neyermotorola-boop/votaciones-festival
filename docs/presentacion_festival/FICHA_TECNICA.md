# Sistema de Votaciones · Festival de la Leyenda Vallenata

**Ficha para la organización del Festival**
Propuesta tecnológica · Proyecto académico de Desarrollo Web

---

## ¿Qué es?

Una plataforma web propia para que el público vote por sus favoritos en las categorías que defina el Festival,
desde cualquier celular o computador, con la identidad visual de la Fundación.

Video de presentación (2:54): *[enlace al video]*

## ¿Cómo funciona para el público?

1. **Se registra** con nombre, correo y documento de identidad. Una persona = una cuenta.
2. **Elige la categoría y su favorito.** Cada participante puede mostrarse con fotografía y nombre.
3. **Confirma** su elección y el voto queda registrado.
4. **Recibe un comprobante** en su correo con número de registro, categoría y opción elegida.

## ¿Cómo funciona para la organización?

- **Panel de administración** con ediciones, categorías, votaciones, participantes y gráficos de resultados.
- **Cada edición se configura sin tocar código**: categorías, votaciones, fechas de apertura y cierre.
- **Usuarios del equipo con permisos individuales**: el administrador decide qué puede hacer cada gestor.
- **Resultados públicos solo cuando la organización lo decide.**
- **Exportación a Excel** de votos, votaciones, categorías y más, lista para auditorías e informes.
- **Bitácora** de cada acción administrativa.

## Fidelidad del voto: un voto por persona en cada categoría

El sistema lo controla en tres niveles:

| Nivel | Qué hace |
|---|---|
| 1. Valida al votante | Exige sesión iniciada, documento único con estructura real y votación abierta |
| 2. Bloquea solicitudes simultáneas | Procesa una sola solicitud a la vez por categoría |
| 3. La base de datos rechaza duplicados | Restricción única por documento, aunque la persona cambie de cuenta |

**Prueba real:** 20 intentos de voto simultáneos de una misma persona → **1 registrado y 19 rechazados**, sin ningún
voto duplicado en la base de datos.

## Seguridad

- Contraseñas guardadas cifradas, nunca en texto plano.
- Formularios protegidos contra suplantación (CSRF).
- Límite de intentos en el ingreso, el registro y el voto.
- Permisos por rol: administrador, gestor y votante.
- Recuperación de contraseña con enlace de un solo uso que vence en 60 minutos.
- Correos enviados con conexión cifrada (TLS).
- En las páginas públicas nunca se muestra quién votó por qué opción.

## Sobre la validación de documentos

El sistema verifica que cada documento tenga la **estructura de un documento colombiano real** según su tipo (cédula,
tarjeta de identidad, cédula de extranjería o pasaporte) y rechaza números inventados. Confirmar que una cédula
**existe y pertenece a esa persona** requiere consultar a la Registraduría mediante un proveedor autorizado, que es un
servicio pago con convenio. El sistema está preparado para conectarlo si el Festival lo contrata.

## Respaldo técnico

- **92 pruebas automáticas**, todas aprobadas.
- Código disponible en GitHub: https://github.com/neyermotorola-boop/votaciones-festival
- Instalación con Docker en un solo comando.
- Tecnologías: Laravel (PHP), MySQL, Bootstrap.
- Funciona en celulares, tabletas y computadores, con modo claro y oscuro.

## Para poner en producción

Antes de usarlo con público real, el Festival debería:

1. Contratar un servidor o hosting con dominio propio y certificado HTTPS.
2. Configurar un servicio de correo (Brevo o Gmail sirven para empezar; para alto volumen, un plan pago).
3. Validar con su área jurídica el texto de **tratamiento de datos personales** (hoy marcado como pendiente).
4. Cambiar las cuentas y contraseñas de demostración.
5. Definir las categorías, participantes y reglas reales de la edición.

> Los participantes y las categorías que aparecen en el video y en los datos de prueba son **ejemplos ficticios**.
