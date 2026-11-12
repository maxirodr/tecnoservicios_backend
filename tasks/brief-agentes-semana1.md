# Brief común — Semana 1 (NS-2026-025 Tecnoservicios)

Sos parte del equipo de desarrollo. Antes de codear leé, completos:
- `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_backend\CLAUDE.md` (o el del front si sos frontend) y `CONTRIBUTING.md` del repo.
- `tecnoservicios_backend\docs\arquitectura.md`, `docs\modelo-datos.md`, `docs\adr\` (ADR-001…014, todos Aceptados), `docs\backlog.md` (§2 DoR, §3 DoD, §4 config inicial, §5 tus historias), `docs\dudas.md` (TODAS resueltas: va la propuesta; DU-13 = aviso al superior del solicitante) y la `docs\especificacion\<modulo>.md` que te toque. La especificación + backlog son tu contrato; si chocan con el modelo de datos, manda la especificación y lo anotás en tu reporte.
- Maquetas: `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios\docs\mockups\wireframes.html` (sección `id="W-XX"`) y notas en `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\7321fd61-f89b-4a2a-ab84-df45cc04969d\scratchpad\maquetas-notas.txt`.

Reglas de oro:
- No agregar, quitar ni cambiar funcionalidad respecto de spec/backlog. Si algo falta: elegí lo más cercano a cómo trabaja hoy el cliente, seguí, y reportalo (no inventes features).
- Código: `declare(strict_types=1)`, tipos de retorno, SOLID, un caso de uso por clase, controllers delgados, FormRequest en el borde, API Resources, envelope `{data,message,errors}` (helpers en `app/Shared/Http/`), archivos de ~200 líneas máx., sin números mágicos, sin `catch` genérico. Nombres según ADR-004 (dominio en español sin tildes, técnico en inglés, `$table` explícito). TS strict sin `any`.
- Permisos: siempre por la matriz (ADR-006), nunca roles en el código. Cada endpoint: test permitido, denegado e IDOR.
- Catálogos y config inicial por **migración** idempotente, no seeder. Seeders solo con datos ficticios.
- Sin cifras de dinero reales ni nombres reales de empleados o proveedores (datos ficticios).
- Tests con nombre que dice la regla (`test_usuario_inactivo_no_puede_ingresar`). Suite con 0 assertions o "connection refused" no cuenta: verificá MySQL (`C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe -h 127.0.0.1 -u root -e "select 1"`) y Redis antes.
- **Base de tests: SOLO la que se te asigna abajo.** Nunca la de dev (`tecnoservicios`), nunca dropear bases (CREATE DATABASE IF NOT EXISTS sí).
- Endpoints: probalos con curl (código + payload) con `php artisan serve --port=<tu puerto>`; matá solo tu server al terminar. No mates procesos ajenos (hay otros proyectos corriendo en 8000 y 5173).
- El front corre en `http://localhost:5310` (Sanctum stateful y CORS deben incluirlo).
- Git: trabajás en TU rama, commits chicos con conventional commits en inglés terminados en `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`. **No pushees, no mergees, no toques otras ramas.** `git add` solo de tus archivos (nunca `-A` si compartís carpeta).
- Al terminar actualizá `docs/qa/matriz-trazabilidad.md` (solo las filas de tus F-XX: tests reales y estado) — si estás en un worktree, hacelo en tu worktree.
- Reporte final: historias hechas (HU → criterios cumplidos), salida de tests (conteo y assertions), lint/analyse/typecheck, curls ejecutados, desvíos y decisiones propias, pendientes. Sin adornos.

## Contratos compartidos entre agentes (respetalos exactamente)

Identidad publica en `app/Modules/Identidad/Contracts/`:
```php
interface Permisos {
    public function puede(User $user, string $funcion, Nivel $nivelMinimo): bool; // Nivel: enum SinAcceso|Ver|Editar (App\Modules\Identidad\Domain\Enums\Nivel)
    public function alcance(User $user, string $funcion): Alcance;               // Alcance: enum Todas|Propias
}
interface Jerarquia {
    /** @return list<int> ids de subordinados recursivos */
    public function subordinados(User $user): array;
    public function superior(User $user): ?User;
}
```
El modelo `User` pasa a `App\Modules\Identidad\Domain\Models\User` (actualizar `config/auth.php`). Hasta que exista, los demás agentes pueden programar contra la interfaz y bindear un fake en sus tests.

Claves de función (catálogo): `pedidos`, `equipos_reparacion`, `obras`, `estado_obra`, `presupuesto_rubro`, `monto_obra`, `gastos`, `rendicion`, `control_cantidad`, `proveedores`, `usuarios_permisos`, `plazos_prioridad`.

Shared publica `app/Shared/Actividad/RegistroActividad` (registrar(Model $sujeto, string $accion, array $cambios = []): void), middleware alias `idempotent`, y adjuntos (ADR-014): `POST /api/adjuntos`, `GET /api/adjuntos/{id}`, `DELETE /api/adjuntos/{id}`, servicio `AdjuntosService::asociar(array $ids, Model $sujeto, string $contexto): void` y la interfaz `AutorizaAdjuntos` que cada módulo dueño implementa y registra por contexto.
