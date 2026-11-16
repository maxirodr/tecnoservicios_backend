# Lecciones

Patrones aprendidos de correcciones en este repo (regla + por qué + cómo evitarlo).

## 2026-11-16 · MySQL reordena las claves de una columna JSON (MariaDB no)
- **Regla:** nunca depender del orden de las claves de un objeto guardado en una columna `json`. Si el orden importa (historial, listados), guardar una **lista** `[{campo, ...}]` (los arrays JSON conservan el orden) — ver `App\Shared\Actividad\CambiosOrdenados`.
- **Por qué:** la suite pasaba en una máquina con MariaDB 10.4 y fallaba en MySQL 8.4 (CI y producción): el historial de obras y proveedores salía en otro orden que el del cambio.
- **Cómo evitarlo:** correr la suite contra MySQL 8.x (como la CI) antes de dar algo por cerrado, no solo contra la base de la máquina de turno.

## 2026-11-16 · Tests que dependen del `.env` de la máquina
- **Regla:** un test no puede pasar o fallar según el `.env` local. Si prueba lectura de variables de entorno, fijarlas en `$_ENV`, `$_SERVER` y `putenv` (y restaurarlas); `putenv` solo no alcanza porque `env()` lee primero `$_ENV`/`$_SERVER`.
- **Después de un pull:** comparar `.env` contra `.env.example` (claves nuevas y valores cambiados) antes de correr la suite.
