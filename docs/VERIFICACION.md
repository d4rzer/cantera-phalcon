# Evidencia de verificación

Entorno de preparación: PHP 8.3.6 NTS, extensión real Phalcon 5.22.1, MariaDB 10.11.14 y Node.js 24.19.0.

| Conjunto | Resultado | Cobertura |
| --- | --- | --- |
| Sintaxis | PHP y JavaScript sin errores | Archivos PHP, scripts de interfaz y demo |
| Negocio | 30 verificaciones correctas | CRUD, edades, unicidad, suspensión, historial, filtros y ACL |
| HTTP | 36 verificaciones correctas | Login/logout, CSRF, permisos, rutas, paginación, filtros, fotos, reportes y limpieza |
| DOM | 23 verificaciones correctas | Acceso, navegación, formularios, edición, borrado, búsqueda, paginación y reportes |
| Publicación de demo | Despliegue confirmado | Demo privada con datos ficticios y guardado local |

La prueba de negocio crea y elimina una base SQLite temporal. Las pruebas HTTP se ejecutaron con la conexión MySQL de Phalcon contra MariaDB y también con SQLite. Se verificó carga multipart de una imagen PNG válida, conversión a JPEG y rechazo de un archivo con MIME falsificado.

La prueba DOM utiliza jsdom, sin motor de renderizado. Comprueba las interacciones y el contenido, pero no acredita inspección visual del diseño en un navegador completo. No se ejecutó una compilación Docker ni pruebas en macOS/Apple Silicon dentro de este entorno. El Dockerfile compila la extensión en la arquitectura del contenedor y usa imágenes con soporte para esa arquitectura.

Pendiente externo: elegir y configurar el servidor/laboratorio donde publicar la instancia PHP real y probar su URL con HTTPS. La demo publicada utiliza JavaScript y almacenamiento del navegador; no acredita la ejecución PHP.

Los scripts de prueba incluidos permiten reproducir las verificaciones. Los resultados corresponden a la versión entregada, con el filtro individual de incidencias incluido.
