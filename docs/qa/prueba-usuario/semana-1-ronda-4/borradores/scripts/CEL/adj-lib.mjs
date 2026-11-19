// PU-212: foco con teclado en el campo de adjuntos (Pegar link, Agregar link, Cancelar, Subir archivo, quitar).
import { sonda, fmt } from './foco-lib.mjs';
export const PDF = 'C:/Users/juanc/AppData/Local/Temp/claude/C--Users-juanc/3fd8de0f-88e6-40e4-a658-bb42248ba145/scratchpad/qa4/CEL/QA4-CEL plano ñandú.pdf';

const activo = (page) => page.evaluate(() => {
  const e = document.activeElement;
  return e === document.body ? 'BODY' : `${e.tagName} "${(e.getAttribute('aria-label') || e.innerText || e.value || '').trim().replace(/\s+/g, ' ').slice(0, 50)}"`;
});

/** Tab (o Shift+Tab) hasta que el foco cumpla `re`; devuelve el recorrido. */
export async function tabHasta(page, re, { max = 15, atras = false } = {}) {
  const pasos = [];
  for (let i = 0; i < max; i++) {
    const a = await activo(page);
    if (re.test(a)) return { ok: true, pasos };
    await page.keyboard.press(atras ? 'Shift+Tab' : 'Tab'); await page.waitForTimeout(80);
    pasos.push(await activo(page));
  }
  return { ok: re.test(await activo(page)), pasos };
}

/** ¿El foco está dentro del grupo de adjuntos (fieldset) y, si hay, del diálogo? */
async function dondeEsta(page, grupoSel) {
  return page.evaluate((sel) => {
    const e = document.activeElement; const g = document.querySelector(sel); const d = document.querySelector('[role=dialog]');
    return `${e === document.body ? 'BODY' : 'ok'} · enCampo=${g ? g.contains(e) : '?'}${d ? ' · enDiálogo=' + d.contains(e) : ''}`;
  }, grupoSel);
}

/**
 * Recorre el campo `grupo` (locator de group) solo con teclado. Lo deja con un link agregado.
 * `grupoSel` = selector CSS para el fieldset (para contains()).
 */
export async function probarAdjuntos(page, grupo, grupoSel, log, { etiqueta = 'campo', link = 'https://drive.example/QA4-CEL/oferta foco.pdf', archivo = true, espera = 0 } = {}) {
  const R = {};
  const nota = async (k, txt) => { const s = fmt(await sonda(page)) + ' · ' + (await dondeEsta(page, grupoSel)); R[k] = s; log(`  [${etiqueta}] ${txt}: ${s}`); return s; };
  // 1. Pegar link → foco al campo Link; lo que se escribe queda escrito
  await grupo.getByRole('button', { name: 'Pegar link' }).focus();
  await page.keyboard.press('Enter'); await page.waitForTimeout(300);
  await nota('pegar', 'Enter en "Pegar link"');
  await page.keyboard.type('https://drive.example/QA4-CEL/escrito-sin-tocar.pdf');
  const valor = await grupo.locator('input[type=url]').first().inputValue().catch(() => '(sin campo)');
  log(`  [${etiqueta}] escrito sin mover el foco → valor del campo Link = "${valor}"`);
  R.escrito = valor;
  // 2. Cancelar → foco a "Pegar link"
  let t = await tabHasta(page, /^BUTTON "Cancelar"/);
  log(`  [${etiqueta}] Tab hasta "Cancelar": ${t.ok} (${t.pasos.join(' → ')})`);
  await page.keyboard.press('Enter'); await page.waitForTimeout(300);
  await nota('cancelar', 'Enter en "Cancelar" del link');
  // 3. Pegar link otra vez, escribir, Tab hasta Agregar link, Enter
  await page.keyboard.press('Enter'); await page.waitForTimeout(300);
  await nota('pegar2', 'Enter otra vez (debería ser "Pegar link")');
  await page.keyboard.type(link);
  t = await tabHasta(page, /^BUTTON "Agregar link/);
  log(`  [${etiqueta}] Tab hasta "Agregar link": ${t.ok} (${t.pasos.join(' → ')})`);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(250); await nota('agregando', 'Agregar link +0,25 s');
  for (let i = 0; i < 40; i++) { await page.waitForTimeout(250); if (!(await grupo.getByRole('button', { name: /Agregando/ }).count())) break; }
  await page.waitForTimeout(400);
  await nota('agregado', 'Agregar link terminado');
  await page.keyboard.press('Tab'); await page.waitForTimeout(100);
  await nota('agregadoTab', '  y un Tab');
  // 4. quitar el link agregado → foco dentro del campo
  t = await tabHasta(page, /^BUTTON "quitar|^BUTTON "Quitar/i, { atras: true, max: 6 });
  if (!t.ok) t = await tabHasta(page, /^BUTTON "quitar|^BUTTON "Quitar/i, { max: 6 });
  log(`  [${etiqueta}] Tab hasta "quitar": ${t.ok} (${t.pasos.join(' → ')})`);
  await page.keyboard.press('Enter'); await page.waitForTimeout(400);
  await nota('quitar', 'Enter en "quitar"');
  // 5. Subir archivo (selector del sistema) → foco durante y después
  if (archivo) {
    await grupo.getByRole('button', { name: 'Subir archivo' }).focus();
    const fc = page.waitForEvent('filechooser', { timeout: 5000 }).catch(() => null);
    await page.keyboard.press('Enter');
    const chooser = await fc;
    log(`  [${etiqueta}] Enter en "Subir archivo" abre el selector: ${!!chooser}`);
    if (chooser) {
      await chooser.setFiles(PDF);
      await page.waitForTimeout(200); await nota('subiendo', 'subiendo +0,2 s');
      for (let i = 0; i < 40; i++) { await page.waitForTimeout(250); if (!(await grupo.locator('.adjunto__spinner').count())) break; }
      await page.waitForTimeout(400);
      await nota('subido', 'archivo subido');
      // quitar el archivo y volver a dejar un link
      t = await tabHasta(page, /^BUTTON "quitar/i, { max: 6 });
      if (!t.ok) t = await tabHasta(page, /^BUTTON "quitar/i, { atras: true, max: 6 });
      log(`  [${etiqueta}] Tab hasta "quitar" del archivo: ${t.ok}`);
      await page.keyboard.press('Enter'); await page.waitForTimeout(400);
      await nota('quitarArchivo', 'Enter en "quitar" del archivo');
    }
  }
  // 6. dejar un link para seguir (vía teclado)
  await grupo.getByRole('button', { name: 'Pegar link' }).focus();
  await page.keyboard.press('Enter'); await page.waitForTimeout(250);
  await page.keyboard.type(link);
  await tabHasta(page, /^BUTTON "Agregar link/);
  await page.keyboard.press('Enter');
  for (let i = 0; i < 40; i++) { await page.waitForTimeout(250); if (!(await grupo.getByRole('button', { name: /Agregando/ }).count())) break; }
  await page.waitForTimeout(400);
  await nota('final', 'link final agregado');
  return R;
}
