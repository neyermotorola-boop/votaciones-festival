# Sistema Web de Votaciones – Festival de la Leyenda Vallenata 2027

Laravel 13 + MySQL + Bootstrap, ejecutable con Docker. Modelo configurable:
**Edición → Categoría → Votación → Opción → Voto**.

> Las categorías y opciones sembradas son **ejemplos ilustrativos**; las reglas no confirmadas por el cliente están marcadas como *pendiente de validación*.

## Con Docker (recomendado)
```bash
docker compose up --build
```
- Sitio: http://localhost:8080
- phpMyAdmin: http://localhost:8081 (usuario `votaciones` / `secret`)

La primera vez instala dependencias, genera la clave, migra y siembra datos demo.

## Cuentas demo
| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@festival.test | Admin12345 |
| Votante | votante@festival.test | Votante12345 |

(Solo para desarrollo; cámbialas antes de cualquier despliegue real.)

## Sin Docker (PHP 8.3 + Composer)
```bash
composer install
php artisan migrate --seed
php artisan serve
```
Usa SQLite por defecto (`.env`).

## Pruebas
```bash
php artisan test
```

## Reglas implementadas
RN-02 solo se vota en votaciones abiertas y dentro de su periodo · RN-03 solo usuarios autenticados ·
RN-04 mínimo 2 opciones para abrir · RN-06 límite de votos por usuario (configurable) ·
RN-08 resultados públicos solo si el administrador los habilita · RN-09 no se elimina lo que ya tiene votos ·
Bitácora de acciones administrativas · límite de intentos (throttle) en login/registro/voto.
