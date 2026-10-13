# Cantera · Torneo infantil

Aplicación real en **PHP 8.3 y Phalcon 5.22.1**, con MVC, modelos ORM, vistas Volt y MySQL/MariaDB. Incluye equipos, jugadores con fotografía, jornadas, goles, incidencias, reportes, acceso por roles y datos de demostración.

## Inicio rápido en Mac o Windows

Con Docker Desktop instalado y abierto, descomprime la entrega y entra a la carpeta `cantera-phalcon`:

```sh
cp .env.example .env
docker compose up --build -d
```

Abre **http://localhost:8080**. El primer arranque descarga las imágenes y compila la extensión Phalcon; los siguientes usan la imagen ya construida. Las migraciones y los datos de prueba se aplican automáticamente sin reemplazar los datos existentes.

En Mac también puedes ejecutar:

```sh
sh iniciar.sh
```

`iniciar.command` permite iniciar desde Finder si macOS autoriza la ejecución del archivo descargado. El comando en Terminal es la alternativa más directa.

```sh
docker compose ps
docker compose logs -f app
curl http://localhost:8080/api/health
```

La respuesta de salud debe mostrar `status: ok` y `Phalcon 5.22.1`. Si el puerto 8080 está ocupado, cambia `APP_PORT` en `.env` y vuelve a ejecutar `docker compose up -d`.

## Cuentas de prueba

| Cuenta | Correo | Contraseña | Permisos |
| --- | --- | --- | --- |
| Administrador | admin@cantera.local | Cantera2026! | Crear, editar, borrar y consultar |
| Árbitro | arbitro@cantera.local | Arbitro2026! | Consultar jugadores e imprimir reportes |

Los botones del acceso rellenan estas credenciales cuando `APP_ENV=development`. En producción se ocultan. Las contraseñas se almacenan con hash de Phalcon.

## Datos precargados

- 4 equipos y 20 jugadores ficticios, con imágenes ilustrativas de prueba.
- 5 jornadas: 3, 10, 17, 24 y 31 de octubre de 2026.
- Goles de las jornadas 1 y 2 y seis incidencias.
- Santiago Rodríguez, Leonardo Castillo y Javier Reyes están suspendidos el 17/10/2026, jornada 3.
- La tabla de goleadores ordena por goles descendentes; Mateo López encabeza los datos iniciales con 10.

## Reglas documentadas

1. Un jugador pertenece a un equipo. Si tiene goles o incidencias, conserva ese equipo durante el torneo para proteger el historial.
2. Categoría infantil de 5 a 14 años al 03/10/2026. `AGE_MIN`, `AGE_MAX` y `AGE_REFERENCE_DATE` permiten adaptar la categoría. Esta es una decisión de implementación, porque el enunciado no fija edades.
3. Cada jornada tiene número y fecha únicos. Se pueden administrar desde el inicio del torneo.
4. Se registra un total de 0 a 20 goles por jugador y jornada. Para corregirlo, se edita el mismo registro.
5. Una tarjeta roja exige fecha de suspensión igual o posterior a la incidencia. La suspensión afecta únicamente las jornadas de esa fecha. Una amarilla no genera suspensión ni acumulación automática.
6. No se pueden registrar goles de un jugador suspendido. Tampoco se admite una roja que contradiga goles ya registrados: primero se corrige ese registro.
7. Los registros con asociaciones no se borran. Para cambiar la fecha de una jornada con suspensiones asociadas, se ajustan esas suspensiones primero.
8. Se reciben fotografías JPG/PNG de hasta 2 MB y 25 megapíxeles. El servidor comprueba MIME y decodificación, las reduce a 1,000 píxeles y las convierte a JPEG. Los nombres de archivo son aleatorios.

## Reportes y exportación

- **Árbitro:** equipo y jornada; incluye nombres, foto, nacimiento, edad, camiseta y estado de suspensión.
- **Incidencias:** equipo, un jugador o todos, tarjeta y rango de fechas inclusivo.
- **Goleadores:** equipo y jornada; total de goles por jugador en orden descendente.
- **Ficha individual:** datos personales, foto, equipo, posición, goles, incidencias y estado por jornada.

Los reportes ofrecen CSV y **Imprimir / PDF**. Para obtener un PDF, usa el diálogo del navegador y selecciona Guardar como PDF. No requieren un servicio externo de generación de documentos.

## Estructura y uso de Phalcon

| Archivo o carpeta | Responsabilidad |
| --- | --- |
| public/index.php | Punto de entrada MVC, cabeceras y respuestas de error |
| config/bootstrap.php | Servicios DI: conexión, sesiones, Security, ACL, dispatcher, Volt |
| config/routes.php | Rutas y métodos HTTP mediante el router Phalcon |
| app/Controllers | Autenticación, catálogos, consultas y reportes |
| app/Models | Modelos `Phalcon\Mvc\Model` y relaciones |
| app/Services/Tournament.php | Reglas de negocio, transacciones y reportes |
| app/Services/PhotoStorage.php | Validación y almacenamiento de fotografías |
| app/views/index/index.volt | Vista de la aplicación compilada por Volt |
| public/js | Interacción de la interfaz y reglas de presentación |
| database | Esquemas SQL y datos de prueba reproducibles |
| scripts | Migración, precarga y cambio de contraseña |
| docker | Nginx, PHP y arranque de contenedores |
| tests | Reglas de negocio, rutas HTTP e interacción DOM |

Se eligieron scripts SQL versionados en lugar de depender de DevTools para crear el proyecto: el enunciado exige usar el framework, pero no exige scaffolding. La aplicación usa Phalcon en ejecución real, no una imitación en JavaScript. La edición y el borrado utilizan una transacción y un bloqueo en MySQL para evitar inconsistencias entre goles y suspensiones.

## Instalación nativa alternativa

Requiere PHP 8.3+, extensión Phalcon 5.22.1+, Composer, MySQL/MariaDB y extensiones `pdo_mysql`, `mbstring`, `gd`, `fileinfo` y OpenSSL. Instala Phalcon desde las instrucciones oficiales apropiadas a tu sistema. No basta con `composer install`: Phalcon es una extensión del intérprete.

Configura `.env` con `DB_HOST=127.0.0.1`, el puerto, usuario, contraseña y nombre de una base creada en MySQL. Ejecuta:

```sh
composer install
php scripts/migrate.php
php scripts/seed.php
php -S 127.0.0.1:8080 -t public public/router.php
```

El servidor incorporado de PHP sirve para desarrollo. Para publicar utiliza PHP-FPM y Nginx/Apache, con document root en `public/`.

## Publicación de la aplicación PHP

La carpeta contiene la configuración para un servidor o laboratorio virtual con Docker. Para completar la URL de la presentación:

1. Copia `cantera-phalcon` a ese servidor y configura `.env` con contraseñas propias.
2. Usa `APP_ENV=production`; las cookies requieren HTTPS en este modo.
3. Ejecuta `docker compose up --build -d`.
4. Configura un dominio o la URL del laboratorio y HTTPS mediante el proxy del alojamiento.
5. Dirige el proxy a `127.0.0.1:8080` o al puerto elegido. Si se utiliza proxy local, cambia el mapeo de Compose a `127.0.0.1:8080:80`.
6. Comprueba `/api/health`, inicia sesión y verifica las fotografías y los reportes desde otra computadora.
7. Comparte esa URL durante la presentación.

El manual incluye una configuración de proxy como referencia. No se contrataron servidores ni se inventó una URL PHP publicada: falta elegir/configurar el alojamiento de esa instancia.

Para cambiar las contraseñas ya creadas, el script lee `NEW_PASSWORD` del entorno y recibe el correo como argumento. Por ejemplo, en la terminal del servidor:

```sh
read -s NEW_PASSWORD
export NEW_PASSWORD
docker compose exec -e NEW_PASSWORD app php scripts/change_password.php admin@cantera.local
unset NEW_PASSWORD
```

Repite para el árbitro. Cambiar únicamente `SEED_ADMIN_PASSWORD` después de la precarga no modifica un usuario existente.

Los volúmenes `database`, `photographs` y `runtime` mantienen la información al detener la aplicación. `docker compose down` conserva esos volúmenes. Para reiniciar: `docker compose up -d`. Conserva una copia de la base y de las fotos antes de actualizar la instancia.

## Demo de interfaz disponible

https://cantera-sergio-demo.toastypoppy64.chatgpt.site

Esta demo privada usa los mismos archivos de interfaz y datos ficticios, con almacenamiento en el navegador. El archivo `demo/demo-config.js` es el adaptador de demostración. La demo permite revisar los flujos, pero **no ejecuta PHP ni Phalcon** y no sustituye la URL PHP para la evaluación del framework. El proyecto de producción utiliza las rutas del servidor PHP y la base de datos.

## Verificación

```sh
docker compose exec app php tests/integration.php
node tests/http-smoke.mjs http://localhost:8080
```

La prueba HTTP crea y elimina registros temporales; ejecútala en una instalación de prueba con las cuentas de demostración. La prueba de negocio usa una base SQLite temporal aislada. SQLite es solo un adaptador de pruebas; la conexión por defecto y Docker usan MySQL.

Pruebas de interfaz DOM opcionales, con Node.js:

```sh
npm install --prefix tests
npm --prefix tests run dom
```

La entrega pasó 30 verificaciones de negocio, 36 verificaciones HTTP y 23 verificaciones DOM. Se verificó PHP 8.3.6 con Phalcon 5.22.1 y MariaDB 10.11.14. Las rutas HTTP también se probaron con SQLite. No se ejecutó una compilación Docker en el entorno de preparación ni una revisión visual en navegador completo. El detalle consta en `docs/VERIFICACION.md`.

## Entrega académica

El ZIP incluye todo el código, este README, el manual técnico PDF y una guía de exposición. Entrega en plataforma: **16/10/2026**. Presentación: **17/10/2026**. Evaluación: funcionamiento 9, documento 4, código 2 y presentación personal 5, total 20 puntos.

Fuentes oficiales: https://github.com/phalcon/cphalcon/releases/tag/v5.22.1 y https://docs.phalcon.io/5.12/ .
