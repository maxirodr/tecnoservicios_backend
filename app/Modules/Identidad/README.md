# Módulo Identidad

**Propósito:** ingreso (Sanctum SPA, cookie), recuperación y primera contraseña por mail, Mi perfil,
usuarios, sectores, jerarquía ("depende de"), matriz de permisos sector × función y plazos de la
prioridad.

**Funciones del documento firmado:** F-01, F-02, F-10 (sin "Mis avisos": semana 2), F-58, F-59,
F-60, F-61 (dato y cálculo de "mi gente"), F-68 (plazos; el cálculo lo usa Pedidos en semana 2).
Historias HU-001…HU-011. Reglas completas: `docs/especificacion/identidad.md` (RN-IDE-01…44).

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
| `POST auth/ingresar` · `POST auth/salir` | público (5/min por mail+IP) · sesión | F-01 |
| `POST auth/olvide-contrasena` · `POST auth/elegir-contrasena` | público (3/h por mail) | F-01 |
| `GET/PATCH me` · `PUT me/contrasena` | sesión | F-02, F-10 |
| `GET usuarios` · `GET usuarios/{id}` | `usuarios_permisos` ver | F-58 |
| `POST usuarios` · `PATCH usuarios/{id}` · `POST usuarios/{id}/reenviar-bienvenida` | `usuarios_permisos` editar (reenvío 3/h) | F-58, F-61 |
| `GET usuarios/opciones` · `GET sectores` | sesión | F-58, F-59 |
| `POST/PATCH/DELETE sectores` | `usuarios_permisos` editar | F-59 |
| `GET permisos` · `PUT permisos` | `usuarios_permisos` ver · editar | F-60 |
| `GET plazos-prioridad` · `PUT plazos-prioridad` | `plazos_prioridad` ver · editar | F-68 |

## Reglas clave implementadas

- Mensaje único "Mail o contraseña incorrectos."; el inactivo recibe el suyo solo con la contraseña
  correcta (RN-IDE-03/04). Mail en minúsculas y sin espacios.
- Links de contraseña con los brokers de Laravel: `users` (recuperación, 60 min) y `bienvenida`
  (alta, 7 días; reenviar invalida el anterior). Sirven una vez; elegir o cambiar la contraseña
  invalida las demás sesiones y la cookie "recordarme" de otros equipos (RN-IDE-07/08/09/17).
- Mails de cuenta por la cola `mail`, después del commit (`QueuedMailable`, RN-IDE-10).
- Nadie se desactiva a sí mismo y ningún cambio (baja, sector, matriz) deja 0 usuarios activos con
  Usuarios y permisos = Editar (RN-IDE-25). "Depende de" sin sí mismo ni círculos (RN-IDE-22).
- Sector: nombre único entre activos sin mayúsculas/tildes/espacios (columna generada con índice
  único); nuevo con Pedidos y Equipos E·T y Obras V·T; sacar = borrar sin historia o archivar con
  usuarios inactivos (RN-IDE-30/31).
- Matriz: cambios todo o nada, `editar`/`propias` solo donde la función los admite, cada celda que
  cambia va a `RegistroActividad` con antes → después (RN-IDE-37/38).
- Catálogo de 12 funciones, 11 sectores, matriz §6.3 y plazos 1/7 **por migración idempotente**.
- Primer usuario en producción: `php artisan identidad:crear-administrador {email} {nombre}` (DU-A10).

## Capas

- `Http/` — controllers delgados, FormRequests, Resources, middleware, reglas de validación.
- `Application/` — un caso de uso o query por clase (Auth, Perfil, Usuarios, Sectores, Permisos, Plazos).
- `Domain/` — modelos (`User`, `Sector`, `Funcion`, `Permiso`, `Plazos`), enums, `CalculadorPrioridad`.
- `Infrastructure/` — implementaciones de los contratos, tokens y mails de cuenta, comando de consola.

Tests: `tests/Feature/Identidad/`, `tests/Unit/Identidad/`, `tests/Architecture/PermisosPorMatrizTest.php`.
