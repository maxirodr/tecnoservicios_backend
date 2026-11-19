// Uso: node cel.mjs p7|se <corrida> → controles ≥ 44, desborde, fechas (no partidas ni cortadas), "Revisá la fecha",
// tarjetas de Obras (tocables enteras, un solo foco) y links de PU-210, en las pantallas de la Semana 1.
import { chromium, abrir, ir, medir, desborde, tablas, foto, salida } from './lib.mjs';
const dispo = process.argv[2] ?? 'se';
const corrida = process.argv[3] ?? '1';
const log = salida(`out-cel-${dispo}-${corrida}.txt`);
const browser = await chromium.launch();
const foto1 = (page, nombre) => (corrida === '1' ? foto(page, nombre) : Promise.resolve());

const fechas = (page) => page.evaluate(() => {
  const res = { total: 0, malas: [], otros: [] };
  const vw = innerWidth;
  const w = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  const re = /\d{2}\/\d{2}\/\d{4}(?: \d{2}:\d{2})?/g;
  const raros = [/\b\d{4}-\d{2}-\d{2}\b/, /\b\d{1,2} (?:de )?(?:ene|feb|mar|abr|may|jun|jul|ago|sep|oct|nov|dic)[a-z]*\.?\b/i, /(?<![\d/])\d{1,2}\/\d{1,2}\/\d{2}(?!\d)/, /(?<![\d/])\d\/\d{1,2}\/\d{4}|(?<![\d/])\d{2}\/\d\/\d{4}/];
  let t;
  while ((t = w.nextNode())) {
    const el = t.parentElement; if (!el || el.closest('script,style,[aria-hidden="true"],.sr-only')) continue;
    const st = getComputedStyle(el); if (st.display === 'none' || st.visibility === 'hidden') continue;
    for (const r of raros) { const m = t.data.match(r); if (m && el.getBoundingClientRect().width) res.otros.push(`"${m[0]}" en <${el.tagName.toLowerCase()}> "${t.data.trim().slice(0, 50)}"`); }
    for (const m of t.data.matchAll(re)) {
      const rg = document.createRange(); rg.setStart(t, m.index); rg.setEnd(t, m.index + m[0].length);
      const rects = [...rg.getClientRects()].filter((q) => q.width > 0);
      if (!rects.length) continue;
      res.total++;
      const tops = new Set(rects.map((q) => Math.round(q.top)));
      const der = Math.max(...rects.map((q) => q.right)), izq = Math.min(...rects.map((q) => q.left));
      let p = el, recorte = null;
      while (p && p !== document.body) {
        const s = getComputedStyle(p);
        if (['hidden', 'clip'].includes(s.overflowX) || s.textOverflow === 'ellipsis') { const b = p.getBoundingClientRect(); if (der > b.right + 1 || izq < b.left - 1) { recorte = `${p.tagName.toLowerCase()}.${p.className}`; break; } }
        p = p.parentElement;
      }
      const deslizable = (() => { let q = el; while (q && q !== document.body) { const s = getComputedStyle(q); if (['auto', 'scroll'].includes(s.overflowX) && q.scrollWidth > q.clientWidth) return true; q = q.parentElement; } return false; })();
      if (tops.size > 1 || der > vw + 1 || izq < -1 || recorte) res.malas.push(`"${m[0]}" ${tops.size > 1 ? 'PARTIDA en ' + tops.size + ' líneas ' : ''}${der > vw + 1 ? `SE SALE (R${Math.round(der)}>${vw}) ` : ''}${recorte ? 'RECORTADA por ' + recorte : ''}${deslizable ? ' (dentro de deslizable)' : ''} · <${el.tagName.toLowerCase()} class="${el.className}">`);
    }
  }
  return res;
});

const marcasFecha = (page) => page.evaluate(() => [...document.querySelectorAll('.fecha-lejana__marca')].map((e) => {
  const r = e.getBoundingClientRect(); const pr = e.parentElement.getBoundingClientRect();
  const rects = [...e.getClientRects()];
  return `"${e.parentElement.innerText.trim()}" marca ${Math.round(r.left)}→${Math.round(r.right)} (${rects.length} línea/s) contenedor ${Math.round(pr.width)}px dentro=${r.right <= innerWidth + 1 && r.left >= -1} color=${getComputedStyle(e).color}`;
}));

async function revisar(page, nombre, captura) {
  const m = await medir(page);
  const d = await desborde(page);
  const t = await tablas(page);
  const f = await fechas(page);
  const mf = await marcasFecha(page);
  log(`\n## ${nombre} · ${page.url().replace(/^https?:\/\/[^/]+/, '')}`);
  log(`controles medidos=${m.total} · chicos(<44)=${m.chicos.length}`);
  for (const c of m.chicos) log('   - ' + c);
  log(`desborde página ${d.pagina} ok=${d.ok} · deslizables: ${d.internos.join(' | ') || '—'}`);
  if (d.fuera.length) log('   fuera de pantalla: ' + d.fuera.join(' | '));
  if (t.length) log('   tablas: ' + t.join(' | '));
  log(`   fechas dd/mm/aaaa: ${f.total} · partidas/cortadas: ${f.malas.length}${f.malas.length ? '\n     ! ' + f.malas.join('\n     ! ') : ''}${f.otros.length ? '\n   OTRO FORMATO: ' + f.otros.join(' | ') : ''}`);
  if (mf.length) log('   "Revisá la fecha": ' + mf.join(' | '));
  if (captura) await foto1(page, captura);
}

async function dialogo(page, boton, nombre, captura) {
  const b = page.getByRole('button', { name: boton }).first();
  if (!(await b.count())) { log(`\n## ${nombre}: NO se encontró el botón "${boton}"`); return; }
  await b.scrollIntoViewIfNeeded(); await b.tap();
  await page.waitForSelector('[role=dialog]', { timeout: 5000 }).catch(() => {});
  await page.waitForTimeout(500);
  await revisar(page, nombre, captura);
  const cancelar = page.locator('[role=dialog]').getByRole('button', { name: /^(cancelar|cerrar)$/i }).first();
  if (await cancelar.count()) await cancelar.tap(); else await page.keyboard.press('Escape');
  await page.waitForTimeout(400);
  if (await page.locator('[role=dialog]').count()) { await page.keyboard.press('Escape'); await page.waitForTimeout(300); }
}

/** PU-210: tarjetas de Obras. Un solo control enfocable por tarjeta y la tarjeta entera abre la obra. */
async function tarjetas(page, quien) {
  const info = await page.evaluate(() => [...document.querySelectorAll('.obras-tabla tbody tr')].map((tr, i) => {
    const r = tr.getBoundingClientRect();
    const foc = [...tr.querySelectorAll('a[href], button, input, select, [tabindex]:not([tabindex="-1"])')];
    const a = tr.querySelector('a[href]');
    const pts = [[0.5, 0.5], [0.1, 0.15], [0.9, 0.15], [0.1, 0.85], [0.9, 0.85], [0.5, 0.95]];
    const golpes = pts.map(([x, y]) => { const px = r.left + r.width * x, py = r.top + r.height * y; if (py < 0 || py > innerHeight) return 'fuera-de-vista'; const e = document.elementFromPoint(px, py); return e && a && (e === a || a.contains(e)) ? 'abre' : `NO (${e?.tagName.toLowerCase()}.${e?.className})`; });
    return { i, txt: tr.innerText.replace(/\s+/g, ' ').slice(0, 70), tam: `${Math.round(r.width)}×${Math.round(r.height)}`, enfocables: foc.length, nombre: a?.innerText.replace(/\s+/g, ' ').trim(), aria: a?.getAttribute('aria-label'), href: a?.getAttribute('href'), golpes };
  }));
  log(`   tarjetas de Obras (${quien}): ${info.length}`);
  for (const c of info) log(`     · [${c.i}] ${c.tam} enfocables=${c.enfocables} link="${c.nombre}"${c.aria ? ' aria-label="' + c.aria + '"' : ''} ${c.href} · toques: ${c.golpes.join(', ')} · "${c.txt}"`);
  const fila = page.locator('.obras-tabla tbody tr').first();
  const td = fila.locator('.obras-tabla__trabajo');
  if (await td.count()) {
    const href = await fila.locator('a[href]').getAttribute('href');
    const bb = await td.boundingBox(); await page.touchscreen.tap(bb.x + bb.width * 0.7, bb.y + bb.height / 2); await page.waitForTimeout(1200);
    log(`   toque en el nombre del trabajo de la 1.ª tarjeta → ${page.url().replace(/^https?:\/\/[^/]+/, '')} (esperado ${href})`);
    await page.goBack(); await page.waitForLoadState('networkidle'); await page.waitForTimeout(500);
  }
}

const enlace = (page, re) => page.evaluate((src) => { const r = new RegExp(src); return [...document.querySelectorAll('a[href]')].filter((a) => r.test(a.innerText.trim())).map((a) => { const q = a.getBoundingClientRect(); return `"${a.innerText.trim()}" ${Math.round(q.width)}×${Math.round(q.height)}`; }); }, re.source);

try {
  {
    const { ctx, page } = await abrir(browser, 'diego', dispo);
    await ir(page, '/obras'); await revisar(page, 'Obras (Diego)', `PU-210-r4-${dispo}-obras.png`);
    await tarjetas(page, 'Diego, pestaña inicial');
    const pest = page.getByRole('tab', { name: /Confirmada/ }).first();
    if (await pest.count()) { await pest.tap(); await page.waitForTimeout(800); await revisar(page, 'Obras · Confirmadas', null); await tarjetas(page, 'Diego, Confirmadas'); }
    await ir(page, '/obras/10'); await revisar(page, 'Ficha obra QA4-CEL (10)', `PU-210-r4-${dispo}-ficha.png`);
    log('   contactos: ' + (await enlace(page, /@|^\d{3} /)).join(' | '));
    await page.locator('.contacto__medios').first().scrollIntoViewIfNeeded().catch(() => {});
    await foto1(page, `PU-210-r4-${dispo}-contactos.png`);
    await dialogo(page, '+ agregar certificado o documento', 'Diálogo agregar documento', `PU-041-r4-${dispo}-dlg-doc.png`);
    await dialogo(page, '+ agregar presupuesto adicional', 'Diálogo presupuesto adicional', null);
    await dialogo(page, /Cambiar estado/, 'Diálogo cambiar estado', null);
    await dialogo(page, /^Editar QA4-CEL Documento/, 'Diálogo editar documento', null);
    await ir(page, '/obras/5'); await revisar(page, 'Ficha obra QA4-B (5, fechas lejanas; solo mirar)', `FECHAS-r4-${dispo}-ficha-lejana.png`);
    await page.locator('.fecha-lejana__marca').last().scrollIntoViewIfNeeded().catch(() => {});
    await foto1(page, `FECHAS-r4-${dispo}-ficha-lejana-2.png`);
    await ir(page, '/obras/10/presupuesto-por-rubro'); await revisar(page, 'Presupuesto por rubro', `PU-210-r4-${dispo}-rubro.png`);
    log('   volver: ' + (await enlace(page, /Volver a la obra/)).join(' | '));
    await ir(page, '/obras/nueva'); await revisar(page, 'Nueva obra', null);
    await ir(page, '/obras/10/editar'); await page.waitForTimeout(800); await revisar(page, 'Editar obra', null);
    await ir(page, '/proveedores'); await revisar(page, 'Proveedores', null);
    await dialogo(page, '+ Nuevo proveedor', 'Diálogo nuevo proveedor', null);
    await ir(page, '/proveedores/5'); await revisar(page, 'Ficha proveedor QA4-CEL (5)', `PU-210-r4-${dispo}-prov.png`);
    log('   ‹ Proveedores / contacto: ' + (await enlace(page, /Proveedores|@|^\d{3} /)).join(' | '));
    await dialogo(page, /^Editar/, 'Diálogo editar proveedor', null);
    await ir(page, '/buscar?q=QA4'); await revisar(page, 'Buscador general', null);
    await ir(page, '/perfil'); await revisar(page, 'Mi perfil', null);
    await ir(page, '/mas'); await revisar(page, 'Más', null);
    await ir(page, '/usuarios'); await revisar(page, 'Usuarios', `PU-041-r4-${dispo}-usuarios.png`);
    await dialogo(page, '+ Agregar usuario', 'Diálogo agregar usuario', null);
    await page.getByRole('button', { name: 'Laura', exact: true }).tap().catch(() => log('  (no pude abrir la ficha de Laura)'));
    await page.waitForTimeout(800); await revisar(page, 'Ficha de usuario Laura', null);
    if (await page.locator('[role=dialog]').count()) { await page.keyboard.press('Escape'); await page.waitForTimeout(300); }
    await ir(page, '/usuarios/sectores'); await revisar(page, 'Sectores', null);
    await dialogo(page, '+ Agregar sector', 'Diálogo agregar sector', null);
    await ir(page, '/usuarios/permisos'); await revisar(page, 'Tabla de permisos', null);
    await ir(page, '/usuarios/plazos'); await revisar(page, 'Plazos de la prioridad', null);
    log('\nconsola/red diego: ' + (page._log.join(' | ') || 'limpia'));
    await ctx.close();
  }
  {
    const { ctx, page } = await abrir(browser, 'carla', dispo);
    await ir(page, '/obras/10/presupuesto-por-rubro'); await revisar(page, 'Presupuesto por rubro (Carla)', null);
    log('   volver: ' + (await enlace(page, /Volver a la obra/)).join(' | '));
    await ir(page, '/proveedores'); await revisar(page, 'Proveedores (Carla)', null);
    await ir(page, '/proveedores/7'); await revisar(page, 'Ficha proveedor (Carla)', null);
    log('   ‹ Proveedores / contacto: ' + (await enlace(page, /Proveedores|@|^\d{3} /)).join(' | '));
    log('\nconsola/red carla: ' + (page._log.join(' | ') || 'limpia'));
    await ctx.close();
  }
  {
    const { ctx, page } = await abrir(browser, 'hernan', dispo);
    await ir(page, '/obras'); await revisar(page, 'Obras (Hernán)', null);
    await tarjetas(page, 'Hernán');
    await ir(page, '/obras/10'); await revisar(page, 'Ficha (Hernán)', null);
    log('   contactos: ' + (await enlace(page, /@|^\d{3} /)).join(' | '));
    log('\nconsola/red hernan: ' + (page._log.join(' | ') || 'limpia'));
    await ctx.close();
  }
} finally { await browser.close(); }
