# Sistema Web de Votaciones – Festival de la Leyenda Vallenata 2027

Aplicación web de votaciones para el Festival de la Leyenda Vallenata. Permite al público votar por categorías
y al administrador gestionar ediciones, categorías, votaciones y opciones.
Modelo de datos: **Edición → Categoría → Votación (subevento) → Opción → Voto**.

> **Regla central (Entrega 3):** cada usuario puede emitir **un solo voto por categoría y edición**. Puede votar
> en otras categorías, pero no repetir en la misma, aunque ésta tenga varios subeventos (votaciones).

> Las categorías y opciones sembradas son **ejemplos ilustrativos**; las reglas no confirmadas por el cliente están
> marcadas como *pendiente de validación*.

## Decisión tecnológica: Laravel/PHP frente a Django/Python
La guía de la asignatura menciona Django y Python; este proyecto se desarrolló con **Laravel (PHP)**, tecnología conversada
y aceptada con el docente. Se documenta la equivalencia para que la diferencia sea explícita:

- **Equivalencia de componentes.** Todos los elementos que pide la guía existen con la misma responsabilidad:

| Elemento de la guía (Django/Python) | Equivalente en este proyecto (Laravel/PHP) |
|---|---|
| Framework web MVC/MTV | Laravel 13 (controladores + vistas Blade) |
| Modelos y ORM de Django | Modelos Eloquent (`app/Models`) |
| Migraciones (`makemigrations` / `migrate`) | Migraciones (`php artisan migrate`) |
| `requirements.txt` | `composer.json` + `composer.lock` |
| Sitio de administración | Panel `/admin` propio (ediciones, categorías, votaciones, opciones, reportes) |
| Pruebas (`manage.py test`) | PHPUnit (`php artisan test`) |
| Servidor de desarrollo (`runserver`) | `php artisan serve` / Docker |
| MySQL como base de datos | MySQL 8.4 (el mismo motor que pide la guía) |

- **Qué se cumple igual:** base de datos MySQL, arquitectura por capas, restricciones en la base de datos, API GET que
  devuelve JSON, pruebas automatizadas, repositorio con instrucciones y contenedores Docker.
- **Diferencia reconocida:** el lenguaje es PHP en lugar de Python. Los requisitos funcionales y de datos (reglas de
  votación, restricción única, API, pruebas CP-01 a CP-10) son independientes del lenguaje y se cumplen en su totalidad.
  La guía de estudio para la sustentación (`docs/entrega_final/Guia_Explicacion_Votaciones_Festival.docx`) explica
  el funcionamiento de Laravel, MySQL y Docker, con ventajas y limitaciones de la elección.

## Tecnologías y versiones
| Componente | Versión |
|---|---|
| PHP | 8.3 (Python no se usa; ver «Decisión tecnológica») |
| Laravel (framework) | 13 |
| MySQL | 8.4 (SQLite como opción local sin configuración) |
| Bootstrap / Bootstrap Icons | 5.3 / 1.11 (por CDN) |
| Docker / Docker Compose | para ejecutar app + MySQL + phpMyAdmin |

**Dependencias:** se declaran en `composer.json` (y quedan fijadas en `composer.lock`), equivalente a `requirements.txt`
en Python. Se instalan con `composer install`. Librería adicional: `openspout/openspout` (reportes Excel).

## Instalación y ejecución

### Opción A: Docker (recomendada, incluye MySQL)
```bash
docker compose up --build
```
- Sitio: http://localhost:8080
- phpMyAdmin: http://localhost:8081
- Buzón de correos de prueba (Mailpit): http://localhost:8025
- Al iniciar, el contenedor instala dependencias, genera la clave, **ejecuta las migraciones** y **siembra los datos de prueba**.

> Las credenciales de MySQL de `docker-compose.yml` son **ficticias y solo para desarrollo local**
> (usuario de aplicación `votaciones`). En un entorno real se definen en un `.env` propio que no se sube al repositorio.

### Opción B: sin Docker (PHP 8.3 + Composer, base SQLite)
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve          # http://127.0.0.1:8000
```

### Configurar MySQL sin Docker
Copia `.env.example` a `.env` y cambia el bloque de base de datos (sin subir tus credenciales al repositorio):
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=votaciones
DB_USERNAME=<tu_usuario>
DB_PASSWORD=<tu_contraseña>
```
Luego: `php artisan migrate --seed`.

### Migraciones y datos de prueba
```bash
php artisan migrate        # crea las tablas (incluye la restricción única de votos)
php artisan db:seed        # ediciones, categorías, votaciones y opciones de ejemplo (idempotente)
```

## Cuentas de prueba (datos ficticios)
| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@festival.test | Admin12345 |
| Votante | votante@festival.test | Votante12345 |

## API GET
`GET /api/categorias/` (responde también en `/api/categorias`) devuelve en JSON las categorías habilitadas con sus
votaciones y opciones, leídas desde MySQL. Filtro opcional: `?edicion_id=1`.

```bash
curl http://localhost:8080/api/categorias/
```
Ejemplo de respuesta (resumida):
```json
{
  "total": 2,
  "data": [
    { "id": 1, "nombre": "Música",
      "edicion": { "id": 1, "nombre": "Festival de la Leyenda Vallenata 2027", "anio": 2027, "activa": true },
      "votaciones": [ { "id": 1, "titulo": "Canción favorita del público", "estado": "abierta",
        "opciones": [ { "id": 1, "nombre": "Canción A" }, { "id": 2, "nombre": "Canción B" } ] } ] }
  ]
}
```
Si el servidor corre en `localhost`, la API solo es accesible desde el equipo que lo ejecuta; para probarla se clona
el repositorio y se levanta con los comandos anteriores.

## Dónde está cada parte (rutas de archivos)
| Elemento | Archivo |
|---|---|
| API GET (ruta) | `routes/api.php` |
| API GET (lógica) | `app/Http/Controllers/Api/CategoriaApiController.php` |
| Control de unicidad del voto | `app/Http/Controllers/PublicoController.php` (método `votar`) |
| Restricción única en MySQL | `database/migrations/2026_11_08_000000_add_edicion_categoria_to_votos_table.php` |
| Modelo de voto | `app/Models/Voto.php` |
| Historial del votante | `PublicoController::misVotos` · vista `resources/views/mis-votos.blade.php` |
| Consulta administrativa de votos | `app/Http/Controllers/Admin/VotoController.php` |
| Pruebas CP-01 a CP-10 | `tests/Feature/Entrega3Test.php` |

## Seguridad
- Voto solo con sesión iniciada; formularios `POST` con protección **CSRF** (sin token, la respuesta es 419).
- El servidor valida que la categoría esté habilitada, la votación abierta y que la opción pertenezca a esa votación.
- Un voto por usuario, categoría y edición: validación en el servidor + bloqueo de la categoría en una transacción +
  restricción **UNIQUE (user_id, edicion_id, categoria_id)** en MySQL que rechaza duplicados concurrentes.
- Límite de intentos (throttle) en login, registro y voto. El archivo `.env` no se sube (`.gitignore`).

## Pruebas
```bash
php artisan test
```
`tests/Feature/Entrega3Test.php` contiene los casos CP-01 a CP-10 de la guía y `Entrega4Test.php` la administración dinámica de ediciones.

## Otras reglas implementadas
RN-02 solo se vota en votaciones abiertas y dentro de su periodo · RN-03 solo usuarios autenticados ·
RN-04 mínimo 2 opciones para abrir · RN-08 resultados públicos solo si el administrador los habilita ·
RN-09 no se elimina lo que ya tiene votos · bitácora de acciones administrativas · reporte en Excel por edición y categoría.

## Administración dinámica de ediciones (sin modificar código)
Una nueva edición del festival se configura **solo desde el panel de administración** (`/admin`), sin tocar el código
ni ejecutar seeders:

1. **Ediciones → Nueva edición.** Nombre, año y descripción. El año propuesto es el siguiente al último existente.
   Opcionalmente se elige **«Copiar estructura de una edición anterior»**: copia categorías, votaciones y opciones;
   las votaciones copiadas quedan en *borrador* y **sin votos**, y los votos de la edición anterior no se tocan.
2. Marcar **Edición activa** para que se muestre en el sitio público (solo una edición está activa a la vez).
3. **Categorías → Nueva categoría** dentro de la edición (o habilitar/deshabilitar las copiadas).
4. **Votaciones → Nueva votación** (subevento) con sus **opciones**, fechas de apertura/cierre y resultados públicos.
   Una votación necesita mínimo 2 opciones para abrirse.
5. La API (`/api/categorias?edicion_id=<id>`) y el reporte Excel ya incluyen la nueva edición automáticamente.

La regla un voto por usuario, categoría y edición se aplica **por edición**: el mismo usuario puede votar en la categoría
«Música» de 2027 y en la de 2028, pero no dos veces en la misma. Integridad adicional: no se puede cambiar la edición de
una categoría que ya tiene votos, y el modelo `Voto` deriva siempre la edición y la categoría de su votación.
Verificado por `tests/Feature/Entrega4Test.php`.

## Aportes del equipo
> Completar antes de entregar: una fila por integrante, con tareas concretas y verificables (commits, módulos, pruebas).

| Integrante | Rol | Aportes principales |
|---|---|---|
| _Nombre 1_ | _p. ej. Backend_ | _p. ej. reglas de votación, restricción única, API GET_ |
| _Nombre 2_ | _p. ej. Frontend_ | _p. ej. vistas públicas, historial «Mis votos»_ |
| _Nombre 3_ | _p. ej. Pruebas y documentación_ | _p. ej. pruebas CP-01 a CP-10, concurrencia, informe_ |

Los aportes individuales también se pueden comprobar con `git shortlog -sn` y `git log --author="<nombre>"`.

## Pruebas automatizadas y de concurrencia
```bash
php artisan test                       # 92 pruebas: CP-01 a CP-10, administración, reglas, reportes, correos, fotos, Excel, tema y documentos
./vendor/bin/phpunit --testdox         # lista cada prueba por nombre
```
**Se deben repetir después de cualquier modificación** (código, migraciones o reglas) y antes de cada entrega.

Prueba de concurrencia (CP-09) contra el servidor real, N solicitudes simultáneas del mismo usuario a la misma categoría:
```bash
php docs/entrega_final/prueba_concurrencia.php <id_votacion> <correo_nuevo@festival.test> 10
```
Resultado esperado: **1 voto registrado y N-1 rechazados**, y una sola fila en la tabla `votos`
(`SELECT user_id, categoria_id, COUNT(*) FROM votos GROUP BY user_id, categoria_id, edicion_id HAVING COUNT(*) > 1;`
debe devolver cero filas). Última ejecución (servidor local, 10 solicitudes): 1 registrado, 9 rechazados, 1 fila en la BD.

## Guion de demostración funcional
1. **Administración dinámica:** ingresar como administrador → *Ediciones → Nueva edición* (por ejemplo 2028, copiando
   la estructura de 2027) → activarla → crear/abrir una votación con 2 opciones. Mostrar que aparece en el sitio público
   sin tocar el código.
2. **Registro de votos:** registrarse o ingresar como votante → elegir una opción → confirmar → «Voto registrado».
3. **Validaciones:** intentar votar otra vez en la misma categoría (rechazado con mensaje); votar en otra categoría
   (permitido); intentar votar sin sesión (pide iniciar sesión); mostrar que una votación cerrada o en borrador no recibe votos.
4. **Consulta de resultados:** *Mis votos* (historial del votante), *Admin → Votos* (consulta administrativa con filtros),
   *Admin → Reportes → Excel* y la API `GET /api/categorias/`.
5. **Pruebas:** en la terminal, `php artisan test` (29 de 29) y, opcionalmente, la prueba de concurrencia.

## Usuarios con permisos y gráficos de votación (Eje 4)
El administrador gestiona usuarios en **Admin → Usuarios y permisos** (`/admin/usuarios`):
- **Roles:** *Administrador* (puede todo), *Gestor* (solo lo que se le asigna) y *Votante* (solo vota).
- **Permisos del gestor** (se marcan al crear o editar el usuario): gestionar ediciones · crear y editar categorías ·
  gestionar votaciones y opciones · ver votos y gráficos de resultados · descargar reportes Excel.
  La barra lateral y las rutas respetan esos permisos (sin permiso: error 403). Un gestor sin ningún permiso no entra al panel.
- Las cuentas se **activan/desactivan** sin borrarlas (conservan su historial); un usuario desactivado no puede iniciar sesión.
  El administrador no puede desactivarse ni quitarse el rol a sí mismo.
- Implementación: columnas `permisos` (JSON) y `activo` en `users` (migración `2026_11_19_000000_add_permisos_to_users_table.php`),
  middleware `PermisoAdmin` (`app/Http/Middleware`), `Admin/UsuarioController` y `User::puede()`.

**Gráficos** (`/admin/graficos`, permiso *resultados*): votos por opción (barras con porcentaje), votos por categoría
(dona), votos por día (línea, 14 días), y totales y participación; se filtran por edición y votación. El panel principal
muestra además votos por día y por categoría. Datos reales de MySQL (`app/Services/EstadisticasVotos.php`); gráficos con Chart.js.

Cuenta de prueba del gestor (ficticia): `gestor@festival.test` / `Gestor12345` (permisos: categorías y resultados).
Pruebas: `tests/Feature/UsuariosPermisosTest.php`.

## Una persona = una cuenta = un voto por categoría (documento y correo)
Para evitar que alguien vote varias veces creando cuentas nuevas:
- **Documento con estructura real** (`app/Rules/DocumentoValido.php`), según el tipo:
  | Tipo | Regla |
  |---|---|
  | CC · Cédula de ciudadanía | 5 a 10 dígitos, sin empezar por 0; las de 10 dígitos (expedidas desde 2003) empiezan por 1 |
  | TI · Tarjeta de identidad | 10 u 11 dígitos (NUIP) |
  | CE · Cédula de extranjería | 5 a 10 dígitos |
  | PA · Pasaporte | 5 a 15 letras o números, con al menos un número |

  Además se rechazan números inventados: el mismo dígito repetido (`11111111`) y secuencias (`123456789`, `987654321`).
  El formulario muestra el formato esperado según el tipo y abre el teclado numérico en celulares.
  **Alcance:** es una validación de plausibilidad. Confirmar que una cédula *existe y pertenece a esa persona* exige consultar
  a la Registraduría, que no ofrece un servicio público gratuito; se haría con un proveedor autorizado (servicio pago con convenio),
  conectándolo en `DocumentoValido::validate()`.
- **Registro con documento:** tipo (CC, CE, TI, PA) y número. Se normaliza (sin espacios, puntos ni guiones) y es **único**:
  un documento no puede tener dos cuentas. El votante **no puede cambiarlo** después (solo un administrador puede corregirlo).
- **Correo real único:** además del correo, se guarda `email_canonico` (único), que ignora mayúsculas, alias `+etiqueta` y puntos de
  Gmail: `juan.perez+2@gmail.com` y `juanperez@gmail.com` se consideran la misma persona.
- **El voto guarda el documento** de quien votó (`votos.documento`) con restricción única `(edicion_id, categoria_id, documento)`:
  MySQL rechaza un segundo voto del mismo documento aunque se cambie de cuenta. Se suma a la restricción `(user_id, edicion_id, categoria_id)`.
- **Cuentas anteriores sin documento** deben registrarlo (`/mi-documento`) antes de poder votar.
- **Trazabilidad:** *Admin → Votos* muestra el documento de cada voto y permite buscar por nombre, correo o documento; el reporte Excel
  incluye la columna «Documento». En las páginas públicas nunca se muestra quién votó por qué opción.
- Implementación: migración `2026_11_20_000000_add_documento_to_users_and_votos.php`, `User::reglasDocumento()`, `User::canonizarEmail()`,
  `PublicoController::yaVoto()`, `DocumentoController`. Pruebas: `tests/Feature/DocumentoUnicoTest.php` y `tests/Feature/DocumentoValidoTest.php`.
- Nota de privacidad: el documento es un dato personal; su tratamiento debe estar cubierto por la política de datos (el texto legal está
  *pendiente de validación* con el cliente).

## Identidad visual, correos, fotos, Excel y rendimiento

### Colores de marca y logo
| Uso | Color | Hex |
|---|---|---|
| Principal (botones, encabezado) | Rojo Festival | `#D3121B` |
| Acento (destacados, "Votar") | Amarillo cañaguate | `#F2B705` |
| Éxito ("Voto registrado") | Verde campo | `#2E7D32` |
| Error / rechazo | Rojo vino | `#8E1B1B` |
| Fondo | Crema | `#FAF7F0` |

Definidos como variables en `public/css/app.css` (`--brand`, `--gold`, `--green`, `--red`, `--bg`), en el tema de correo
`resources/views/vendor/mail/html/themes/festival.css` y en los encabezados de los Excel. El logo está en `public/img/logo-festival.png`.

### Tema claro / oscuro
Botón sol/luna en el encabezado del sitio, en el panel de administración (barra lateral) y en las pantallas de acceso.
La elección se guarda en el navegador de cada usuario; si no ha elegido, se usa el modo del sistema operativo. El tema
se aplica antes de pintar la página (sin destellos) y las gráficas se adaptan. Archivos: `public/js/tema.js`,
`resources/views/partials/tema-head.blade.php` y el bloque `[data-theme="dark"]` de `public/css/app.css`.

### Notificaciones por correo
Se envían en cola (servicio `queue` de Docker; sin Docker: `php artisan queue:work`). Si el servidor de correo falla,
el registro o el voto **no** se afectan.
- **Bienvenida** al crear la cuenta.
- **Comprobante de voto** (categoría, votación, opción, fecha y número de comprobante).
- **Recuperación de contraseña** con enlace seguro de un solo uso que vence en 60 minutos ("¿Olvidaste tu contraseña?" en
  el ingreso; la ruta anterior `/recuperar` redirige a este flujo). Nadie puede cambiar la contraseña de otra persona
  solo con conocer su correo.

Se pueden desactivar con `NOTIFICACIONES_CORREO=false`. En desarrollo todos los correos se ven en Mailpit.

**Enviar correos reales con un servicio gratuito** (conexión cifrada STARTTLS, puerto 587). Edita `.env` y reinicia con
`docker compose restart app queue`:

- **Brevo** (300 correos/día gratis): crea la cuenta, verifica el remitente y genera una clave SMTP.
  `MAIL_HOST=smtp-relay.brevo.com`, `MAIL_PORT=587`, `MAIL_USERNAME=<usuario SMTP>`, `MAIL_PASSWORD=<clave SMTP>`.
- **Gmail** (≈500 correos/día): activa la verificación en dos pasos y crea una *contraseña de aplicación*.
  `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_USERNAME=tucuenta@gmail.com`, `MAIL_PASSWORD=<contraseña de aplicación>`.

### Participantes con foto
En **Categorías → Editar** activa *"Los participantes llevan foto"*. En esas categorías la foto es obligatoria al agregar
una opción y se muestra junto al nombre al votar, al confirmar, en los resultados y en la consulta de votos. Las demás
categorías siguen funcionando solo con texto. Formatos JPG, PNG o WEBP, máx. 2 MB (`IMAGEN_MAX_KB`); se guardan en
`public/uploads/`. Cada opción se puede editar (cambiar o quitar foto); si ya tiene votos, su nombre no se puede cambiar.

### Exportar a Excel
Botón **Exportar Excel** en Ediciones, Categorías, Votaciones, opciones de cada votación y **Votos** (respeta los filtros
de edición, categoría y votante). Cada exportación exige el mismo permiso que su sección; **Votantes** y **Bitácora**
solo los exporta el administrador.

### Rendimiento
- OPcache y caché de rutas de PHP en la imagen Docker; `vendor/` en un volumen de Docker (en Windows/macOS leerlo desde
  el disco compartido era lo más lento).
- Caché de navegador y compresión gzip para CSS e imágenes; el CSS se versiona automáticamente.
- Imagen de portada optimizada (228 KB → 25 KB).
- Las pruebas dentro de Docker (`docker exec votaciones_app php artisan test`) usan SQLite en memoria y nunca tocan MySQL.

