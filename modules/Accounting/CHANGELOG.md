# Creación del Plan de Cuentas
* Migración chart_of_accounts con estructura basada en el estándar contable de Colombia.
* Modelo ChartOfAccount.php con relaciones y validaciones.
* Seeder con la estructura inicial del plan de cuentas.

# Implementación de Asientos Contables (Journal Entries)
* Migración unificada para:
  * journal_prefixes (Prefijos de comprobantes contables).
  * journal_entries (Asientos contables).
  * journal_entry_details (Detalles de los asientos).

* Reglas de negocio:
  * No modificar ni eliminar asientos en estado posted.
  * Validación de balance (débito = crédito) antes de aprobación.
  * Prefijos fijos y parametrizables para identificar movimientos.

# Creación de Modelos
* JournalPrefix.php (Prefijos de comprobantes).
* JournalEntry.php (Asientos contables).
* JournalEntryDetail.php (Detalles de los asientos).
📌 Relaciones implementadas entre modelos.

# Controladores
* Controlador JournalPrefixController.php
  * CRUD para administrar prefijos contables (fijos y parametrizables).
  * Evita modificar prefijos internos del sistema.

* Controlador JournalEntryController.php
  * CRUD para la gestión de asientos contables.
  * No permite modificar ni eliminar asientos publicados.

* Controlador JournalEntryDetailController.php
  * CRUD para los detalles de cada asiento contable.
  * Validaciones de débito y crédito.

# Configuración de Rutas
📌 routes/web.php en módulo Accounting
* accounting/charts → Plan de cuentas.
* accounting/journal/prefixes → Prefijos de comprobantes.
* accounting/journal/entries → Asientos contables.
* accounting/journal/entry-details → Detalles de asientos.

# Flujo de Aprobación para Asientos Contables
* Se agregó un flujo de aprobación para los asientos contables.
* Nuevos estados en JournalEntry:
  * draft → Borrador (editable).
  * pending_approval → Pendiente de aprobación (no editable).
  * posted → Publicado (aprobado, no editable).
  * rejected → Rechazado (editable para corrección).
* Se permite que los asientos rechazados (rejected) puedan editarse y volver a enviarse a aprobación.
* Restricciones de Edición:
  * Solo se pueden modificar asientos en draft y rejected.
  * Los asientos en pending_approval o posted no se pueden modificar.
* Reglas de Visualización:
  * Vendedor: Puede editar draft y rejected, pero no modificar pending_approval ni posted.
  * Administrador: Puede aprobar (posted) o rechazar (rejected) los pending_approval.
* Nuevo método requestApproval($id) en JournalEntryController que valida y envía un asiento a aprobación.
* Rutas
  * accounting/journal/entries/{id}/request
  * accounting/journal/entries/{id}/approve
  * accounting/journal/entries/{id}/reject