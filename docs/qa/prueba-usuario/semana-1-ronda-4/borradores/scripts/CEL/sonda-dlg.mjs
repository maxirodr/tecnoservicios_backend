// Geometría del diálogo Nuevo proveedor en el celular, antes y durante el aviso "Estamos guardando". node sonda-dlg.mjs se|p7 <n>
import { chromium, abrir, ir, foto, salida } from './lib.mjs';
import { demorar } from './foco-lib.mjs';
const dispo = process.argv[2] ?? 'se'; const n = process.argv[3] ?? '2';
const log = salida(`out-sonda-dlg-${dispo}-${n}.txt`);
const browser = await chromium.launch();
const geo = (page) => page.evaluate(() => {
  const f = document.querySelector('.dialogo__fondo'); const d = document.querySelector('[role=dialog]');
  if (!d) return 'sin diálogo';
  const c = d.querySelector('.dialogo__cuerpo'); const p = d.querySelector('.dialogo__pie');
  const r = (e) => { const q = e.getBoundingClientRect(); return `${Math.round(q.top)}→${Math.round(q.bottom)}`; };
  const s = (e) => { const st = getComputedStyle(e); return `scroll ${e.scrollTop}/${e.scrollHeight - e.clientHeight} oy=${st.overflowY}`; };
  const btn = [...p.querySelectorAll('button')].map((b) => `"${b.innerText.trim().split('\n')[0]}" ${r(b)}`).join(' | ');
  return `vh=${innerHeight} · fondo ${r(f)} ${s(f)} · diálogo ${r(d)} ${s(d)} · cuerpo ${r(c)} ${s(c)} · pie ${r(p)} · botones ${btn} · window.scrollY=${scrollY} docScroll=${document.documentElement.scrollHeight - innerHeight}`;
});
try {
  const { page } = await abrir(browser, 'carla', dispo);
  await ir(page, '/proveedores');
  await page.getByRole('button', { name: '+ Nuevo proveedor' }).tap();
  const dlg = page.locator('[role=dialog]'); await dlg.waitFor(); await page.waitForTimeout(400);
  log(`# ${dispo} · Nuevo proveedor`);
  log('  recién abierto: ' + await geo(page));
  await dlg.getByRole('textbox', { name: 'Nombre' }).fill(`QA4-CEL Ferretería ${dispo.toUpperCase()} ${n}`);
  await page.waitForTimeout(900);
  log('  con nombre: ' + await geo(page));
  await demorar(page, 5000);
  await dlg.getByRole('button', { name: 'Guardar proveedor' }).tap();
  await page.waitForTimeout(300);
  log('  guardando: ' + await geo(page));
  await dlg.getByRole('button', { name: 'Cancelar', exact: true }).tap({ force: true });
  await page.waitForTimeout(300);
  log('  con aviso: ' + await geo(page));
  // ¿se puede deslizar para ver los botones?
  await page.mouse.move(180, 400); await page.mouse.wheel(0, 400); await page.waitForTimeout(300);
  log('  tras deslizar: ' + await geo(page));
  await foto(page, `O-1-2-r4-${dispo}-prov-sonda-${n}.png`);
  await page.waitForTimeout(6000);
  log('  final: diálogos=' + await page.locator('[role=dialog]').count());
  log('  consola/red: ' + (page._log.join(' | ') || 'limpia'));
} finally { await browser.close(); }
