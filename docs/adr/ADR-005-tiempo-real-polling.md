# ADR-005 · Tiempo real con polling corto

- **Estado:** Propuesta (requiere OK de Juani) · **Fecha:** 2026-11-12 · **Rol:** CTO

## Contexto
Cap. 13: "un gasto cargado se ve en la rendición en el momento; un aviso aparece apenas
pasa". ≈13 usuarios, volumen chico.

| | Laravel Reverb (websockets) | Polling corto + invalidación |
|---|---|---|
| Latencia | < 1 s | Inmediato para lo propio; ≤ 15 s para lo que hizo otro |
| Operación | Un proceso más en Supervisord, proxy WS en nginx, TLS, reconexión, auth de canales | Nada nuevo |
| Costo | Mismo hosting, más superficie de fallas | Cero |
| Carga | Mínima | 13 usuarios × 1 req/15 s ≈ 1 req/s: despreciable |
| Código | Eventos broadcast, canales privados, cliente Echo | `refetchInterval` de TanStack Query |

## Decisión
**Polling corto con TanStack Query:**
- Contador y panel de avisos: cada 15 s, solo con la pestaña visible, y al volver a enfocarla.
- Rendición abierta y "Mis tareas": cada 15 s con la pestaña visible.
- Mutaciones propias: invalidan al instante las queries afectadas (quien carga un gasto ve la
  rendición actualizada en el momento).
- Intervalos en constantes nombradas del front. `GET /api/avisos/no-leidos/contador` es barato
  (índice `user_id, leido_at`).
- La rendición se cachea por obra en Redis y se invalida con los eventos de gasto (ADR-007).

## Consecuencias
Si Juani o el cliente lo sienten lento, Reverb se suma sin tocar el dominio: los eventos ya
existen, solo se agrega un listener que hace broadcast y el front cambia polling por Echo.
