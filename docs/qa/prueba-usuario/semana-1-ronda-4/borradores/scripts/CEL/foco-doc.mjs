// PU-212 dentro del diálogo "Agregar certificado o documento" (Hernán, PC, solo teclado). Uso: node foco-doc.mjs <n> [lento]
import { chromium, abrir, ir, foto, salida } from './lib.mjs';
import { demorar, guardarConTeclado, sonda, fmt } from './foco-lib.mjs';
import { probarAdjuntos } from './adj-lib.mjs';
const n = process.argv[2] ?? '1';
const lento = process.argv.includes('lento');
const obra = process.env.OBRA ?? '10';
const log = salida(`out-foco-doc-${n}${lento ? '-lento' : ''}.txt`);
const browser = await chromium.launch();
try {
  const { page } = await abrir(browser, 'hernan', 'pc');
  await ir(page, `/obras/${obra}`);
  if (lento) await demorar(page);
  log(`# Hernán /obras/${obra} · documento ${n} · red ${lento ? 'lenta 2,5 s en POST' : 'normal'}`);
  await page.getByRole('button', { name: '+ agregar certificado o documento' }).focus();
  await page.keyboard.press('Enter');
  const dlg = page.locator('[role=dialog]'); await dlg.waitFor();
  await dlg.getByRole('combobox', { name: 'Tipo' }).selectOption({ label: 'Otro documento' });
  await dlg.getByRole('textbox', { name: 'Fecha' }).fill('2026-11-18');
  await dlg.getByRole('textbox', { name: 'Nombre' }).fill(`QA4-CEL Documento teclado ${n}${lento ? ' lento' : ''}`);
  const grupo = dlg.locator('fieldset.adjuntos').first();
  await grupo.evaluate((e) => e.setAttribute('data-qa', 'doc'));
  log('  grupo: ' + (await grupo.evaluate((e) => e.querySelector('legend')?.innerText ?? e.getAttribute('aria-label'))));
  const R = await probarAdjuntos(page, grupo, '[data-qa=doc]', log, { etiqueta: 'diálogo', link: `https://drive.example/QA4-CEL/doc teclado ñ ${n}.pdf` });
  await foto(page, `PU-212-r4-doc-${n}${lento ? '-lento' : ''}.png`);
  const fuera = Object.entries(R).filter(([, v]) => /BODY|enDiálogo=false|enCampo=false/.test(v)).map(([k]) => k);
  log(`  RESUMEN: pasos con foco fuera del campo/diálogo o en body = ${fuera.length ? fuera.join(', ') : 'ninguno'}`);
  await guardarConTeclado(page, dlg.getByRole('button', { name: 'Guardar', exact: true }), log, { tabs: lento });
  log(`  diálogo abierto al final: ${await page.locator('[role=dialog]').count()}`);
  log('  consola/red: ' + (page._log.join(' | ') || 'limpia'));
} finally { await browser.close(); }
