[English version](README.en.md)

# Matrículas y trámites — consejo profesional (Laravel)

> **Proyecto de portfolio.** El consejo ("Consejo Profesional de Ingeniería y Arquitectura de Villa Ficticia", CPIA-VF) no existe y todos los datos son de ejemplo, generados por el seeder.

Sistema web para un colegio/consejo profesional: lleva el padrón de **profesionales matriculados**, recibe **trámites desde un portal** (visado de planos, certificado de matrícula vigente, cambio de domicilio) con PDFs adjuntos, los hace pasar por un **flujo con historial** que manejan los empleados, controla las **cuotas** mensuales y emite un **certificado PDF con código de verificación pública**.

## Por qué lo hice

Muchos avisos de **desarrollador PHP full stack** piden **Laravel, MySQL, HTML/CSS, Bootstrap, jQuery y Git**. Ya tengo varios proyectos en PHP puro; este es para mostrar Laravel usado como propone el framework: Eloquent con relaciones y scopes, migraciones/seeders/factories, Form Requests, Policies, colas, notificaciones, Sanctum y tests de feature.

## Capturas

| Panel de empleados: trámites con filtros | Detalle de un trámite con historial |
|---|---|
| ![Panel de trámites](docs/capturas/01-panel-tramites.png) | ![Detalle de trámite](docs/capturas/02-panel-detalle-tramite.png) |

| Portal del profesional: nuevo trámite | Verificación pública del certificado |
|---|---|
| ![Nuevo trámite](docs/capturas/03-portal-nuevo-tramite.png) | ![Verificar certificado](docs/capturas/04-verificar-certificado.png) |

## Qué hace

**Roles** (`profesional`, `empleado`, `admin`), con Gates para entrar a cada sección y Policies por modelo:

- **Profesional** (portal `/portal`): ve su estado de matrícula y sus cuotas, inicia trámites adjuntando PDFs, sigue el historial, responde observaciones (sube la corrección y el trámite vuelve a revisión) y descarga sus certificados.
- **Empleado** (panel `/panel`): bandeja de trámites con búsqueda (número, matrícula, apellido), filtros por estado / tipo / asignado / fechas y paginación; cambia estados dejando observación; alta y edición de profesionales con validación de CUIT; registra pagos de cuotas.
- **Admin**: todo lo anterior, más suspender / dar de baja / reactivar matrículas y anular certificados.

**Flujo de un trámite**

```
Iniciado ──► En revisión ──► Aprobado
                │   ▲    └─► Rechazado
                ▼   │
              Observado
```

- Las transiciones válidas están en el enum `EstadoTramite`; cualquier otra se rechaza.
- Observar o rechazar **exige** una observación (un "rechazado" sin motivo no le sirve al profesional).
- Cada cambio queda en `tramite_movimientos` (quién, cuándo, de qué estado a cuál y la observación).
- Al **aprobar**: un "certificado de matrícula vigente" emite el certificado (solo si la matrícula está activa y sin cuotas vencidas); un "cambio de domicilio" actualiza el domicilio del profesional. Si eso falla, el trámite **no** queda aprobado (todo va en una transacción).
- Cada cambio de estado le manda un **mail al profesional** (notificación `ShouldQueue` → cola `database` → worker). Sin Docker va al log; con Docker llega a **Mailpit**.

**Cuotas**: `php artisan cuotas:generar [AAAA-MM]` crea la cuota del mes para cada matrícula activa (es idempotente y está programado para el día 1 de cada mes). Estados pagada / pendiente / vencida; el pago lo registra un empleado (medio y fecha) desde un modal, por AJAX.

**Certificado**: PDF (dompdf) con un código tipo `K7Q4-M2XP-9HTA` (sin caracteres que se confunden, como 0/O o 1/I). Cualquiera puede verificarlo en `/verificar/{codigo}` sin login: dice si es válido, si venció o si fue anulado, y muestra el estado **actual** de la matrícula.

**API REST** chica con tokens de Sanctum (consulta de matrículas por número o CUIT y de trámites). Documentada en [docs/api.md](docs/api.md).

## Stack

- PHP 8.3, **Laravel 13** (la última estable al momento de hacerlo), Eloquent, **MySQL 8.4** (SQLite para desarrollo rápido y tests)
- Blade + **Bootstrap 5** + Bootstrap Icons + **jQuery** (filtros que se envían solos, campos que aparecen según el tipo de trámite, control de cantidad/tamaño de PDFs antes de subir, formato de CUIT mientras se escribe, registro de pagos por AJAX, confirmaciones), compilado con Vite
- Laravel Sanctum (API), barryvdh/laravel-dompdf (PDF), colas y notificaciones de Laravel
- PHPUnit (tests de feature y unitarios), Laravel Pint (estilo)
- Docker: Apache + PHP 8.3, worker de la cola, MySQL 8.4 y Mailpit. CI con GitHub Actions.

## Usuarios demo

Contraseña de todos: **`demo1234`**

| Usuario | Rol | Para ver |
|---|---|---|
| `admin@demo.local` | admin | todo el panel + suspender matrículas / anular certificados |
| `empleado@demo.local` | empleado | bandeja de trámites, profesionales, cuotas |
| `profesional@demo.local` | profesional (Martina Gómez, AR-10234) | portal con trámites en todos los estados |

Código de certificado demo para probar la verificación: **`DEMO-CERT-2026`** → http://localhost:8081/verificar/DEMO-CERT-2026

## Cómo correrlo

### Con Docker (recomendado; también en Windows 10 con Docker Desktop)

Requisitos: Docker Desktop (en Windows, con WSL 2) y Git.

```bash
git clone <url-del-repo> tramites-laravel
cd tramites-laravel
docker compose up -d --build
```

- App: http://localhost:8081
- Mailpit (mails de cambio de estado): http://localhost:8026

La primera vez el contenedor genera la `APP_KEY`, corre las migraciones y carga los datos demo (solo si la base está vacía). Para empezar de cero: `docker compose down -v`.

En Windows es lo mismo desde PowerShell. El `.gitattributes` fuerza finales de línea LF; si igual el contenedor falla con `/bin/sh^M` (por `core.autocrlf`), ejecutar `git config core.autocrlf input` y volver a clonar.

### Sin Docker

Requisitos: PHP 8.3 con `pdo_sqlite` (o `pdo_mysql`), `mbstring`, `intl`, `gd`, `zip`; Composer 2; Node 20.19+ o 22.

```bash
composer install
cp .env.example .env             # en Windows: copy .env.example .env
php artisan key:generate
touch database/database.sqlite   # en Windows: type nul > database\database.sqlite
php artisan migrate --seed
npm install && npm run build
php artisan serve                # http://localhost:8000
php artisan queue:work           # en otra terminal: procesa los mails (van a storage/logs/laravel.log)
```

Para usar MySQL, en `.env` poner `DB_CONNECTION=mysql` y completar `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`.

En Windows sin Docker lo más simple es Laragon o XAMPP con PHP 8.3 (activar `intl`, `gd`, `zip` y `pdo_sqlite` en `php.ini`).

## Pantallas y rutas principales

| Ruta | Quién | Qué |
|---|---|---|
| `/` | público | inicio |
| `/verificar`, `/verificar/{codigo}` | público | verificación de certificados (con límite de pedidos) |
| `/login` | público | ingreso |
| `/portal` | profesional | resumen: matrícula, cuotas, trámites |
| `/portal/tramites`, `/portal/tramites/nuevo` | profesional | mis trámites / iniciar trámite con PDFs |
| `/portal/cuotas` | profesional | mis cuotas |
| `/panel` | empleado/admin | tablero con contadores |
| `/panel/tramites` | empleado/admin | bandeja con filtros, búsqueda y paginación |
| `/panel/profesionales` | empleado/admin | padrón, alta/edición, cambio de estado (admin) |
| `/panel/cuotas` | empleado/admin | cuotas con filtros y registro de pagos |
| `/api/v1/...` | token Sanctum | ver [docs/api.md](docs/api.md) |

## Decisiones técnicas

- **Lógica de negocio en servicios** (`GestorTramites`, `EmisorCertificados`, `GeneradorCuotas`): los controllers web y de la API solo validan (Form Requests) y delegan. Así cada regla está en un solo lugar y se testea directo.
- **Enums de PHP** para los estados (trámite, matrícula, cuota) y los roles, con sus transiciones, etiquetas y colores de badge, en vez de strings sueltos.
- **Concurrencia**: el cambio de estado bloquea la fila (`lockForUpdate`) dentro de la transacción, para que dos empleados no resuelvan el mismo trámite a la vez.
- **Mails después del commit** (`afterCommit()`): si la transacción falla, no se avisa de algo que no pasó.
- **Adjuntos en el disco privado** (`storage/app/private`): se descargan por una ruta que pasa por la Policy, nunca quedan públicos. Si falla la base al iniciar un trámite, se borran los archivos ya guardados.
- **CUIT con dígito verificador** (módulo 11) en `App\Support\Cuit` + la regla `CuitValido`; se guarda normalizado (11 dígitos) y se muestra con guiones.
- **Código de certificado** aleatorio (`random_int`) con un alfabeto sin caracteres ambiguos; la página pública tiene `throttle` contra fuerza bruta y muestra lo mínimo (sin datos de contacto).
- **API** de solo lectura, con `JsonResource` que no exponen datos personales, tokens con vencimiento y límite de intentos en el login.
- `Model::shouldBeStrict()` fuera de producción: si aparece un N+1 (lazy loading), los tests fallan.
- `fakerphp/faker` está en `require` (no en `require-dev`) porque el seeder de datos demo también corre en la imagen de Docker, que se instala sin dependencias de desarrollo.

## Tests

PHPUnit contra **SQLite en memoria** (no hace falta levantar una base): **88 tests, 342 aserciones, todos pasan**.

```bash
php artisan test
# o
composer test
```

Cubren: flujo de estados y transiciones inválidas, observación obligatoria, efectos de aprobar (certificado / domicilio), permisos por rol en portal, panel y API, subida de PDFs (rechaza otros formatos), validación de CUIT, cuotas (generación idempotente, vencidas, pago), verificación pública de certificados (válido / vencido / anulado / inexistente), notificación encolada al cambiar de estado, y login web y por API.

Además verifiqué que las migraciones corran en **MySQL 8.4** (`migrate:fresh --seed`, `migrate:rollback` y de nuevo `migrate`). El CI (`.github/workflows/ci.yml`) corre Pint y los tests, las migraciones y el seed contra un servicio MySQL, y `docker compose build`.

## Qué NO incluye / qué no se probó

- No hay pago online real (el pago lo registra un empleado) ni facturación.
- No hay recuperación de contraseña ni registro abierto: a los profesionales los da de alta el consejo.
- Los mails usan el layout estándar de Laravel; no hay otros canales (SMS, WhatsApp).
- La API es solo de lectura.
- **Docker**: en el entorno donde lo desarrollé los repositorios de paquetes de Debian estaban bloqueados, así que **no pude construir la etapa final del `Dockerfile` tal cual está** (el paso `apt-get install libicu-dev`). Sí construí la etapa de assets (Node/Vite) y probé el `docker-compose.yml` completo (app + worker + MySQL + Mailpit) con una imagen equivalente basada en Ubuntu, con el mismo código, `entrypoint.sh` y `php.ini`: levantó, migró, cargó los datos demo, el login y el panel respondieron, un cambio de estado generó el mail que procesó el worker y llegó a Mailpit, la API devolvió datos con token y el PDF del certificado se descargó. El `Dockerfile` del repo (basado en `php:8.3-apache`) debería construir en una máquina con internet normal, y el CI lo construye, pero no lo vi terminar en mi entorno.
- El workflow de GitHub Actions está escrito pero no lo corrí en GitHub.
- No lo probé en Windows; las instrucciones son las habituales de Docker Desktop / Laragon.

## Cómo contarlo en una entrevista

1. Es un sistema de trámites para un consejo profesional: padrón de matriculados, portal para iniciar trámites con PDFs, panel interno para resolverlos, cuotas y certificados verificables.
2. El flujo de estados es un **enum de PHP** que sabe a qué estados puede pasar; un servicio (`GestorTramites`) aplica las reglas dentro de una **transacción** y deja el historial.
3. Los permisos están en **Policies** (un profesional solo ve sus trámites; solo el admin suspende matrículas) y la validación en **Form Requests**; los controllers quedan cortos.
4. Los mails salen por **cola** (driver `database`) con `afterCommit`, para no avisar de algo que se deshizo; con Docker se ven en Mailpit.
5. El certificado tiene un **código de verificación público** que cualquiera consulta sin login, con límite de pedidos.
6. Valido el **CUIT con su dígito verificador** (módulo 11) y lo testeo con casos válidos e inválidos.
7. Tiene una **API con Sanctum** documentada y **88 tests** de feature y unitarios contra SQLite en memoria; las migraciones las probé también en MySQL.

**Preguntas probables**

- *¿Por qué un servicio y no la lógica en el controller?* — Porque la usan el portal, el panel y la API; así la regla está en un solo lugar y la testeo sin HTTP.
- *¿Qué pasa si dos empleados cambian el mismo trámite a la vez?* — El segundo espera el `lockForUpdate`, relee el estado y, si la transición ya no es válida, recibe un error en vez de pisar al primero.
- *¿Para qué Gates y Policies?* — Gates para "¿puede entrar al panel o al portal?" (depende solo del rol) y Policies para reglas sobre un registro concreto (¿este trámite es suyo?).
- *¿Cómo evitás el N+1?* — Eager loading (`with`) en los listados y `Model::shouldBeStrict()` en desarrollo y tests, que hace fallar cualquier lazy loading.
- *¿Por qué una cola para los mails?* — Para que el empleado no espere al servidor de correo y para reintentar si falla (`tries = 3`).
- *¿Cómo protegés los adjuntos?* — Están fuera de `public`, en el disco privado, y se descargan por una ruta que pasa por la Policy.
- *¿Qué mejorarías?* — Pago online con webhook, auditoría de cambios en el padrón, firma digital del PDF y tests de navegador (Dusk).
