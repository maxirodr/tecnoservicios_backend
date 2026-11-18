// PU-207 con datos cargados ANTES de la migración (base de la ronda 3, recién migrada): ficha + historial.
import { createRequire } from 'node:module';
const require = createRequire('B:/ProgramFiles/NexoSmart/projects/tecnoservicios_project/tecnoservicios_frontend/package.json');
const { chromium } = require('@playwright/test');
const CAP = 'B:/ProgramFiles/NexoSmart/projects/tecnoservicios_project/tecnoservicios_backend/docs/qa/prueba-usuario/semana-1-ronda-4/capturas';
const browser = await chromium.launch();
try {
  const ctx = await browser.newContext({
    storageState: 'C:/Users/juanc/AppData/Local/Temp/claude/C--Users-juanc/3fd8de0f-88e6-40e4-a658-bb42248ba145/scratchpad/qa3/sesiones/diego.json',
    locale: 'es-AR', timezoneId: 'America/Argentina/Buenos_Aires', viewport: { width: 1366, height: 768 },
  });
  const page = await ctx.newPage();
  let n = 1;
  for (const id of [5, 9]) {
    await page.goto(`http://localhost:5316/obras/${id}/editar`);
    await page.waitForLoadState('networkidle');
    const nombres = ['__editar']; console.log('tabs', id, nombres.join(' | '));
    for (const pestaña of nombres) {
      {
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(500);
        const archivo = `${CAP}/PU-207-r4-premig-hist-${n++}.png`;
        await page.screenshot({ path: archivo, fullPage: true });
        const texto = (await page.locator('main').innerText()).replace(/\s+/g, ' ');
        const lineas = texto.match(/(agregado|quitado|Agregó|Quitó)[^.]{0,110}/gi) ?? [];
        console.log(`obra ${id} · ${pestaña} → ${archivo}\n  ${lineas.slice(0, 20).join('\n  ')}`);
      }
    }
  }
} finally {
  await browser.close();
}


