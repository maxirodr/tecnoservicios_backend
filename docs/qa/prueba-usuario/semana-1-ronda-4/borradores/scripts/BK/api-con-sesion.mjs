// BK ronda 4 · PU-202: pedidos CON sesión (Diego, solo GET) midiendo status, tiempo y mensaje. No escribe cookies.
// Uso: node api-con-sesion.mjs <etiqueta> [vueltas=2]   → agrega a api-con-sesion-<etiqueta>.log
// Esperado con MySQL parado: 503 "El sistema no está disponible en este momento. Probá de nuevo en unos minutos." en ~1–3 s.
import fs from 'node:fs';
import path from 'node:path';

const AQUI = path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Z]:)/, '$1');
const etiqueta = process.argv[2] ?? 'seco';
const vueltas = Number(process.argv[3] ?? 2);
const LOG = path.join(AQUI, `api-con-sesion-${etiqueta}.log`);
const estado = JSON.parse(fs.readFileSync(path.join(AQUI, '..', 'sesiones', 'diego.json'), 'utf8'));
const cookie = estado.cookies.filter((c) => c.domain === 'localhost').map((c) => `${c.name}=${c.value}`).join('; ');
const xsrf = decodeURIComponent(estado.cookies.find((c) => c.name === 'XSRF-TOKEN')?.value ?? '');
const log = (s) => { const l = `${new Date().toTimeString().slice(0, 8)} ${s}`; console.log(l); fs.appendFileSync(LOG, l + '\n'); };

const rutas = ['/api/me', '/api/proveedores?page=1', '/api/obras?page=1', '/api/obras/1'];
for (let v = 1; v <= vueltas; v++) {
  for (const r of rutas) {
    const t = performance.now();
    let st = 'SIN-RESPUESTA', msg = '', ra = '';
    try {
      const res = await fetch(`http://localhost:8340${r}`, {
        headers: { Accept: 'application/json', Origin: 'http://localhost:5316', Referer: 'http://localhost:5316/', Cookie: cookie, 'X-XSRF-TOKEN': xsrf },
        signal: AbortSignal.timeout(45_000),
      });
      st = res.status; ra = res.headers.get('retry-after') ?? '';
      const txt = await res.text();
      try { msg = JSON.parse(txt).message ?? '(data ok)'; } catch { msg = txt.slice(0, 120); }
      if (st === 200) msg = '(200, datos)';
    } catch (e) { msg = e.message; }
    log(`[${etiqueta} v${v}] GET ${r} → ${st} en ${((performance.now() - t) / 1000).toFixed(2)} s${ra ? ` Retry-After=${ra}` : ''} · ${JSON.stringify(msg)}`);
  }
}
