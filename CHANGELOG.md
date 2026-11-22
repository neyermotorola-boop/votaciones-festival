# Registro de cambios

## Ajustes posteriores a la Entrega 4 (noviembre de 2026)

Cambios hechos después de la sustentación, sobre la versión de la entrega final (`0e40644`). El informe de
`docs/entrega_final/` se conserva tal como se entregó; la ficha para presentar el sistema al Festival está en
[`docs/presentacion_festival/`](docs/presentacion_festival/FICHA_TECNICA.md).

### Identidad visual
- Color principal **rojo Festival `#D3121B`**, acento amarillo cañaguate `#F2B705`, éxito verde `#2E7D32`,
  error rojo vino `#8E1B1B` y fondo crema `#FAF7F0`, aplicados en el sitio, los gráficos, los correos y los Excel.
- **Logo de la Fundación** en el encabezado, el pie, el panel, el acceso, los correos y el ícono del navegador
  (versión de texto oscuro para fondos claros).
- **Tema claro / oscuro** en todo el sistema, con botón sol/luna; la elección se guarda en el navegador.

### Votación
- **Participantes con foto**: cada categoría define si sus participantes llevan foto (obligatoria en ese caso). La foto
  se muestra junto al nombre al votar, al confirmar, en los resultados y en la consulta de votos.
- **Validación de documentos con estructura real** por tipo (CC, TI, CE, PA) y rechazo de números inventados
  (dígitos repetidos y secuencias). Ver «Una persona = una cuenta» en el README.

### Correo
- Notificaciones en cola: **bienvenida**, **comprobante de voto** con número de registro y **recuperación de contraseña**.
- La recuperación pasa de «contraseña temporal» a **enlace de un solo uso** que vence en 60 minutos: nadie puede cambiar
  la contraseña de otra persona solo con conocer su correo. La ruta `/recuperar` redirige al nuevo flujo.
- Mailpit para desarrollo; configuración documentada para Brevo y Gmail (gratuitos, con TLS).

### Administración
- **Exportar a Excel** en ediciones, categorías, votaciones, opciones y votos (respeta los filtros). Votantes y bitácora
  solo los exporta el administrador.
- Cada exportación exige el mismo permiso que su sección.

### Rendimiento y mantenimiento
- OPcache, `vendor/` en volumen de Docker, caché y compresión de archivos estáticos, imagen de portada optimizada.
- Las pruebas dentro de Docker usan SQLite en memoria y nunca tocan la base MySQL.
- **92 pruebas automáticas** (antes 50), todas aprobadas.

### Verificación
- Prueba de concurrencia repetida tras los cambios: **20 solicitudes simultáneas → 1 voto registrado, 19 rechazadas**,
  0 votos duplicados en la base de datos.
