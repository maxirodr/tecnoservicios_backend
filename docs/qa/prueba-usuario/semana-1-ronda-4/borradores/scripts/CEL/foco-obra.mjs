// PU-212 en Nueva obra (Hernán, PC, solo teclado) y alta de la obra QA4-CEL. Uso: node foco-obra.mjs <n> [lento] [noguardar]
import { chromium, abrir, ir, foto, salida } from './lib.mjs';
import { demorar } from './foco-lib.mjs';
import { probarAdjuntos } from './adj-lib.mjs';
const n = process.argv[2] ?? '1';
const lento = process.argv.includes('lento');
const guardar = !process.argv.includes('noguardar');
const log = salida(`out-foco-obra-${n}${lento ? '-lento' : ''}.txt`);
const browser = await chromium.launch();
try {
  const { page } = await abrir(browser, 'hernan', 'pc');
  await ir(page, '/obras/nueva');
  if (lento) await demorar(page);
  log(`# Nueva obra · corrida ${n} · Hernán PC 1366 · red ${lento ? 'lenta 2,5 s en POST' : 'normal'}`);
  const oferta = page.getByRole('group', { name: 'Presupuesto (oferta técnica)' });
  await oferta.evaluate((e) => e.setAttribute('data-qa', 'oferta'));
  await probarAdjuntos(page, oferta, '[data-qa=oferta]', log, { etiqueta: 'oferta técnica', link: `https://drive.example/QA4-CEL/oferta foco ñ ${n}.pdf` });
  await foto(page, `PU-212-r4-obra-${n}${lento ? '-lento' : ''}.png`);
  const oc = page.getByRole('group', { name: 'OC / OT / contrato / mail de aprobación' });
  await oc.evaluate((e) => e.setAttribute('data-qa', 'oc'));
  await probarAdjuntos(page, oc, '[data-qa=oc]', log, { etiqueta: 'OC', link: `https://drive.example/QA4-CEL/oc ${n}.pdf`, archivo: n === '1' });
  if (guardar) {
    await page.getByLabel('N.º de presupuesto').fill(`2-41${n}7-2026`);
    await page.getByLabel('Nombre del trabajo').fill(`QA4-CEL Obra foco ${n}`);
    const cliente = page.getByRole('combobox', { name: 'Cliente' });
    await cliente.fill('QA4-CEL Cliente Foco'); await page.waitForTimeout(800); await page.keyboard.press('Escape');
    await page.getByRole('combobox', { name: 'Responsable de obra' }).selectOption({ label: 'Hernán · Jefatura de Proyectos' });
    await page.getByRole('textbox', { name: 'Inicio previsto' }).fill('2026-11-20');
    await page.getByRole('textbox', { name: 'Fin previsto' }).fill('2027-03-15');
    for (const [l, v] of [[/^Solicitante.*nombre/, 'QA4-CEL Ana Foco'], [/^Solicitante.*mail/, 'ana.foco.qa4cel.contacto.largo@cliente-ejemplo.com'], [/^Solicitante.*tel/, '291 400-0041'], [/^Comprador.*nombre/, 'QA4-CEL Beto Foco'], [/^Comprador.*mail/, 'beto.foco@cliente-ejemplo.com'], [/^Comprador.*tel/, '291 400-0042']]) await page.getByRole('textbox', { name: l }).fill(v);
    await page.getByRole('button', { name: 'Guardar obra' }).focus();
    await page.keyboard.press('Enter');
    await page.waitForURL(/\/obras\/\d+$/, { timeout: 20000 }).catch(() => {});
    await page.waitForTimeout(1500);
    log('  guardada → url=' + page.url().replace(/^https?:\/\/[^/]+/, '') + ' · avisos=' + JSON.stringify(await page.locator('[role=alert],[role=status]').allInnerTexts()));
  }
  log('  consola/red: ' + (page._log.join(' | ') || 'limpia'));
} finally { await browser.close(); }

