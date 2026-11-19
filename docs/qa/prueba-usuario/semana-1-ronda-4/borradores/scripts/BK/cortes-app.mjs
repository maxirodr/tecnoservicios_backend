// BK · ronda 4 · parte 2 (cortes, PU-203/PU-138/PU-020/PU-202). Mira la app como Diego mientras el Lead corta y levanta un servicio.
// Uso: node cortes-app.mjs <etiqueta> [--seco]
//   etiqueta: redis | mysql | worker  (nombra capturas y log)
//   --seco: no espera señales (prueba de selectores con todo arriba)
// Coordinación por archivos (los crea el Lead / BK en otra consola):
//   New-Item -ItemType File BK\senal\caido   → justo DESPUÉS de cortar el servicio
//   New-Item -ItemType File BK\senal\arriba  → justo DESPUÉS de que /api/health vuelva a 200
// La app NO se toca después de "arriba" (nada de "Reintentar"): se mira a los +8 s, +15 s, +35 s y +70 s.
// PU-203 ✅ si A y B vuelven solas (sin alerta, con datos) y, durante el corte, la alerta dice "Apenas vuelva, esta pantalla se recupera sola.".
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire('B:/ProgramFiles/NexoSmart/projects/tecnoservicios_project/tecnoservicios_frontend/package.json');
const { chromium } = require('@playwright/test');

const AQUI = path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Z]:)/, '$1');
const SESION = path.join(AQUI, '..', 'sesiones', 'diego.json');
const SENAL = path.join(AQUI, 'senal');
const CAPT = 'B:/ProgramFiles/NexoSmart/projects/tecnoservicios_project/tecnoservicios_backend/docs/qa/prueba-usuario/semana-1-ronda-4/capturas';
const FRONT = 'http://localhost:5316';
const etiqueta = process.argv[2] ?? 'redis';
const seco = process.argv.includes('--seco');
const LOG = path.join(AQUI, `cortes-app-${etiqueta}${seco ? '-seco' : ''}.log`);
fs.mkdirSync(SENAL, { recursive: true });
fs.writeFileSync(LOG, '');
const t0 = Date.now();
const log = (s) => { const l = `${new Date().toISOString().slice(11, 19)} +${((Date.now() - t0) / 1000).toFixed(1)}s ${s}`; console.log(l); fs.appendFileSync(LOG, l + '\n'); };
const dormir = (ms) => new Promise((r) => setTimeout(r, ms));
async function esperarSenal(nombre, maxMin = 20) {
  if (seco) return;
  const f = path.join(SENAL, nombre);
  log(`esperando señal "${nombre}" (${f})`);
  const fin = Date.now() + maxMin * 60_000;
  while (!fs.existsSync(f)) { if (Date.now() > fin) throw new Error(`sin señal ${nombre}`); await dormir(500); }
  log(`señal "${nombre}" recibida`);
}
async function estado(page, nombre, captura) {
  const alerta = page.getByRole('alert').filter({ hasText: /No pudimos cargar|no está disponible|problema/ });
  const nAlertas = await alerta.count();
  const textoAlerta = nAlertas ? (await alerta.first().innerText()).replace(/\s+/g, ' ') : '';
  const reintentar = await page.getByRole('button', { name: 'Reintentar' }).count();
  const barra = await page.getByRole('link', { name: 'Obras', exact: true }).count();
  const h1 = (await page.locator('h1').first().innerText().catch(() => '')).replace(/\s+/g, ' ');
  const recupera = await page.getByText('Apenas vuelva, esta pantalla se recupera sola.').count();
  const vis = await page.evaluate(() => document.visibilityState).catch(() => '?');
  const datos = await page.locator('main a[href*="/proveedores/"], main a[href*="/obras/"]').count();
  log(`[${nombre}] url=${page.url().replace(FRONT, '')} h1="${h1}" barra(link Obras)=${barra} links-a-fichas=${datos} alerta=${nAlertas ? `"${textoAlerta}"` : 'no'} Reintentar=${reintentar} se-recupera-sola=${recupera} visibilidad=${vis}`);
  if (captura) await page.screenshot({ path: path.join(CAPT, captura), fullPage: false });
}

const browser = await chromium.launch();
try {
  const ctx = await browser.newContext({ storageState: SESION, locale: 'es-AR', timezoneId: 'America/Argentina/Buenos_Aires', viewport: { width: 1366, height: 768 } });
  const abiertas = [];
  const espiar = (page, nombre) => {
    page.on('response', (r) => { if (r.url().includes('/api/')) log(`  ${nombre} ${r.request().method()} ${r.url().replace(/^https?:\/\/[^/]+/, '')} → ${r.status()}`); });
    page.on('requestfailed', (r) => { if (r.url().includes('/api/')) log(`  ${nombre} ${r.method()} ${r.url().replace(/^https?:\/\/[^/]+/, '')} → FALLÓ ${r.failure()?.errorText}`); });
    page.on('console', (m) => { if (['error', 'warning'].includes(m.type())) log(`  ${nombre} consola.${m.type()}: ${m.text().slice(0, 200)}`); });
    page.on('pageerror', (e) => log(`  ${nombre} pageerror: ${e.message.slice(0, 200)}`));
  };

  // A: app ya cargada en Obras (sesión cargada) → durante el corte navega a Proveedores.
  const a = await ctx.newPage(); espiar(a, 'A'); abiertas.push(a);
  await a.goto(`${FRONT}/obras`);
  await a.getByRole('link', { name: 'Proveedores', exact: true }).first().waitFor({ timeout: 20_000 });
  await estado(a, 'A antes', seco ? null : `BK-${etiqueta}-0-antes.png`);

  await esperarSenal('caido');
  await a.getByRole('link', { name: 'Proveedores', exact: true }).first().click();
  await dormir(8_000);
  await estado(a, 'A caído, fue a Proveedores', seco ? null : `BK-${etiqueta}-1-A-caido.png`);

  // B: pestaña nueva (sesión nunca cargada en esa pestaña) abierta durante el corte.
  const b = await ctx.newPage(); espiar(b, 'B'); abiertas.push(b);
  await b.goto(`${FRONT}/proveedores`).catch((e) => log(`B goto: ${e.message}`));
  await dormir(8_000);
  await estado(b, 'B caído, carga /proveedores', seco ? null : `BK-${etiqueta}-2-B-caido.png`);

  await esperarSenal('arriba');
  let previo = 0;
  for (const seg of [8, 15, 35, 70]) {
    await dormir((seg - previo) * 1000); previo = seg;
    await estado(a, `A +${seg}s sin tocar`, seco ? null : `BK-${etiqueta}-3-A-${seg}s.png`);
    await estado(b, `B +${seg}s sin tocar`, seco ? null : `BK-${etiqueta}-4-B-${seg}s.png`);
  }
  // Recién al final, para comparar: ¿un cambio de sección lo recupera?
  await a.getByRole('link', { name: 'Obras', exact: true }).first().click().catch((e) => log(`A clic Obras: ${e.message}`));
  await dormir(4_000);
  await estado(a, 'A tras ir a Obras (control)', seco ? null : `BK-${etiqueta}-5-A-control.png`);
} finally {
  await browser.close();
  log('navegador cerrado');
}
