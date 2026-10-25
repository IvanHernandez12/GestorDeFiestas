
![base_de_datos_img_01.jpeg](./assets/base_de_datos/base_de_datos_img_01.jpeg)

![base_de_datos_img_02.png](./assets/base_de_datos/base_de_datos_img_02.png)

![base_de_datos_img_03.png](./assets/base_de_datos/base_de_datos_img_03.png)

**  ****Ingen****iería**** en software**
**Unidad de aprendizaje:**
**Auditoria de sistemas**
**Empresa ****Bigbitbyte**
**Profesor:**

![base_de_datos_img_04.png](./assets/base_de_datos/base_de_datos_img_04.png)

**Mauro Sánchez Sánchez**
**Manual de Base de Datos**
**Estudiantes****:**
Ivan Hernández Hinojosa
Jorge Darien Cabrera Bastidas
Carlos González Laines
**Fecha de entrega:** **22****/****Septiembre****/202****6**

# Elaboración

El presente manual fue elaborado por el equipo de Bigbitbyte, integrado por:

| N.° | Integrantes |
| --- | --- |
| 1 | Jorge Darien Cabrera Bastidas |
| 2 | Carlos Gonzales Laines |
| 3 | Ivan Hernandez Hinojosa |

> **Ilustración 1. Tabla de Elaboración**

# Control de Versiones

La siguiente tabla registra el historial de cambios del presente manual, Toda
modificación posterior a su aprobación debe quedar asentada en la siguiente tabla:

| Versión | Fecha | Autor | Descripción |
| --- | --- | --- | --- |
| 1.0 | 09/09/2026 | Jorge Cabrera | Primera versión y creación de manual de base de datos. |
| 2.0 | 17/09/2026 | Jorge Cabrera, Carlos Gonzales | Ultima revisión de los manuales. |
| 3.0 | 08/10/2026 | Jorge Cabrera | Ajuste de manual por auditoria externa. |

> **Ilustración 2. Tabla de control de versiones**

Tabla de contenido
Tabla de ilustraciones

> **Ilustración 1. Tabla de Elaboración PAGEREF _Toc240709440 \h 2**

> **Ilustración 2. Tabla de control de versiones PAGEREF _Toc240709441 \h 2**

> **Ilustración 3. Formato F-CCBD en blanco. PAGEREF _Toc240709442 \h 11**

# 1. Bases de datos.

Una base de datos es una colección estructurada de datos almacenados de forma sistemática y electrónica, cuyo propósito principal es facilitar el almacenamiento, la modificación y la extracción eficiente de la información. En el contexto de la empresa **Bigbitbyte**, las bases de datos representan el pilar fundamental que sostiene nuestras operaciones diarias, garantizando la integridad, disponibilidad y confidencialidad de la información crítica del negocio.
«Una base de datos compartida e integrada proporciona una visión centralizada de los datos de la empresa, lo que ayuda a eliminar la redundancia y a mejorar la consistencia» (Coronel y Morris, 2020, p. 15). Este principio es esencial para asegurar que todas las áreas de nuestra organización trabajen bajo un ecosistema sincronizado y estandarizado.

# 2. Administración de bases de datos.

Considerando que ***los*** desarrollo***s*** de ***la empresa ***Bigbitbyte se realiza en un entorno de trabajo colaborativo distribuido (equipos personales de cada estudiante), la administración de la base de datos se basa en procesos ***sencillos,*** pero estrictamente auditables. El objetivo es garantizar el control y resguardo de los datos sin requerir infraestructura de paga.

## 2.1 Monitoreo de Rendimiento y Evidencia

El monitoreo se realizará de forma local en los equipos de desarrollo utilizando herramientas gratuitas e integradas como el Dashboard de MySQL Workbench. Se establecen los siguientes lineamientos auditables:
Revisión periódica: Cada viernes, el integrante designado con el rol de DBA documentará mediante capturas de pantalla el estado del servidor local de prueba, evidenciando que las consultas se ejecuten correctamente. Estas capturas se guardarán en una carpeta de evidencias del proyecto*** esta carpeta esta en Google Drive llamada: “***Backups_DB***”****** ***.
Registro de consultas lentas: Se verificará visualmente en el gestor local si existen bloqueos o cuellos de botella al ejecutar procesos pesados***.***

## 2.2 Gestión de Copias de Seguridad (Backups)

Para asegurar que el progreso no se pierda por fallos en la computadora de algún integrante y mantener una trazabilidad transparente para auditoría, se implementará la siguiente política de respaldos compartidos:
Frecuencia y Herramienta: Se realizará un respaldo lógico completo (exportación de la estructura y datos) cada viernes al finalizar la jornada de trabajo del equipo, utilizando comandos de exportación estándar (ej. mysqldump o la opción de exportar de MySQL Workbench).
Nomenclatura: El archivo SQL generado deberá nombrarse obligatoriamente bajo el formato: Backup_Bigbitbyte_YYYYMMDD.sql.
Almacenamiento y Auditoría: El archivo será subido el mismo día a una Carpeta Compartida en Google Drive (o OneDrive) del equipo*** la carpeta compartida se llama: “*****Backups***_DB”*. Esta plataforma es ideal para auditoría, ya que permite revisar el historial de versiones, verificar con exactitud qué miembro subió el archivo y corroborar el cumplimiento de fechas y horarios.

## 2.3 Control de Acceso

Incluso en un entorno controlado, se debe tener un control de accesos para cumplir con las normativas de auditoría de sistemas:
**Separación de Roles:** La conexión del código de la aplicación (backend) a la base de datos local se hará mediante un usuario creado ***llamado ***específicamente***: “DB_DEV”*** para la app el cual tendrá permisos exclusivos de lectura, inserción y actualización.
**Uso del usuario Root:** Las credenciales administrativas (root) estarán reservadas únicamente para realizar cambios estructurales (crear tablas, borrar registros críticos) y no deben incluirse en el código fuente subido a GitHub.

## 2.4 Acceso a la carpeta de “Backups_DB”.

El acceso a la carpeta destinada a los respaldos de la base de datos y a la carga de evidencias es exclusivo para los colaboradores de los proyectos de la empresa*** ***Bigbitbyte. Dichos miembros se encuentran definidos en la sección "Colaboradores", y el enlace de acceso se incluye en los anexos de este manual. *(Anexo1)**.*

# 3. Diseño de bases de datos

El diseño debe ser fácil de interpretar para fines académicos y estar centralizado para que todos los miembros del equipo trabajen sobre la misma estructura lógica.

## 3.1 Normalización y Estándares de Nombrado

- **Normalización:** Las tablas estarán estandarizadas en Tercera Forma Normal (3FN) para evitar redundancias evidentes en la auditoría del esquema.
- **Nomenclatura:** Formato snake_case en plural para tablas (ej. usuarios, ventas). Las claves primarias siempre serán id y las foráneas usarán el prefijo singular de la tabla origen (ej. usuario_id).

## 3.2 Diagramado

Todos los diagramas Entidad-Relación (ER) se elaborarán utilizando herramientas accesibles y gratuitas como draw.io (diagrams.net) o la función nativa de MySQL Workbench. Para asegurar su auditoría, los archivos fuente del diseño (ej. archivos .drawio o .mwb) deberán almacenarse directamente en el repositorio de GitHub del proyecto. Esto permitirá rastrear cualquier modificación gráfica en la arquitectura a lo largo del cuatrimestre.
***4******. Metodologías para el diseño y programación de bases de datos***

> ***La implementación de cambios en la base de datos se rige por un flujo de trabajo basado en el control de versiones, previniendo así el caos de tener esquemas desincronizados entre las computadoras del equipo.***

## 4.1 Flujo de Trabajo Auditable en GitHub

> ***Sincronización de Estructuras: Cualquier cambio en la base de datos (creación de tablas, nuevas columnas) no debe realizarse de forma manual y aislada. El integrante responsable creará un script SQL (ej. 01_crear_tabla_usuarios.sql) y lo enviará al repositorio de GitHub (en una carpeta llamada /database_scripts) mediante un Commit. Sus compañeros descargarán ese script para actualizar su entorno local.***

> ***Auditoría de cambios: Al utilizar el historial de GitHub, **se** puede verificar exactamente qué **miembro** programó el script SQL, en qué fecha y qué estructura modificó, garantizando una trazabilidad perfecta para la evaluación del proyecto.***

## 4.2 Estándares de Programación SQL

> ***Procedimientos y Triggers: En caso de implementarse, deberán documentarse dentro de los archivos SQL del repositorio. Se usará el prefijo sp_ para procedimientos (ej. sp_registrar_venta) y trg_ para disparadores.***

> ***Comentarios obligatorios: Dentro de cada archivo .sql alojado en GitHub, el **miembro** autor debe dejar un comentario en la cabecera del código indicando su nombre completo, la fecha de creación y el propósito de la consulta, facilitando así las revisiones cruzadas del equipo**.***

# 5. Generalidades a considerar en la selección del manejador de base de datos

Para el desarrollo de los sistemas de Bigbitbyte, la elección del Sistema Gestor de Base de Datos (SGBD) se ha fundamentado en la viabilidad técnica para el entorno y en los estrictos requerimientos de auditoría. Es indispensable contar con un software que todos los miembros puedan ejecutar localmente sin incurrir en gastos de licenciamiento.
Por lo anterior, se seleccionó MySQL (o su variante MariaDB) como el motor relacional principal del equipo, basándonos en las siguientes consideraciones:
**Licenciamiento Gratuito (Open Source):** No genera costos económicos, garantizando la viabilidad del proyecto escolar mientras ofrece herramientas de nivel corporativo.
**Soporte Multiplataforma: **Puede instalarse y ejecutarse de manera idéntica en Windows, macOS o distribuciones Linux. Esto asegura que el código SQL escrito por un miembro funcionará exactamente igual en la computadora del resto del equipo.
**Herramientas Visuales Nativas:** Su ecosistema incluye herramientas como MySQL Workbench, las cuales facilitan el modelado de diagramas ER y la exportación de bitácoras, características ideales para presentar evidencias durante una auditoría.

# 6. Formatos

En el contexto de la materia de Auditoría de Sistemas, es vital estandarizar la forma en que el equipo solicita e implementa los cambios estructurales. A continuación, se especifican las plantillas de sintaxis SQL estándar que todo desarrollador debe utilizar al elaborar los scripts que enviará al repositorio de GitHub.

## 6.1 Creación, modificación y eliminación de usuarios

La administración de cuentas locales para simular entornos de control de acceso debe usar esta sintaxis estricta:
**Creación:** CREATE USER 'nombre_usuario'@'localhost' IDENTIFIED BY 'password_seguro';
**Modificación:** ALTER USER 'nombre_usuario'@'localhost' IDENTIFIED BY 'nuevo_password';
**Eliminación:** DROP USER 'nombre_usuario'@'localhost';

## 6.2 Creación, modificación y eliminación de roles

Para agrupar permisos lógicos (ej. rol de desarrollador vs. rol de aplicación web):
**Creación:** CREATE ROLE 'nombre_rol';
**Modificación (Asignación):** GRANT SELECT, INSERT, UPDATE ON bigbitbyte_db.* TO 'nombre_rol';
**Eliminación:** DROP ROLE 'nombre_rol';

## 6.3 Creación, modificación y eliminación de tablas

Toda tabla debe incluir obligatoriamente el motor InnoDB para soportar el control de transacciones y llaves foráneas.
**Creación:**
CREATE TABLE nombre_tabla (
id INT AUTO_INCREMENT PRIMARY KEY,
columna_ejemplo VARCHAR(100) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
**Modificación: **ALTER TABLE nombre_tabla ADD nueva_columna INT;
**Eliminación:** DROP TABLE IF EXISTS nombre_tabla; (Acción reservada al DBA).

## 6.4 Creación, modificación y eliminación de triggers

**Creación:**
CREATE TRIGGER trg_nombre_accion
AFTER UPDATE ON nombre_tabla
FOR EACH ROW
BEGIN
-- Inserción en tabla de bitácora
END;
**Modificación:** Dado que gestores como MySQL carecen de un comando directo de alteración, el formato estipulado será un DROP TRIGGER seguido de la recreación del mismo (CREATE TRIGGER).
**Eliminación:** DROP TRIGGER IF EXISTS trg_nombre_accion;

## 6.5 Creación, modificación y eliminación de store procedures

**Creación:**
DELIMITER //
CREATE PROCEDURE sp_accion_entidad (IN parametro INT)
BEGIN
-- Secuencia de consultas SQL
END //
DELIMITER ;
**Modificación:** Al igual que con los disparadores, se utilizará DROP PROCEDURE IF EXISTS seguido de la declaración completa con la nueva lógica.
**Eliminación:** DROP PROCEDURE IF EXISTS sp_accion_entidad;

## 6.6 Creación, modificación y eliminación del diccionario de datos

El diccionario de datos es un documento de marcado Markdown (diccionario.md) alojado directamente en el repositorio de GitHub. Los desarrolladores deben mantenerlo actualizado según el siguiente formato:
**Creación (Adición):** Registrar en formato tabla: Nombre de Tabla, Nombre de Columna, Tipo de Dato, Restricciones (PK/FK/NN) y una Descripción Funcional breve.
**Modificación:** Modificar el archivo y enviar un Commit evidenciando el cambio de estructura (ej. "Ajuste de longitud en columna teléfono").
**Eliminación:** Mover la descripción de la tabla borrada a una sección inferior etiquetada como "Deprecados / Histórico", para mantener intacta la trazabilidad del diseño original.

## 6.7 Creación, modificación y eliminación de relaciones

**Creación:** ALTER TABLE tabla_hija ADD CONSTRAINT fk_nombre_relacion FOREIGN KEY (columna_local) REFERENCES tabla_padre(id) ON DELETE CASCADE;
**Modificación:** Se debe eliminar la llave actual y volver a crearla con la nueva restricción (ej. pasar de CASCADE a RESTRICT).
**Eliminación:** ALTER TABLE tabla_hija DROP FOREIGN KEY fk_nombre_relacion;

## 6.8 Creación, modificación y eliminación de llaves

**Creación (Primaria o Única):** ALTER TABLE nombre_tabla ADD PRIMARY KEY (id); o bien ALTER TABLE nombre_tabla ADD UNIQUE (correo);
**Modificación:** Requiere eliminar la restricción previa antes de instaurar la nueva.
**Eliminación:** ALTER TABLE nombre_tabla DROP PRIMARY KEY; o ALTER TABLE nombre_tabla DROP INDEX correo;

# 7. Formatos sin llenar

Para asegurar que cualquier modificación a la base de datos sea auditable y controlada por el equipo, se establece el **Formato Único de Control de Cambios a Base de Datos **
**(F-CCBD)**. Este documento en blanco debe ser copiado, llenado y anexado como evidencia (en PDF o formato Markdown en el repositorio designado) cada vez que un desarrollador de Bigbitbyte solicite aplicar un cambio estructural en el entorno general.

| Formato Único de Control de Cambios a Base de Datos (F-CCBD) |  |  |  |
| --- | --- | --- | --- |
| ID de Solicitud: | Fecha de Petición: |  |  |
| Solicitante (Desarrollador): |  |  |  |
| Tipo de Cambio: | [   ] Usuario/Rol      [   ] Tabla/Relación/Llave      [   ] SP/Trigger      [   ] Diccionario de Datos |  |  |
| Descripción del Cambio y Justificación |  |  |  |
|  |  |  |  |
|  |  |  |  |
| Script SQL Propuesto (URL del Commit en GitHub) |  |  |  |
|  |  |  |  |
| Revisado y Aprobado por (DBA): |  | Firma / Visto Bueno: |  |

> **Ilustración 3. Formato F-CCBD en blanco.**

Descripción: Plantilla oficial para la solicitud y autorización de cambios estructurales, diseñada con la paleta de colores institucional.

# 8. Especificaciones de llenado de cada formato

Para asegurar que los reportes generen evidencia correcta para la materia de Auditoría de Sistemas, todo miembro de Bigbitbyte debe adherirse a las siguientes especificaciones al llenar el formato F-CCBD:
**ID de Solicitud:** Asignar un número consecutivo que empate con el Issue o tarea de GitHub (ej. REQ-DB-001).
**Fecha de Petición:** Indicar el día, mes y año en que se entrega la solicitud para su revisión (formato DD/MM/AAAA).
**Solicitante (Desarrollador):** Escribir el nombre completo del miembro que programó el cambio y requiere integrarlo a la base central.
**Tipo de Cambio:** Marcar con una "X" la casilla que corresponda a la categoría del script SQL (gestión de permisos, creación de tablas, inyección de lógica o actualización de documentación).
**Descripción del Cambio y Justificación:** Redactar de forma clara el "qué" y el "por qué". Ejemplo correcto: "Se agrega la tabla 'inventarios' para soportar el nuevo módulo de control de stock de la semana 4". Ejemplo incorrecto: "Pongo una tabla nueva".
**Script SQL Propuesto:** Es obligatorio pegar el enlace (URL) exacto al archivo .sql alojado en el repositorio del equipo en GitHub. Esto facilita que el administrador lo evalúe directamente en línea antes de aprobar.
**Revisado y Aprobado por (DBA):** Espacio reservado para el miembro que funge como Administrador de Bases de Datos. Debe colocar su nombre una vez que verifique que el script cumple con las normas del manual.
**Firma / Visto Bueno:** Al ser un entorno digital, bastará con colocar la palabra "APROBADO" y la fecha de autorización, o la firma escaneada del DBA.

# 9. Seguridad, salvaguarda y protección de las bases de datos

La protección de los activos de información es el eje central de la asignatura de Auditoría de Sistemas. Aunque el desarrollo de Bigbitbyte se realice en computadoras locales, el equipo debe aplicar principios de seguridad corporativos para simular un entorno de producción real y evitar vulnerabilidades fundamentales.

## 9.1 Protección de Credenciales y Archivos Sensibles

**Manejo de variables de entorno:** Las credenciales de acceso a la base local (usuario, contraseña, puerto) jamás deben escribirse en el código fuente de la aplicación (hardcoding). Toda credencial debe residir en un archivo de entorno (ej. .env).
**Bloqueo en GitHub:** Está estrictamente prohibido subir (pushear) archivos .env o scripts con contraseñas reales al repositorio. El archivo .gitignore debe estar configurado para bloquearlos. Cualquier filtración accidental de credenciales será documentada como un "Incidente de Seguridad Crítico" en los reportes de la auditoría.

## 9.2 Prevención de Inyección SQL y Cifrado

**Consultas Preparadas:** El equipo de programación backend debe utilizar obligatoriamente sentencias preparadas (Prepared Statements) u ORMs (Mapeo Objeto-Relacional) para interactuar con la base de datos. En una auditoría de código, se penalizará gravemente en uan auditoria la concatenación directa de variables en las cadenas SQL.
**Cifrado de Datos:** Todas las contraseñas de los usuarios simulados del sistema deben almacenarse aplicando algoritmos de hash criptográficos (como Bcrypt o Argon2). Almacenar contraseñas en texto plano es una violación directa a las políticas de desarrollo seguro de la empresa.

## 9.3 Plan de Recuperación ante Desastres (Salvaguarda)

El plan de recuperación de Bigbitbyte depende enteramente de la estrategia de almacenamiento distribuido descrita en secciones previas. Si la computadora de un integrante sufre un daño irreparable, el gestor de base de datos del equipo podrá restaurarse en cuestión de minutos descargando el último archivo Backup_Bigbitbyte_YYYYMMDD.sql alojado en la carpeta compartida de Google Drive *(Anexo1)**,* minimizando el impacto en los tiempos de entrega académicos.

# 10. Consideraciones adicionales

Para concluir este documento, se establecen las normativas finales de convivencia técnica que asegurarán que el trabajo del equipo sea uniforme, verificable y profesional durante todo el proceso de desarrollo.

## 10.1 Revisiones Cruzadas (Code Reviews)

Ningún miembro debe aprobar sus propias propuestas de cambios estructurales. Toda modificación a la base de datos enviada a GitHub debe ser revisada por, al menos, un compañero diferente al creador del cambio. Esto simula el proceso de auditoría corporativa, donde un par técnico valida que las políticas de normalización (3FN) y la nomenclatura snake_case se hayan cumplido rigurosamente antes de unificarlas.

## 10.2 Régimen de Auditoría y Penalizaciones Internas

En el contexto de la materia, la documentación es la evidencia del trabajo. El incumplimiento deliberado de los lineamientos dictados en este manual (por ejemplo, alterar una tabla localmente sin anexar el formato F-CCBD o nombrar un respaldo de forma incorrecta) se registrará como una "No Conformidad" en los reportes de auditoría internos del equipo. Las desviaciones recurrentes a este manual restarán credibilidad y defensa técnica al momento de presentar el proyecto final.

# Anexos

Acceso a la carpeta ***“***Backups_DB***”***:
https://drive.google.com/drive/folders/1hZoYLpoCK6dG4VnII3H_HnJCY0kUnxX5?usp=drive_link

# Bibliografía

Connolly, T. M., & Begg, C. E. (2015). *Database Systems: A Practical Approach to Design, Implementation, and Management* (6th ed.). Pearson Education. Sitio de consulta: Sitio Oficial de Pearson. Fecha de consulta: 9 de septiembre de 2026. https://www.pearson.com [URL]
Coronel, C., & Morris, S. (2020). *Database Systems: Design, Implementation, & Management* (13th ed.). Cengage Learning. Sitio de consulta: Repositorio Digital Cengage. Fecha de consulta: 9 de septiembre de 2026. https://www.cengage.com [URL]
Oracle Corporation. (2023). *MySQL 8.0 Reference Manual*. Oracle Publishing. Sitio de consulta: Sitio Oficial de MySQL. Fecha de consulta: 9 de septiembre de 2026. https://dev.mysql.com/doc/refman/8.0/en/ [URL]