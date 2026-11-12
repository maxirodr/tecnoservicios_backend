# Contribuir

## Flujo

1. Rama desde `main`: `feat/<modulo>-<tema>`, `fix/<tema>`, `chore/<tema>`.
2. Leer `docs/especificacion/<modulo>.md` y las F-XX que toca. Lo que no está especificado va a
   `docs/dudas.md` (fuente + propuesta), no al código.
3. Conventional commits en inglés (`feat:`, `fix:`, `refactor:`, `test:`, `chore:`, `docs:`).
4. PR con la descripción de qué F-XX cubre y cómo se probó. `composer lint`, `composer analyse` y
   `composer test` en verde local y en CI.

## Checklist de PR (Tech Lead)

Diseño y código

- [ ] **SOLID**: una responsabilidad por clase; controllers finos (validan con FormRequest, llaman a una
      Action, devuelven Resource); reglas en Domain/Application, no en controllers ni en el modelo Eloquent.
- [ ] **Límites de módulo**: Domain sin `Illuminate\Http`, Facades ni `Http/`; nadie importa
      `Infrastructure` de otro módulo (eventos o `Contracts/`). El test de arquitectura pasa.
- [ ] **Nombres**: lenguaje del cliente en español sin tildes/ñ para entidades y estados; inglés técnico
      para el resto. Tablas en español plural con `$table` explícito. Nada de abreviaturas crípticas.
- [ ] **`declare(strict_types=1);`** en todo archivo PHP nuevo. Tipos de parámetros y retorno siempre.
- [ ] **Envelope** `{data, message, errors}` en toda respuesta nueva (`ApiResponse` / `RespondsWithEnvelope`).
- [ ] **Idempotencia**: los POST que crean aceptan `Idempotency-Key` con clave única en DB.
- [ ] **Sin datos reales**: ni cifras de dinero reales ni nombres de empleados/proveedores en código,
      seeders, factories o tests.

Tests

- [ ] **Nombres que dicen la regla**: `solo_el_propietario_puede_tomar_un_pedido`, no `test_take`.
- [ ] **Permisos**: por cada endpoint, un caso **permitido** y uno **denegado** según la matriz
      (sector × función, `ver`/`editar`, alcance `todas`/`propias`).
- [ ] **IDOR**: acceder por ID a un recurso fuera de mi alcance devuelve 403/404, no el recurso.
- [ ] **Validación**: 422 con `errors` por campo para los casos inválidos relevantes.
- [ ] Corren contra `tecnoservicios_testing` (MySQL real), con assertions > 0.

Datos y performance

- [ ] **Sin N+1**: eager loading (`with`) en listados; `preventLazyLoading` no salta en los tests.
- [ ] Listados paginados por defecto.
- [ ] **Migración reversible**: `down()` real, probada con `migrate:rollback` en la base de tests.
      Nunca `DROP DATABASE`, `migrate:fresh` ni `db:wipe` contra dev/prod. Catálogos y configuración
      inicial que necesita prod van **en migración**, no solo en seeder.
- [ ] Importes `DECIMAL(15,2)`, cantidades `DECIMAL(14,3)`; nada de `float` para plata.

Calidad y trazabilidad

- [ ] `composer lint` y `composer analyse` (Larastan nivel 8) limpios, sin baseline nuevo.
- [ ] **Trazabilidad F-XX actualizada** en `docs/qa/matriz-trazabilidad.md` (F-XX → endpoint → test).
- [ ] **Docs**: especificación del módulo, `docs/modelo-datos.md`, ADR si hubo una decisión nueva,
      `docs/bitacora.md` con lo hecho; README del módulo si cambió su alcance.
