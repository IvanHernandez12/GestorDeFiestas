# Manuales y Estándares Oficiales - Bigbitbyte (GestorDeFiestas)

Este directorio contiene la documentación oficial del proyecto **GestorDeFiestas** (Empresa **Bigbitbyte**), adaptada y estructurada en formato Markdown de alta precisión para que cualquier agente de Inteligencia Artificial (o miembro del equipo) pueda consultar, respetar y aplicar todas las directrices técnicas, de interfaz y de procesos sin ambigüedades.

---

## 📚 Índice de Manuales

| # | Documento | Enlace | Temas Principales |
|---|---|---|---|
| 01 | **Manual de Base de Datos** | [01_manual_base_de_datos.md](./01_manual_base_de_datos.md) | 3FN, normalización, nomenclatura snake_case, InnoDB, control de cambios F-CCBD, política de backups semanales (`Backup_Bigbitbyte_YYYYMMDD.sql`), usuario `DB_DEV`, seguridad contra inyecciones SQL. |
| 02 | **Manual de Metodología y Desarrollo** | [02_manual_metodologia_desarrollo.md](./02_manual_metodologia_desarrollo.md) | Metodología SCRUM, gestión en Jira, tableros Por Hacer/En Curso/En Revisión/Finalizado, roles (PO: Jorge, SM: Ivan, Dev Team), Definition of Done (DoD), ceremonias ágiles. |
| 03 | **Manual de Programación Móvil** | [03_manual_programacion_movil.md](./03_manual_programacion_movil.md) | Formato de código, PSR-12, cabeceras PHPDoc, paleta de colores móvil (#3B82F6, #60A5FA, #1F2937, #F0F9FF, colores de tablas), tipografía, dimensiones (150x100px), formas redondeadas, catálogo oficial de iconos de acción. |
| 04 | **Manual de Programación Web** | [04_manual_programacion_web.md](./04_manual_programacion_web.md) | Estándares PHP/Laravel, PSR-12, espaciado de 4 espacios, nombrado (PascalCase en clases, camelCase en métodos/variables, UPPER_SNAKE en constantes), diseño visual web, paleta de colores, componentes redondeados, burbujas de notificación, catálogo de iconos de acción. |

---

## 🎨 Resumen Rápido de Directrices de Diseño e Interfaz (UI/UX)

Cualquier pantalla o vista desarrollada (Blade, HTML, CSS o componente) debe cumplir estrictamente:

### 1. Paleta de Colores Oficial
* **Color 1 (Primario / Fondo Principal):** `#3B82F6` (Azul cielo). Fondo general plano.
* **Color 2 (Acento / Secundario):** `#60A5FA` (Azul claro).
* **Color 3 (Contraste / Texto / Fondos oscuros):** `#1F2937` (Cian-azul oscuro / gris muy oscuro).
* **Color 4 (Superficies / Tarjetas):** `#F0F9FF` / `#FFFFFF` (Blanco con bordes redondeados para agrupar elementos).
* **Colores de Tablas:**
  * Fondo general tabla: `#F6F6F6`
  * Azul oscuro encabezados: `#195864`
  * Azul claro acentos: `#008FA9`

### 2. Logotipo
* **Logo institucional de Bigbitbyte:** Debe colocarse **siempre visible en la esquina superior izquierda** de cada pantalla. El recurso se encuentra en [web_img_09.png](./assets/web/web_img_09.png) y [movil_img_09.png](./assets/movil/movil_img_09.png).

### 3. Tipografía
* **Cuerpo general y componentes:** Fuente `Arial` en tamaños estándar `12px`, `16px` y `24px`.
* **Subtemas / Encabezados de sección:** Fuente `Times New Roman` en negrita, tamaño `16px`.

### 4. Componentes y Layout
* **Contenedores modulares:** Tarjetas blancas con bordes redondeados de tamaño aproximado `150x100px` (para separar información, QR, personalización de salón, proveedores).
* **Formularios:** Cajas de texto rectangulares con bordes redondeados.
* **Diseñador de Salón de Eventos:** Variedad en mesas y sillas (representadas por círculos) y áreas redimensionables para escenarios y pistas.
* **Notificaciones:** Burbujas emergentes que se descartan al pulsar fuera o al hacer clic en el botón 'X' en la esquina superior derecha.

### 5. Catálogo de Iconos Oficiales
Los iconos definidos en el manual web y móvil para acciones estándar están disponibles en alta resolución en `assets/web/` y `assets/movil/`:
* **Invitados:** ![web_icon_12.png](./assets/web/web_icon_12.png)
* **QR:** ![web_icon_13.png](./assets/web/web_icon_13.png)
* **Información:** ![web_icon_14.png](./assets/web/web_icon_14.png)
* **Seguimiento:** ![web_icon_15.png](./assets/web/web_icon_15.png)
* **Modificar:** ![web_icon_16.png](./assets/web/web_icon_16.png)
* **Eliminar:** ![web_icon_17.png](./assets/web/web_icon_17.png)
* **Ubicación:** ![web_icon_18.png](./assets/web/web_icon_18.png)
* **Agregar:** ![web_icon_19.png](./assets/web/web_icon_19.png)

---

## 🗄️ Resumen de Base de Datos y Código Backend

* **Motor:** MySQL / MariaDB con motor `InnoDB` obligatorio.
* **Normalización:** Tercera Forma Normal (3FN).
* **Nomenclatura BD:**
  * Tablas en plural y `snake_case` (ej. `usuarios`, `eventos`, `reservas`).
  * Claves primarias: `id`.
  * Claves foráneas: singular de la tabla de origen + `_id` (ej. `usuario_id`, `evento_id`).
* **Conexión:**
  * Conexión backend vía usuario restringido: `DB_DEV`.
  * Credenciales únicamente en archivo `.env` (nunca en código fuente ni en commits).
* **Backups:**
  * Cada viernes: `Backup_Bigbitbyte_YYYYMMDD.sql` a Google Drive `Backups_DB`.
* **Estándar de Código PHP:**
  * Cumplir con **PSR-12**.
  * Bloque de cabecera **PHPDoc** al inicio de cada archivo.
  * Clases en `PascalCase`, funciones/métodos en `camelCase`, variables en `camelCase`, constantes en `MAYUSCULAS_SNAKE_CASE`.

---

## 📁 Estructura de Recursos e Imágenes (`assets/`)

Todas las imágenes originales de los manuales fueron extraídas intactas y organizadas:
* `assets/base_de_datos/`: Logotipos institucionales y esquemas.
* `assets/desarrollo/`: Tableros de Jira, gestión de Sprint Backlog, DoD y métricas de avance.
* `assets/movil/`: Demos de fondos móviles, menús, formas redondeadas e iconos de acción.
* `assets/web/`: Demos de fondos web, menús, formas de componentes, logo oficial e iconos de acción.
