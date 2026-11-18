# Prueba de usuario Semana 1 · Ronda 4 · Lo que falta (agenda para retomar)

> Interrumpida el 2026-11-18 ~15:20 a pedido de Juani, en la máquina sin Laragon (`B:\…`).
> Prompt de la ronda: `tasks/prompt-prueba-usuario-semana-1-ronda-4.md` (sigue valiendo todo).
> Trabajo hecho: rama **`qa/prueba-usuario-semana-1-ronda-4`** (commit `62ffd47`), carpeta
> `docs/qa/prueba-usuario/semana-1-ronda-4/` → `borradores/a.md`, `b.md`, `bk.md`, `estado-parcial.md`,
> `borradores/scripts/` y `capturas/`.

## Para retomar en otra PC

1. `git fetch` y `git checkout qa/prueba-usuario-semana-1-ronda-4` en el backend (los reportes siguen ahí). Para
   probar, el backend y el front tienen que estar en el código de `staging`: la rama de QA sale de `staging` @
   `74beea5` y solo suma `docs/`; el front va en `staging` @ `15b544f`. Si `staging` avanzó, anotá los commits nuevos
   en el reporte y mirá qué tocaron.
2. Entorno como en el §3 del prompt, con `DB_CONNECT_TIMEOUT=1` y un `ERRORS_TOKEN` **nuevo** (no está en el repo).
   Antes de empezar, `migrate` + `migrate:fresh --seed` **solo** sobre `tecnoservicios_prueba_usuario` (verificado
   con tinker). La prueba de PU-207 con datos anteriores a la migración **ya está hecha** (ver más abajo): no hace
   falta repetirla.
3. Ingresar con las 7 personas (Diego da de alta a Dora y a los `qa-lead-r4*`) y repartir sesiones. Presupuesto de
   códigos: 20 por IP por hora; reservar ≥ 4 para la sesión vencida con vuelta por ingreso real del Cazador de regresiones.
4. **Memoria:** en `B:\…` un `clangd` del proyecto de Unreal llegó a 62 GB y dejó la API en 4–15 s por pedido. Juani
   autorizó cerrarlo; se relanza solo. Revisá la memoria libre antes de cada tanda.

## Qué falta

- [ ] **Rol 3 · QA celular y accesibilidad** (completo): PU-210, PU-212, PU-041, PU-136 en Pixel 7, iPhone SE
      375×667 y PC con teclado. Lo parcial está en `borradores/estado-parcial.md`:
      - PU-212 venía bien;
      - faltaba "Subir archivo";
      - faltaba identificar qué pantallas tienen 3 y 7 controles de menos de 44 px;
      - faltaba confirmar si el diálogo de documento en iPhone SE se pasa de la pantalla mientras guarda.
- [ ] **Rol 5 · Cazador de regresiones** (completo). Faltan:
      - cambio de estado, Plazos, Mi perfil, doble clic, buscador general;
      - permisos de los 5 sectores;
      - sesión vencida con vuelta por **ingreso real** y borrador recuperado y descartado.
      Alta y edición de obra y de proveedor, adicional, documento y rubro se recorrieron sin problemas anotados, pero
      sin borrador firmado: repasarlos.
- [ ] **Rol 4 · QA backend, parte 2 (cortes)**, con nadie más probando: PU-202 (MySQL parado de verdad → 503 en ~1–3 s,
      nunca 500 ni 30 s, entrada en `errors`), PU-203 (la pantalla ya abierta se recupera sola tras Redis y tras MySQL),
      PU-020, PU-138, worker de mail. Plan listo: `borradores/scripts/BK/plan-cortes.md` (adaptar rutas a la máquina).
- [ ] **Rol 6 · QC** y **consolidación del Lead**: `verificacion.md` (20 PU + "Decisiones aprobadas"),
      `hallazgos.md` (desde **PU-301**), `reporte.md` con el **veredicto del §7**, capturas `PU-XXX-r4-n.png`.

## Resultados ya obtenidos (2026-11-18)

**PU verificados (12 de 20):**
| PU | Estado | Rol |
|---|---|---|
| PU-201 · rubro: conflicto 409 + "Actualizar", nada se pisa, historial atribuye bien | ✅ | A |
| PU-204 · rubro y Plazos preguntan antes de irse; después de guardar no | ✅ | A |
| PU-002 · conflictos en el resto de las pantallas | ✅ | A |
| PU-006 · cambios sin guardar en el resto de las pantallas | ✅ | A |
| PU-205 · tooltip legible de links con ñ y espacios | ✅ | B |
| PU-206 · todo 401 → "Tu sesión venció. Volvé a ingresar." | ✅ | B (+ BK por API) |
| PU-207 · links por nombre de archivo, también datos anteriores a la migración | ✅ | B + Lead |
| PU-208 · sin comillas en "Agregó…" / "Quitó…" | ✅ | B |
| PU-209 · fechas dd/mm/aaaa en todas las pantallas | ⚠️ (hora del historial en UTC → B-01) | B |
| PU-112, PU-129, PU-133 | ✅ | B |
| PU-210, PU-212, PU-041, PU-136 | **pendiente** (rol 3) | — |
| PU-202, PU-203, PU-020, PU-138 | **pendiente** (rol 4 parte 2) | — |

**Decisiones aprobadas:** O-1.1 ⚠️ (A-01, A-02) · O-1.2 ✅ (14 diálogos; también en el celular) · O-3 ✅ · O-4 ✅
(front y 422 de la API en las 4 rutas) · formato de fecha ⚠️ (B-01, B-02).

**Hallazgos nuevos hasta ahora** (IDs de borrador; se renumeran a PU-3XX al consolidar):
- **B-01 · alta:** el historial muestra la hora en **UTC**: un cambio de las 14:08 se ve "18/11/2026 17:08". Se
  reprodujo con Diego, Hernán y Dora. La API manda `+00:00` (`config/app.php` `'timezone' => 'UTC'`) y el front
  muestra la hora tal cual llega. Entre las 21:00 y las 24:00 "Cuándo" muestra el día siguiente. "Ingreso trabado
  hasta las HH:MM" **sí** sale en hora local.
- **A-01 · media:** en la Tabla de permisos, irse mientras guarda pregunta "Tenés cambios sin guardar. ¿Salir igual?"
  en vez de "Estamos guardando…". Al terminar el guardado, la pregunta sigue abierta y la persona no llega a donde
  quería ir (O-1.1). Reproducido 3 veces.
- **A-02 · media:** Editar proveedor + Atrás mientras guarda: la app va a `/proveedores` pero el diálogo "Editar
  proveedor" queda abierto y no dice "Guardamos…". El cambio sí queda guardado (O-1.1). Reproducido 3 veces.
- **B-02 · baja:** los campos de fecha nativos muestran mm/dd/aaaa ("06/30/2099") cuando el idioma de la interfaz
  del navegador es inglés, aunque el `locale` sea es-AR. Con el navegador en español se ven bien.
- QA backend parte 1: sin hallazgos. Health 200 (0,22–1,05 s), `failed_jobs` 0, canal `errors` y `/api/errors` sin
  4xx, scheduler OK, migraciones `2026_11_18_*` sin error y dos veces seguidas (rollback y up repetido idénticos).

**Veredicto anticipado:** **NO APROBADA** aunque lo pendiente salga perfecto, por B-01 (alta) y A-01/A-02 (media),
según el §7. Si desarrollo los arregla en `staging` antes de retomar, conviene correr lo pendiente **y** re-verificar
B-01, A-01 y A-02 en la misma pasada. Formalmente sería la ronda 5 con este prompt: actualizar §0/§2 y las rutas.
