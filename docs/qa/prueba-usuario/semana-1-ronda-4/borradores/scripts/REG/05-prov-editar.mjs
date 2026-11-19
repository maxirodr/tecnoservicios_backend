import { abrir, ctx, vigilar, contarEnvios, FRONT, API, sleep, texto, alertas, cap, db } from './lib.mjs';
const [, , ID = '4', clic = 'click', tel = '291 455-1111', capn = ''] = process.argv;
await abrir(async (b) => {
  const c = await ctx(b, 'diego'); const p = await c.newPage(); vigilar(p, 'pe');
  const t0 = Date.now(); const log = [];
  p.on('request', (r) => { if (r.url().includes('/api/')) log.push(`+${Date.now()-t0} → ${r.method()} ${r.url().replace(API,'')}`); });
  p.on('response', (r) => { if (r.url().includes('/api/')) log.push(`+${Date.now()-t0} ← ${r.status()} ${r.request().method()} ${r.url().replace(API,'')}`); });
  await p.goto(`${FRONT}/proveedores/${ID}`); await p.waitForLoadState('networkidle'); await sleep(500);
  await p.getByRole('button', { name: 'Editar' }).first().click(); await sleep(600);
  const d = p.getByRole('dialog');
  await d.getByLabel('Contacto · teléfono').fill(tel);
  log.push(`+${Date.now()-t0} CLICK`);
  await d.getByRole('button', { name: 'Guardar proveedor' })[clic]();
  for (let i = 0; i < 12; i++) {
    await sleep(1000);
    const n = await p.getByRole('dialog').count();
    const btn = n ? await d.getByRole('button', { name: /Guardar/ }).innerText().catch(() => '?') : '-';
    const busy = n ? await d.getByRole('button', { name: /Guardar/ }).getAttribute('aria-busy').catch(() => '?') : '-';
    console.log(`t+${i+1}s dialog=${n} btn="${btn}" busy=${busy} alertas=${JSON.stringify((await alertas(p)).filter(Boolean))}`);
    if (!n) break;
    if (i === 3 && capn) await cap(p, capn, false);
  }
  console.log(log.join('\n'));
  console.log('DB', db(`select telefono from proveedores where id=${ID}`));
});
