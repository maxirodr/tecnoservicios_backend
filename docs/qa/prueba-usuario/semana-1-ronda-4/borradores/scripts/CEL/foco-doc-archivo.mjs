// PU-212: "cancelar" de un archivo subiendo y "quitar" de un archivo, dentro del diálogo de documento (teclado, red lenta).
import { chromium, abrir, ir, foto, salida } from './lib.mjs';
import { demorar, sonda, fmt } from './foco-lib.mjs';
import { tabHasta, PDF } from './adj-lib.mjs';
const n = process.argv[2] ?? '1';
const log = salida(`out-foco-doc-archivo-${n}.txt`);
const browser = await chromium.launch();
try {
  const { page } = await abrir(browser, 'hernan', 'pc');
  await ir(page, '/obras/10');
  await demorar(page, 4000);
  log(`# Hernán /obras/10 · diálogo documento · archivo · red lenta 4 s en POST · corrida ${n}`);
  await page.getByRole('button', { name: '+ agregar certificado o documento' }).focus();
  await page.keyboard.press('Enter');
  const dlg = page.locator('[role=dialog]'); await dlg.waitFor();
  const subir = dlg.getByRole('button', { name: 'Subir archivo' });
  const elegir = async () => { await subir.focus(); const fc = page.waitForEvent('filechooser'); await page.keyboard.press('Enter'); (await fc).setFiles(PDF); await page.waitForTimeout(400); };
  await elegir();
  log('  subiendo: ' + fmt(await sonda(page)));
  let t = await tabHasta(page, /^BUTTON "Cancelar QA4-CEL|^BUTTON "cancelar/i, { atras: true, max: 4 });
  log(`  Shift+Tab hasta "cancelar" del archivo: ${t.ok} (${t.pasos.join(' → ')})`);
  await page.keyboard.press('Enter'); await page.waitForTimeout(400);
  log('  Enter en "cancelar": ' + fmt(await sonda(page)));
  await elegir();
  for (let i = 0; i < 40; i++) { await page.waitForTimeout(250); if (!(await dlg.locator('.adjunto__spinner').count())) break; }
  await page.waitForTimeout(400);
  log('  archivo subido: ' + fmt(await sonda(page)));
  t = await tabHasta(page, /^BUTTON "Quitar QA4-CEL/i, { atras: true, max: 4 });
  log(`  Shift+Tab hasta "quitar" del archivo: ${t.ok} (${t.pasos.join(' → ')})`);
  await page.keyboard.press('Enter'); await page.waitForTimeout(400);
  log('  Enter en "quitar" del archivo: ' + fmt(await sonda(page)));
  await foto(page, `PU-212-r4-doc-archivo-${n}.png`);
  await page.keyboard.press('Escape'); await page.waitForTimeout(500);
  log('  Escape: diálogos abiertos=' + await page.locator('[role=dialog]').count() + ' · ' + JSON.stringify((await page.locator('[role=dialog]').allInnerTexts()).map((x) => x.slice(0, 120))));
  if (await page.locator('[role=dialog]').count()) { const d = page.getByRole('button', { name: /descartar|salir sin guardar|sí/i }).first(); if (await d.count()) await d.click(); }
  log('  consola/red: ' + (page._log.join(' | ') || 'limpia'));
} finally { await browser.close(); }
