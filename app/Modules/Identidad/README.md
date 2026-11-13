# Módulo Identidad

**Propósito:** ingreso con el mail y un código por mail, sin contraseña (ADR-015; Sanctum SPA con
cookie), Mi perfil, usuarios, sectores, jerarquía ("depende de"), matriz de permisos sector × función
y plazos de la prioridad.

**Funciones del documento firmado:** F-01, F-02, F-10 (sin "Mis avisos": semana 2), F-58, F-59,
F-60, F-61 (dato y cálculo de "mi gente"), F-68 (plazos; el cálculo lo usa Pedidos en semana 2).
Historias HU-001…HU-011 (HU-002 y HU-004 reemplazadas por ADR-015). Reglas completas: `docs/especificacion/identidad.md` (RN-IDE-01…44).

## Qué publica para otros módulos (`Contracts/`)

| Contrato | Para qué |
|---|---|
| `Permisos::puede(User, funcion, Nivel)` / `alcance(User, funcion)` | Única puerta de autorización (ADR-006). Lee la celda en cada llamada; un inactivo no puede nada. Claves: `Domain\Enums\ClaveFuncion`. |
| `Jerarquia::subordinados(User)` / `superior(User)` | "Mi gente" recursiva (CTE) y jefe directo (aviso al superior **del solicitante**, DU-13). |
| `PlazosPrioridad::calcular(fechaPedido, fechaEntrega)` | Prioridad con los plazos vigentes, días corridos (RN-IDE-41/42). |
| `ObrasDelResponsable::cantidad(User)` | Puerto que **implementa Obras**; Identidad trae uno que devuelve 0 hasta que Obras lo registre. |

Middleware para rutas de cualquier módulo: `permiso:<funcion>,<nivel>` (403 con el sobre si la celda
no alcanza). En el grupo `api` corre `AsegurarUsuarioActivo`: la sesión de un usuario desactivado se
corta en su próximo pedido (401).

## Endpoints (`routes.php`, prefijo `/api`)

| Ruta | Permiso | F-XX |
|---|---|---|
| `POST auth/pedir-codigo` | público (3 cada 10 min por mail, 20/h por IP) | F-01 |
| `POST auth/ingresar` · `POST auth/salir` | público (10/min por mail, 30/min por IP) · sesión | F-01 |
| `GET/PATCH me` | sesión | F-02, F-10 |
| `GET usuarios` · `GET usuarios/{id}` | `usuarios_permisos` ver | F-58 |
| `POST usuarios` (idempotente) · `PATCH usuarios/{id}` | `usuarios_permisos` editar | F-58, F-61 |
| `GET usuarios/opciones` · `GET sectores` | sesión | F-58, F-59 |
| `POST/PATCH/DELETE sectores` (el POST es idempotente) | `usuarios_permisos` editar | F-59 |
| `GET permisos` · `PUT permisos` | `usuarios_permisos` ver · editar | F-60 |
| `GET plazos-prioridad` · `PUT plazos-prioridad` | `plazos_prioridad` ver · editar | F-68 |

## Reglas clave implementadas

- Ingreso con código (ADR-015): pedir código responde siempre lo mismo y solo manda a activos; el
  código es de 6 dígitos, vence a los 10 min, sirve una vez, 5 fallos lo invalidan y pedir otro
  reemplaza al anterior (`codigos_ingreso`, una fila por usuario, solo el HMAC-SHA256). Al ingresar,
  código malo, vencido, usado, inactivo o inexistente dan el mismo error. El campo `codigo` no se
  registra en el log de errores. Mail en minúsculas y sin espacios.
- Sesión de 90 días desde el último uso (`SESSION_LIFETIME=129600`, `expire_on_close = false`), sin
  "recordarme". El alta no manda mail: la persona entra directo con su mail.
- Mail del código por la cola `mail`, después del commit (`QueuedMailable`, RN-IDE-08).
- Nadie se desactiva a sí mismo y ningún cambio (baja, sector, matriz) deja 0 usuarios activos con
  Usuarios y permisos = Editar (RN-IDE-25; cuenta a cualquier activo, haya entrado o no). "Depende
  de" sin sí mismo ni círculos (RN-IDE-22).
- Sector: nombre único entre activos sin mayúsculas/tildes/espacios (columna generada con índice
  único); nuevo con Pedidos y Equipos E·T y Obras V·T; sacar = borrar sin historia o archivar con
  usuarios inactivos (RN-IDE-30/31).
- Matriz: cambios todo o nada, `editar`/`propias` solo donde la función los admite, cada celda que
  cambia va a `RegistroActividad` con antes → después (RN-IDE-37/38).
- Catálogo de 12 funciones, 11 sectores, matriz §6.3 y plazos 1/7 **por migración idempotente**.
- Primer usuario en producción: `php artisan identidad:crear-administrador {email} {nombre}` (DU-A10);
  no manda mail, entra pidiendo un código.

## Capas

- `Http/` — controllers delgados, FormRequests, Resources, middleware, reglas de validación.
- `Application/` — un caso de uso o query por clase (Auth, Perfil, Usuarios, Sectores, Permisos, Plazos).
- `Domain/` — modelos (`User`, `Sector`, `Funcion`, `Permiso`, `Plazos`, `CodigoIngreso`), enums, `CalculadorPrioridad`.
- `Infrastructure/` — implementaciones de los contratos, códigos de ingreso y mail de cuenta, comando de consola.

Tests: `tests/Feature/Identidad/`, `tests/Unit/Identidad/`, `tests/Architecture/PermisosPorMatrizTest.php`.
