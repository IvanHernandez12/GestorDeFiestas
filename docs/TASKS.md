# Plan de Implementación por Fases

## Fase 1: Base de Datos y Modelos
- [ ] T1: Migraciones de Usuarios, Planes, Eventos, Salones, Categorías, Mesas, Proveedores, Paquetes.
- [ ] T2: Modelos Eloquent con relaciones.
- [ ] T3: Seeders con lógica de precios de salones (Toluca) y Proveedores de prueba.

## Fase 2: API y Lógica de Negocio (Laravel)
- [ ] T4: Auth (Sanctum) y Middleware de límites de plan.
- [ ] T5: `TableDistributionService` (algoritmo de asignación).
- [ ] T6: `BudgetService` (cálculo de paquetes).
- [ ] T7: Endpoints CRUD de Eventos, Búsqueda de Salones y UUID público.

## Fase 3: Frontend Web (Vue 3)
- [ ] T8: Vistas de Login y Dashboard.
- [ ] T9: Formulario de evento (integración Google Places API).
- [ ] T10: Vistas de Paquetes y Presupuestos interactivos.
- [ ] T11: Lienzo interactivo con Konva.js.

## Fase 4: App Móvil (Flutter)
- [ ] T12: Setup de Flutter y Provider. Consumo de API por UUID.
- [ ] T13: Vista de Invitación (QR).
- [ ] T14: Vista de Mapa (Cómo llegar).
- [ ] T15: Vista de Álbum de fotos.