export async function demorar(page, ms = 2500) {
  await page.route('**/api/**', async (r) => {
    if (r.request().method() !== 'GET') await new Promise((s) => setTimeout(s, ms));
    await r.fallback();
  });
}

export async function sonda(page) {
  return page.evaluate(() => {
    const e = document.activeElement;
    const dlg = document.querySelector('[role=dialog]');
    const vivos = [...document.querySelectorAll('[aria-live], [role=status], [role=alert]')]
      .map((x) => x.innerText.trim()).filter(Boolean).map((t) => t.slice(0, 60));
    const btnBusy = document.querySelector('button[aria-busy="true"]');
    return {
      foco: e === document.body ? 'BODY' : `${e.tagName}${e.getAttribute('role') ? '/' + e.getAttribute('role') : ''} "${(e.getAttribute('aria-label') || e.innerText || e.value || '').trim().replace(/\s+/g, ' ').slice(0, 40)}"`,
      busy: e.getAttribute?.('aria-busy') ?? null,
      enDialogo: dlg ? dlg.contains(e) : null,
      botonOcupado: btnBusy ? `"${btnBusy.innerText.trim().replace(/\s+/g, ' ')}" disabled=${btnBusy.disabled} aria-disabled=${btnBusy.getAttribute('aria-disabled')}` : null,
      vivos,
    };
  });
}

export const fmt = (s) => `foco=${s.foco}${s.busy ? ' [aria-busy]' : ''}${s.enDialogo === null ? '' : s.enDialogo ? ' (dentro del diálogo)' : ' (FUERA del diálogo)'} · ocupado=${s.botonOcupado ?? '—'} · vivos=${JSON.stringify(s.vivos)}`;

/** Aprieta Enter en el botón enfocado y mira el foco antes, durante (con 2 Tab) y después. */
export async function guardarConTeclado(page, boton, log, opciones = {}) {
  await boton.focus();
  log('  antes: ' + fmt(await sonda(page)));
  await page.keyboard.press('Enter');
  await page.waitForTimeout(300);
  const d1 = await sonda(page);
  log('  durante (0,3 s): ' + fmt(d1));
  // nombre accesible del botón ocupado
  const nombre = await page.locator('button[aria-busy="true"]').first().evaluate((b) => b ? b.textContent : null).catch(() => null);
  const porRol = await page.getByRole('button', { name: /Guardando/ }).count();
  log(`  nombre del botón ocupado (textContent)="${nombre?.trim()}" · getByRole(button,{name:/Guardando/}).count=${porRol}`);
  const snap = await page.locator('button[aria-busy="true"]').first().ariaSnapshot().catch(() => '(sin botón ocupado)');
  log('  ariaSnapshot del botón: ' + snap.replace(/\n/g, ' '));
  if (opciones.tabs !== false) {
    await page.keyboard.press('Tab'); await page.waitForTimeout(150);
    log('  durante, tras Tab 1: ' + fmt(await sonda(page)));
    await page.keyboard.press('Tab'); await page.waitForTimeout(150);
    log('  durante, tras Tab 2: ' + fmt(await sonda(page)));
    await page.keyboard.press('Shift+Tab'); await page.waitForTimeout(150);
    log('  durante, tras Shift+Tab: ' + fmt(await sonda(page)));
  }
  await page.waitForTimeout(1200);
  log('  durante (1,8 s): ' + fmt(await sonda(page)));
  await page.waitForTimeout(2500);
  log('  después: ' + fmt(await sonda(page)) + ' · url=' + page.url().replace(/^https?:\/\/[^/]+/, ''));
}
