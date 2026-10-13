# Recorrido de demostración y preparación del grupo

La duración siguiente es una sugerencia de 7 a 10 minutos; la conferencia no fijó una duración. Antes de exponer, cada integrante debe ejecutar y comprender los pasos, personalizar los comentarios del grupo y tener abierta la instancia PHP real.

1. **Objetivo y framework.** Mostrar el panel y explicar que se administra un torneo infantil. Indicar Phalcon 5.22.1 y que es una extensión de PHP. Mostrar `/api/health` desde la instancia real.
2. **Relación maestro-detalle.** Abrir equipos y luego sus jugadores. Explicar que `teams.id` se relaciona con `players.team_id`, y que un jugador tiene un equipo.
3. **Ingreso de datos.** Crear un jugador de prueba con nombre, apellido, nacimiento y foto JPG/PNG. Mostrar búsqueda y paginación. Consultar su ficha.
4. **Jornadas y goles.** Mostrar las cinco fechas. Registrar goles de un jugador habilitado para jornada 3. Editar el total y mostrar el cambio en goleadores.
5. **Incidencias.** Mostrar una amarilla sin suspensión y una roja con fecha de suspensión. Seleccionar a Santiago Rodríguez en jornada 3 e intentar registrar un gol: debe rechazarse.
6. **Reportes.** Generar el listado del árbitro para jornada 3: aparecen tres suspendidos. Filtrar incidencias por equipo y jugador. Mostrar los goleadores y guardar un reporte como PDF desde impresión.
7. **Permisos.** Cerrar sesión y entrar como árbitro: puede consultar, pero no agregar, editar ni borrar.
8. **Experiencia y código.** Explicar el entorno, las reglas y las dificultades concretas de instalación/configuración. Mostrar controlador, servicio y modelos. Cerrar con la URL PHP y las recomendaciones.

## Preguntas de práctica

| Pregunta | Respuesta que debe comprender el grupo |
| --- | --- |
| ¿Por qué Composer no instala por sí solo Phalcon? | Phalcon se carga como extensión del intérprete PHP. Composer valida su existencia y administra el proyecto. |
| ¿Dónde entra una solicitud? | Nginx envía las rutas a `public/index.php`; el router y el dispatcher de Phalcon seleccionan el controlador. |
| ¿Para qué sirve DI? | Los controladores y servicios reutilizan una conexión, la sesión, Security, ACL y las vistas, sin construir todo de nuevo manualmente. |
| ¿Dónde están las reglas de suspensión? | En `Tournament.php`. Se busca una roja cuya `suspension_date` coincida con la fecha de la jornada. |
| ¿Qué pasa si ya había goles en la fecha de suspensión? | Se rechaza la incidencia incompatible hasta corregir esos goles. No se borra el historial silenciosamente. |
| ¿Cómo se suman los goleadores? | Se suman los registros de cada jugador, se aplican filtros y se ordenan de mayor a menor. Los empates se ordenan por nombre. |
| ¿Qué es ORM? | Los modelos representan las tablas y sus relaciones; `find`, `save` y `delete` operan sobre la base mediante Phalcon. |
| ¿Dónde se usa Volt? | En `app/views/index/index.volt`, registrada como motor de vistas. La interacción posterior usa JavaScript y la API PHP. |
| ¿Cómo se valida una imagen? | Se comprueba tamaño, MIME, dimensiones y decodificación; se genera un JPEG con nombre aleatorio. |
| ¿Por qué no borrar equipos con jugadores? | La relación y las llaves foráneas protegen la consistencia. Se retiran primero las asociaciones o se inactiva el equipo. |
| ¿Cómo se protegen las contraseñas? | `Phalcon\Encryption\Security` genera y verifica hashes. La base no guarda la contraseña en texto. |
| ¿Para qué sirve CSRF? | Para que una escritura necesite el token de la sesión; se envía en `X-CSRF-Token`. |
| ¿Qué diferencia hay entre la demo y la aplicación? | La demo guarda ejemplos en el navegador; la aplicación real ejecuta Phalcon y usa MySQL/MariaDB. La evaluación debe mostrar la aplicación real. |

## Comentarios del grupo: borrador para adaptar

El patrón MVC ayudó a ubicar la función de cada archivo: controladores para solicitudes, modelos para datos y servicios para las reglas. La inyección de dependencias permitió reutilizar la conexión y los servicios de acceso. La parte que requiere mayor cuidado es preparar PHP con la extensión Phalcon y utilizar los nombres de clases correspondientes a la versión instalada. Docker concentra ese entorno y facilita repetir la instalación.

Se recomienda validar tanto en el formulario como en el servidor, documentar las decisiones sobre edades y suspensiones, conservar datos de prueba reproducibles y preparar los reportes antes de la exposición. El grupo debe ajustar este borrador a su experiencia real y no afirmar pruebas o publicaciones que no haya realizado.
