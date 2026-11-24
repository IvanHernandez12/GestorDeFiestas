# Especificación del Sistema: Vela (BigBitByte)

## 1. Módulo de Planes y Auth
- **Plan Gratuito:** Por defecto al registrarse. Límite: 1 evento activo, máx 50 invitados.
- **Planes de Pago (MercadoPago):** Múltiples eventos, sin límite de invitados.
- **App Móvil:** Los invitados no tienen login. Acceden mediante un token UUID público generado por el evento.

## 2. Módulo de Eventos y Salones
- **Creación:** Nombre, Categoría (Boda, Cumpleaños, etc.), Fecha, Invitados, Presupuesto total.
- **Salones (Tabla `venues`):** `id`, `name`, `address`, `lat`, `lng`, `capacity`, `min_price`, `max_price`, `contact_info`, `google_maps_url`.
- **Lógica de Precios (Estimación):** 
  El precio se calcula automáticamente según la capacidad:
  - Hasta 50 personas: $3,000 - $8,000 MXN
  - 51 - 150 personas: $7,000 - $25,000 MXN
  - 151 - 300 personas: $20,000 - $60,000 MXN
  - Más de 300 personas: $50,000 - $200,000+ MXN
- **Presupuesto:** El sistema toma el `min_price` del salón seleccionado y lo resta al presupuesto total. El presupuesto restante se usa para calcular los paquetes de proveedores.

## 3. Distribución de Mesas (Algoritmo y Konva.js)
- **Regla:** Máx 8 personas por mesa.
- **Prioridad:** Cat 1 (más cerca del escenario/pista de baile/mesa de novios), Cat 2, etc.
- **Algoritmo:** Divide a los invitados de Cat 1 en bloques de 8 (ej. 20 = 8+8+4) y asigna las mesas más cercanas al punto focal.
- **Interfaz:** Canvas interactivo (Konva.js) donde el organizador puede arrastrar mesas y áreas, respetando el aforo del salón.

## 4. Paquetes Dinámicos
- Basado en el presupuesto restante y el tipo de evento.
- El usuario puede seleccionar/deseleccionar proveedores (ej. quitar pastel) y el total se recalcula en tiempo real.

## 5. App Móvil (Flutter) - Invitados
- **Invitación:** Datos del evento, reglamento, QR de acceso.
- **Cómo llegar:** Mapa con ruta desde ubicación actual al salón (abre Google Maps/Waze).
- **Álbum:** Toma fotos y las sube a la galería del evento.