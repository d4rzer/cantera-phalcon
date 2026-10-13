"""Generate the deliverable PDF. Optional dependency: reportlab."""
from pathlib import Path
from xml.sax.saxutils import escape
import sys
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, Flowable, KeepTogether
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib import colors
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

ROOT = Path(__file__).resolve().parents[1]
OUTPUT = Path(sys.argv[1]) if len(sys.argv) > 1 else ROOT / 'docs/Manual_Tecnico_Cantera.pdf'
LOGO = Path(sys.argv[2]) if len(sys.argv) > 2 else ROOT / 'docs/umg-logo.jpg'
OUTPUT.parent.mkdir(parents=True, exist_ok=True)
FONT = Path('/usr/share/fonts/truetype/dejavu')
for name, filename in [('Sans','DejaVuSans.ttf'),('SansBold','DejaVuSans-Bold.ttf'),('Mono','DejaVuSansMono.ttf')]:
    path = FONT / filename
    if path.exists(): pdfmetrics.registerFont(TTFont(name, str(path)))
if 'Sans' not in pdfmetrics.getRegisteredFontNames():
    pdfmetrics.registerFont(pdfmetrics.Font('Sans','Helvetica','WinAnsiEncoding'))
    pdfmetrics.registerFont(pdfmetrics.Font('SansBold','Helvetica-Bold','WinAnsiEncoding'))
    pdfmetrics.registerFont(pdfmetrics.Font('Mono','Courier','WinAnsiEncoding'))

GREEN = colors.HexColor('#16856a'); DARK = colors.HexColor('#173f32'); LIGHT = colors.HexColor('#edf6f0')
GRAY = colors.HexColor('#60776a'); LINE = colors.HexColor('#dce7df'); INK = colors.HexColor('#24392d')
styles=getSampleStyleSheet()
styles.add(ParagraphStyle(name='BodyX',fontName='Sans',fontSize=9.4,leading=14.1,textColor=INK,spaceAfter=8))
styles.add(ParagraphStyle(name='SmallX',fontName='Sans',fontSize=8,leading=12,textColor=GRAY,spaceAfter=7))
styles.add(ParagraphStyle(name='TitleX',fontName='SansBold',fontSize=24,leading=30,textColor=DARK,spaceAfter=18))
styles.add(ParagraphStyle(name='SubX',fontName='SansBold',fontSize=11.2,leading=16,textColor=GREEN,spaceBefore=10,spaceAfter=7))
styles.add(ParagraphStyle(name='CodeX',fontName='Mono',fontSize=8,leading=12,textColor=DARK,backColor=LIGHT,borderPadding=10,spaceBefore=6,spaceAfter=12))
styles.add(ParagraphStyle(name='CellX',fontName='Sans',fontSize=8.4,leading=12,textColor=INK))
styles.add(ParagraphStyle(name='HeadX',fontName='SansBold',fontSize=8,leading=12,textColor=colors.white))
styles.add(ParagraphStyle(name='CoverX',fontName='SansBold',fontSize=48,leading=57,textColor=DARK,spaceAfter=10))
styles.add(ParagraphStyle(name='CoverSub',fontName='Sans',fontSize=19,leading=27,textColor=GRAY,spaceAfter=22))
story=[]
def p(text,style='BodyX'): return Paragraph(text,styles[style])
def add(text,style='BodyX'): story.append(p(text,style))
def title(number,text): add(f'{number:02d} / {text}','TitleX')
def sub(text): add(text,'SubX')
def code(text): story.append(p('<br/>'.join(escape(line).replace(' ','&#160;') for line in text.splitlines()),'CodeX'))
def bullets(items):
    for item in items: add('• '+item)
def table(headers,rows,widths=None):
    cells=[[p(escape(str(h)),'HeadX') for h in headers]]+[[p(str(cell),'CellX') for cell in row] for row in rows]
    result=Table(cells,colWidths=widths or [512/len(headers)]*len(headers),hAlign='LEFT',repeatRows=1)
    result.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,0),DARK),('BACKGROUND',(0,1),(-1,-1),colors.white),('ROWBACKGROUNDS',(0,1),(-1,-1),[colors.white,colors.HexColor('#f6faf7')]),('LINEBELOW',(0,0),(-1,0),.5,DARK),('LINEBELOW',(0,1),(-1,-1),.5,LINE),('VALIGN',(0,0),(-1,-1),'TOP'),('LEFTPADDING',(0,0),(-1,-1),10),('RIGHTPADDING',(0,0),(-1,-1),10),('TOPPADDING',(0,0),(-1,-1),6),('BOTTOMPADDING',(0,0),(-1,-1),6)]))
    story.extend([result,Spacer(1,10)])
def page(): story.append(PageBreak())

class EntityDiagram(Flowable):
    def __init__(self): super().__init__();self.width=512;self.height=230
    def draw(self):
        c=self.canv
        nodes=[(0,125,'teams',['id · nombre · activo']), (181,125,'players',['id · nombres · apellidos','nacimiento · foto · team_id']), (362,125,'matchdays',['id · número · fecha']), (181,15,'incidents',['player_id · tarjeta','incidencia · suspensión']), (362,15,'goals',['player_id · matchday_id','cantidad de goles'])]
        for x,y,name,fields in nodes:
            c.setFillColor(LIGHT);c.setStrokeColor(LINE);c.roundRect(x,y,150,75,6,fill=1,stroke=1)
            c.setFillColor(DARK);c.setFont('SansBold',11);c.drawString(x+12,y+52,name)
            c.setFont('Sans',7.5);c.setFillColor(GRAY)
            for i,line in enumerate(fields):c.drawString(x+12,y+33-i*13,line)
        c.setStrokeColor(GREEN);c.setLineWidth(1.2)
        for coords in [[150,162,181,162],[256,125,256,90],[437,125,437,90],[331,162,345,162,345,52,362,52]]:
            path=c.beginPath();path.moveTo(coords[0],coords[1])
            for i in range(2,len(coords),2):path.lineTo(coords[i],coords[i+1])
            c.drawPath(path)
        c.setFont('SansBold',7);c.setFillColor(GREEN)
        for x,y,text in [(154,172,'1:N'),(264,105,'1:N'),(445,105,'1:N'),(347,110,'1:N')]:c.drawString(x,y,text)

def header(c,doc):
    c.saveState();c.setFillColor(DARK);c.setFont('SansBold',8.4);c.drawString(50,752,'UNIVERSIDAD MARIANO GÁLVEZ DE GUATEMALA')
    c.setFont('Sans',7.5);c.setFillColor(GRAY);c.drawString(50,738,'Ingeniería en Sistemas · Centro Universitario Villanueva')
    c.drawString(50,724,'Desarrollo Web · Jornada sábado · Proyecto final')
    if LOGO.exists():c.drawImage(str(LOGO),520,714,width=43,height=41,preserveAspectRatio=True,mask='auto')
    c.setStrokeColor(LINE);c.line(50,707,562,707)
    c.setFont('Sans',7);c.setFillColor(GRAY);c.drawString(50,35,'CANTERA / Manual técnico y guía de publicación')
    c.drawRightString(562,35,'Versión 1.0 · Octubre 2026')
    c.restoreState()

# Page 1
story.append(Spacer(1,117));add('CANTERA','CoverX');add('El juego, bien organizado.','CoverSub')
add('Aplicación para administrar un torneo infantil de fútbol','SubX');add('Manual técnico de instalación, uso del framework y publicación.')
story.append(Spacer(1,25))
table(['Proyecto','Tecnología'],[['Aplicar un framework de desarrollo PHP','PHP 8.3 + Phalcon 5.22.1'],['Equipos, jugadores, goles e incidencias','MVC · Volt · MySQL / MariaDB']], [300,212])
story.append(Spacer(1,35));add('<b>Sergio Darinel Rodríguez Pérez</b>');add('Entrega en plataforma: 16 de octubre de 2026<br/>Presentación en clase: 17 de octubre de 2026','SmallX')
add('El código PHP es la aplicación para evaluar el framework. La demo web adjunta permite revisar la interfaz con datos ficticios; su almacenamiento es local al navegador.','SmallX')
page()
# Page 2
title(1,'Propósito y contenido')
add('Cantera organiza los datos de jugadores de un torneo infantil. Permite administrar catálogos de equipos y jornadas, registrar goles y tarjetas, revisar suspensiones y generar los cuatro informes solicitados.')
table(['Contenido','Página'],[['Propósito, alcance y entregables','2'],['Instalación con Docker y cuentas','3'],['Instalación nativa y estructura','4'],['Modelo de datos y relaciones','5'],['Reglas y decisiones de negocio','6'],['Seguridad y uso de Phalcon','7'],['Consulta y generación de reportes','8'],['Publicación web y operación','9'],['Verificación y presentación','10'],['Recomendaciones y referencias','11']], [430,82])
sub('Entregables y evaluación')
table(['Aspecto','Puntos'],[['Funcionamiento del programa','9'],['Documento PDF','4'],['Código fuente y preguntas','2'],['Presentación personal, expresión y respuestas','5'],['Total','20']], [430,82])
add('La entrega en plataforma es un ZIP con este PDF y el proyecto completo. La presentación debe usar una instancia funcional con datos precargados y una URL PHP de internet o laboratorio virtual. El alojamiento de esa instancia queda por configurar.','SmallX')
page()
# Page 3
title(2,'Instalación con Docker')
sub('Prerrequisitos')
add('Docker Desktop instalado y en ejecución, conexión para descargar las imágenes y un puerto disponible. En la Mac no necesitas instalar PHP, MySQL, Phalcon o Composer por separado para este método.')
sub('Paso a paso')
add('<b>1.</b> Descomprime el ZIP y abre Terminal dentro de la carpeta <b>cantera-phalcon</b>.')
code('cp .env.example .env\ndocker compose up --build -d')
add('<b>2.</b> Espera la descarga y compilación inicial. El servicio app ejecuta automáticamente las migraciones y la precarga. Los arranques siguientes conservan la información.')
code('docker compose ps\ndocker compose logs -f app\ncurl http://localhost:8080/api/health')
add('<b>3.</b> Abre <b>http://localhost:8080</b>. Si cambias APP_PORT en .env, utiliza ese puerto. La respuesta de salud debe indicar status ok y Phalcon 5.22.1.')
sub('Acceso de demostración')
table(['Rol','Correo / contraseña'],[['Administrador','admin@cantera.local<br/><b>Cantera2026!</b>'],['Árbitro / consulta','arbitro@cantera.local<br/><b>Arbitro2026!</b>']], [150,362])
add('También puedes ejecutar <b>sh iniciar.sh</b>. El archivo iniciar.command sirve para Finder si macOS autoriza su apertura. El administrador puede escribir; el árbitro consulta y genera reportes.','SmallX')
page()
# Page 4
title(3,'Instalación nativa y estructura')
add('Alternativa para un servidor con PHP ya instalado: PHP 8.3+, extensión Phalcon 5.22.1+, Composer, MySQL/MariaDB y extensiones pdo_mysql, mbstring, gd, fileinfo y OpenSSL. Verifica la extensión con php --ri phalcon. Composer por sí solo no instala esta extensión.')
add('Crea una base de datos MySQL y configura en .env el host, puerto, nombre, usuario y contraseña. Asegura escritura para la cuenta del servidor en var/ y public/uploads/.')
code('composer install\nphp scripts/migrate.php\nphp scripts/seed.php\nphp -S 127.0.0.1:8080 -t public public/router.php')
add('El servidor incorporado es para desarrollo. En una publicación usa PHP-FPM con Nginx o Apache y document root en public/.')
table(['Ubicación','Responsabilidad'],[['public/index.php','Entrada MVC y respuestas de error'],['config/','Configuración, cargador, servicios y rutas'],['app/Controllers/','Solicitudes HTTP y autenticación'],['app/Models/','Tablas y relaciones mediante ORM'],['app/Services/','Reglas de torneo y fotografías'],['app/views/','Vista Volt de la aplicación'],['database/ y scripts/','Esquemas, migración y datos de prueba'],['docker/ y compose.yaml','Servicios reproducibles de la aplicación'],['tests/ y docs/','Verificación, manual y exposición']], [184,328])
page()
# Page 5
title(4,'Modelo de datos')
add('La relación maestro-detalle parte de equipos y jugadores. Los goles se asocian tanto al jugador como a la jornada. Las incidencias pertenecen a un jugador. Una relación 1:N indica un registro principal con varios registros asociados.')
story.append(EntityDiagram());story.append(Spacer(1,9))
table(['Tabla','Datos principales'],[['teams','Nombre único y estado activo'],['players','Nombres, apellidos, nacimiento, foto, equipo, camiseta y posición'],['matchdays','Número de jornada y fecha de juego únicos'],['goals','Jugador, jornada y cantidad; pareja jugador/jornada única'],['incidents','Jugador, descripción, tarjeta, incidencia y suspensión'],['users','Nombre, correo único, hash y rol']], [125,387])
add('Las tablas de negocio incluyen id, created_at y updated_at. Se usan claves foráneas restrictivas y un registro auxiliar de bloqueo para serializar escrituras. schema_migrations guarda la versión aplicada y login_attempts cuenta intentos fallidos.','SmallX')
page()
# Page 6
title(5,'Reglas y decisiones')
table(['Área','Regla implementada'],[['Jugadores','Nombres y apellidos separados, con letras y espacios; máximo 100 caracteres. Equipo existente obligatorio.'],['Categoría infantil','De 5 a 14 años al 03/10/2026. AGE_MIN, AGE_MAX y AGE_REFERENCE_DATE permiten cambiar esta decisión.'],['Fotografía','JPG/PNG, hasta 2 MB y 25 megapíxeles. Validación de MIME y decodificación; salida JPEG de hasta 1,000 píxeles.'],['Goles','Total entero de 0 a 20 por jugador y jornada. Un registro por pareja; los cambios se realizan por edición.'],['Tarjeta roja','Fecha de suspensión obligatoria, igual o posterior a la incidencia. Bloquea las jornadas que tengan esa fecha.'],['Tarjeta amarilla','No guarda suspensión. No se acumulan amarillas automáticamente.'],['Consistencia','Una suspensión incompatible con goles existentes se rechaza; primero debe corregirse el registro de goles.'],['Historial','Un jugador con goles/incidencias conserva su equipo durante el torneo. No se borran registros con asociaciones.'],['Jornadas','Número y fecha únicos. Para mover una fecha con suspensiones asociadas, se ajustan esas suspensiones antes.']], [128,384])
add('Los límites de edad, de goles, la unicidad de fecha y la interpretación de suspensión son decisiones documentadas de implementación. El enunciado no define todos estos valores. Si el docente establece una regla distinta, se modifica el servicio y su configuración.','SmallX')
page()
# Page 7
title(6,'Phalcon y seguridad')
table(['Componente','Uso en la aplicación'],[['Application / Router / Dispatcher','Seleccionan y ejecutan controladores MVC por ruta y método HTTP.'],['Di / Autoload','Registran servicios compartidos y clases con namespace App.'],['Mvc Model','Representa tablas y relaciones; consulta y guarda registros.'],['Volt','Compila la vista index.volt que entrega la interfaz.'],['Filter Validation','Valida presencia y longitud; el servicio agrega las reglas del torneo.'],['Encryption Security','Genera hashes de contraseña y el token para protección CSRF.'],['Session / ACL','Mantienen la sesión y distinguen administración de consulta.'],['Paginator NativeArray','Paginación de diez resultados en los endpoints de listados.'],['Db Pdo Mysql','Conexión MySQL/MariaDB y transacciones.']], [169,343])
sub('Protecciones incluidas')
bullets(['Cada escritura exige sesión, rol autorizado y token X-CSRF-Token.','Cookies HttpOnly y SameSite; en producción requieren HTTPS. Se regenera el identificador al ingresar y salir.','Diez intentos fallidos bloquean temporalmente el acceso desde esa dirección durante diez minutos.','Se escapan los textos en la interfaz, se limitan campos de entrada y se usan parámetros de consulta.','Los archivos se convierten a JPEG y usan nombres aleatorios. El document root expone únicamente public/.'])
add('Las alertas de la interfaz son mensajes locales y las exportaciones PDF utilizan impresión del navegador. Estos detalles se eligieron para mantener el proyecto pequeño y reproducible.','SmallX')
page()
# Page 8
title(7,'Uso y reportes')
sub('Flujo de administración')
add('Configura los equipos y las jornadas. Registra jugadores con sus datos y fotografía. Ingresa goles por jornada y documenta las incidencias. La consulta individual reúne los datos y el historial completo del jugador.')
table(['Informe','Filtros y contenido'],[['Lista del árbitro','Equipo y jornada. Nombres, fotografía, nacimiento, edad, camiseta, equipo y disponibilidad/suspensión.'],['Incidencias','Uno o todos los jugadores del equipo. Equipo, jugador, tarjeta y rango de fechas; descripción y fechas de incidencia/suspensión.'],['Goleadores','Equipo y jornada. Nombre, foto, equipo y suma de goles en orden descendente.'],['Ficha de jugador','Información personal, posición, equipo, fotografía, goles, incidencias, suspensiones e historial por jornada.']], [146,366])
sub('Exportar o imprimir')
add('Genera el reporte y selecciona <b>CSV</b> para descargar una tabla. Selecciona <b>Imprimir / PDF</b> y elige Guardar como PDF en el diálogo del navegador. El diseño impreso oculta menús, filtros y botones y conserva los encabezados y las fotografías.')
sub('Casos de demostración')
add('La precarga contiene 4 equipos, 20 jugadores, 5 jornadas, 36 registros de goles (99 goles en total) y 6 incidencias. En jornada 3 aparecen tres suspendidos: Santiago Rodríguez, Leonardo Castillo y Javier Reyes. Mateo López suma 10 goles en los datos iniciales.')
add('Las fotografías precargadas son ilustraciones de prueba. Los registros y nombres son ficticios. Para demostrar una carga real, agrega un jugador de prueba y selecciona una imagen JPG o PNG.','SmallX')
page()
# Page 9
title(8,'Publicación y operación')
add('Para la evaluación se debe abrir la aplicación PHP real en un servidor o laboratorio virtual. La demo privada publicada permite revisar la interfaz, pero ejecuta JavaScript y guarda ejemplos en el navegador.')
bullets(['Copia la carpeta cantera-phalcon al alojamiento con Docker.','Configura .env con credenciales propias y APP_ENV=production.','Ejecuta docker compose up --build -d y revisa los registros.','Configura un dominio o URL del laboratorio y HTTPS.','Dirige el proxy del alojamiento al puerto web de la aplicación.','Comprueba salud, acceso, imágenes y reportes desde otra computadora.'])
sub('Proxy Nginx de referencia en el servidor')
add('El ejemplo corresponde al bloque de un virtual host al que el alojamiento agregará el certificado y la configuración HTTPS. Para un proxy local, limita el mapeo de Compose a 127.0.0.1:8080:80.')
code('location / {\n  proxy_pass http://127.0.0.1:8080;\n  proxy_set_header Host $host;\n  proxy_set_header X-Forwarded-Proto $scheme;\n}')
sub('Persistencia y acceso')
add('La base, las fotos y las sesiones se almacenan en volúmenes. docker compose down conserva esos volúmenes; docker compose up -d inicia de nuevo la instancia. Mantén respaldo de base de datos y fotografías antes de una actualización.')
add('Para cambiar cuentas existentes usa scripts/change_password.php con NEW_PASSWORD en el entorno. Modificar la variable de precarga después del primer inicio no cambia usuarios ya creados.')
add('<b>Estado de entrega:</b> código listo y demo privada desplegada. La URL de la instancia PHP real queda pendiente de configurar en el alojamiento elegido. No se contrató ni se inventó un servidor externo.','SmallX')
page()
# Page 10
title(9,'Verificación y presentación')
table(['Conjunto','Resultado'],[['Reglas de negocio','30 verificaciones correctas: CRUD, unicidad, edades, suspensión, asociaciones, reportes y ACL.'],['Rutas HTTP','36 verificaciones correctas: login, CSRF, permisos, fotos, filtros, paginación, informes y borrado.'],['Interacción DOM','23 verificaciones correctas: acceso, navegación, formularios, búsqueda, consulta y reportes.']], [143,369])
add('Se utilizó PHP 8.3.6 con la extensión real Phalcon 5.22.1 y MariaDB 10.11.14. Los endpoints también se probaron con SQLite. Las pruebas DOM utilizan jsdom y comprueban interacción, sin acreditar una inspección visual completa del navegador.')
code('docker compose exec app php tests/integration.php\nnode tests/http-smoke.mjs http://localhost:8080')
add('No se ejecutó una compilación Docker ni una prueba en macOS/Apple Silicon en el entorno de preparación. La configuración está incluida para reproducirla en la máquina de presentación.','SmallX')
sub('Recorrido sugerido para exponer')
bullets(['Mostrar el objetivo, el framework y /api/health de la instancia real.','Explicar equipos y jugadores como relación maestro-detalle.','Crear un jugador con foto, registrar goles y consultar su ficha.','Demostrar una roja, el bloqueo de goles y el listado de jornada 3.','Filtrar incidencias por jugador, mostrar goleadores y guardar PDF.','Entrar como árbitro para mostrar permisos de consulta.','Explicar controlador, modelo y servicio, problemas y recomendaciones.'])
add('docs/GUIA_EXPOSICION.md contiene un recorrido y preguntas de práctica. Los integrantes deben comprender el código y adaptar los comentarios a su experiencia real.','SmallX')
page()
# Page 11
title(10,'Experiencia y recomendaciones')
sub('Valoración técnica para revisar con el grupo')
add('MVC permite ubicar cada responsabilidad con claridad. Los modelos ORM y las relaciones simplifican la persistencia, y DI reúne la conexión, las sesiones y los permisos. La dificultad principal del entorno es que Phalcon exige instalar una extensión compatible con PHP; no basta descargar el código o ejecutar Composer.')
add('En esta implementación se ajustaron las clases a la versión utilizada, incluyendo Encryption\\Security y Support\\Version. También se separaron las reglas de suspensión del formulario y se verificó la carga real de fotografías para evitar depender solo de su extensión de archivo.')
add('Este texto funciona como borrador de comentarios del grupo. Antes de entregar o exponer, cada integrante debe comprobar el entorno y adaptar la valoración a la experiencia que realmente tuvo.','SmallX')
sub('Recomendaciones de uso')
bullets(['Conservar la versión de Phalcon y revisar documentación compatible.','Preparar el entorno antes de diseñar funcionalidades adicionales.','Validar en el servidor y mantener explícitas las decisiones del torneo.','Usar datos de prueba para mostrar tanto casos válidos como rechazos.','Probar la URL PHP, las imágenes y la impresión antes de la clase.'])
sub('Referencias y archivos de apoyo')
add('<link href="https://github.com/phalcon/cphalcon/releases/tag/v5.22.1" color="#16856a">Phalcon: publicación oficial de la versión 5.22.1</link>','SmallX')
add('<link href="https://docs.phalcon.io/5.12/" color="#16856a">Documentación oficial de Phalcon 5</link>','SmallX')
add('<link href="https://docs.phalcon.io/5.12/environments-docker/" color="#16856a">Phalcon: entorno Docker</link>','SmallX')
add('Enunciado ProyectoFinalA.pdf y transcripción de la conferencia proporcionados para definir el alcance. README.md amplía los comandos, docs/VERIFICACION.md registra la cobertura y docs/GUIA_EXPOSICION.md prepara la demostración.','SmallX')
add('<link href="https://cantera-sergio-demo.toastypoppy64.chatgpt.site" color="#16856a">Abrir la demo privada de interfaz</link>','SmallX')

doc=SimpleDocTemplate(str(OUTPUT),pagesize=(612,792),rightMargin=50,leftMargin=50,topMargin=106,bottomMargin=57,title='Cantera - Manual técnico',author='Sergio Darinel Rodríguez Pérez')
doc.build(story,onFirstPage=header,onLaterPages=header)
print(OUTPUT)
