import path from 'node:path';
import fs from 'node:fs';
import { createRequire } from 'node:module';
const require = createRequire('B:/ProgramFiles/NexoSmart/projects/tecnoservicios_project/tecnoservicios_frontend/package.json');
export const { chromium, devices } = require('@playwright/test');

export const FRONT = 'http://localhost:5316';
export const QA4 = 'C:/Users/juanc/AppData/Local/Temp/claude/C--Users-juanc/3fd8de0f-88e6-40e4-a658-bb42248ba145/scratchpad/qa4';
export const CAPS = 'B:/ProgramFiles/NexoSmart/projects/tecnoservicios_project/tecnoservicios_backend/docs/qa/prueba-usuario/semana-1-ronda-4/capturas';
fs.mkdirSync(CAPS, { recursive: true });

export const DISPOS = {
  p7: () => ({ ...devices['Pixel 7'] }),
  se: () => ({ viewport: { width: 375, height: 667 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2,
    userAgent: devices['iPhone SE'].userAgent }),
  pc: () => ({ viewport: { width: 1366, height: 768 } }),
};

export async function abrir(browser, persona, dispo) {
  const ctx = await browser.newContext({ ...DISPOS[dispo](), storageState: `${QA4}/sesiones/${persona}.json`,
    locale: 'es-AR', timezoneId: 'America/Argentina/Buenos_Aires' });
  const page = await ctx.newPage();
  page._log = [];
  page.on('console', (m) => { if (['error', 'warning'].includes(m.type())) page._log.push(`console.${m.type()}: ${m.text().slice(0, 200)}`); });
  page.on('pageerror', (e) => page._log.push(`pageerror: ${e.message}`));
  page.on('response', (r) => { if (r.url().includes('/api/') && r.status() >= 400) page._log.push(`HTTP ${r.status()} ${r.request().method()} ${r.url().replace(/^https?:\/\/[^/]+/, '')}`); });
  return { ctx, page };
}

export async function ir(page, ruta) {
  await page.goto(FRONT + ruta, { waitUntil: 'networkidle' });
  await page.waitForTimeout(400);
}

/** Mide todos los controles visibles; devuelve los < 44 px. */
export async function medir(page, raiz = 'body') {
  return page.evaluate((raizSel) => {
    const root = document.querySelector(raizSel) ?? document.body;
    const sel = 'button, a[href], input:not([type=hidden]), select, textarea, [role=tab], [role=checkbox], [role=radio], [role=switch], summary, [tabindex]:not([tabindex="-1"])';
    const out = []; let total = 0;
    for (const el of root.querySelectorAll(sel)) {
      const st = getComputedStyle(el);
      if (st.visibility === 'hidden' || st.display === 'none') continue;
      let r = el.getBoundingClientRect();
      if (r.width === 0 || r.height === 0) continue;
      if (el.closest('[aria-hidden="true"]')) continue;
      total++;
      let objetivo = r; let via = '';
      if (el.matches('input[type=checkbox], input[type=radio]')) {
        const lab = el.closest('label') ?? (el.id && document.querySelector(`label[for="${el.id}"]`));
        if (lab) { objetivo = lab.getBoundingClientRect(); via = ' (rótulo)'; }
      }
      // sr-only / ocultos visualmente
      if (r.width <= 1 && r.height <= 1) continue;
      const enLinea = el.matches('a') && el.closest('p, li') && !el.closest('nav') && st.display === 'inline';
      if (objetivo.height < 44 || objetivo.width < 44) {
        const nombre = (el.getAttribute('aria-label') || el.innerText || el.value || el.placeholder || el.name || el.className || '').trim().replace(/\s+/g, ' ').slice(0, 50);
        out.push(`${el.tagName.toLowerCase()}${el.type ? '[' + el.type + ']' : ''}${el.getAttribute('role') ? '[role=' + el.getAttribute('role') + ']' : ''} "${nombre}" ${Math.round(objetivo.width)}×${Math.round(objetivo.height)}${via}${enLinea ? ' [link en línea]' : ''}`);
      }
    }
    return { total, chicos: out };
  }, raiz);
}

export async function desborde(page) {
  return page.evaluate(() => {
    const d = document.documentElement;
    const res = { pagina: `${d.scrollWidth}/${d.clientWidth}`, ok: d.scrollWidth <= d.clientWidth, internos: [] };
    for (const el of document.querySelectorAll('body *')) {
      const st = getComputedStyle(el);
      if (el.scrollWidth > el.clientWidth + 1 && ['auto', 'scroll'].includes(st.overflowX) && el.clientWidth > 0) {
        res.internos.push(`${el.tagName.toLowerCase()}.${String(el.className).split(' ').join('.')} ${el.scrollWidth}/${el.clientWidth}`);
      }
    }
    // elementos que se salen de la pantalla a la derecha (recortados)
    const vw = window.innerWidth; const fuera = [];
    for (const el of document.querySelectorAll('button, a[href], input, select, th, td')) {
      const r = el.getBoundingClientRect();
      if (r.width && r.right > vw + 1) {
        let p = el.parentElement, recortado = false;
        while (p) { const s = getComputedStyle(p); if (['auto', 'scroll', 'hidden'].includes(s.overflowX)) { recortado = true; break; } p = p.parentElement; }
        fuera.push(`${el.tagName.toLowerCase()} "${(el.innerText || el.value || '').trim().slice(0, 25)}" R${Math.round(r.right)}${recortado ? ' (dentro de deslizable)' : ''}`);
      }
    }
    res.fuera = fuera.slice(0, 15);
    return res;
  });
}

export async function tablas(page) {
  return page.evaluate(() => [...document.querySelectorAll('table')].map((t) => {
    const thead = t.querySelector('thead'); const st = thead ? getComputedStyle(thead) : null;
    const marco = t.closest('[class*=tabla-marco]');
    return `table ${Math.round(t.getBoundingClientRect().width)}px thead=${st ? st.display + (st.position === 'absolute' ? '/abs' : '') : 'no'} marco="${marco?.className ?? '-'}" td.display=${t.querySelector('td') ? getComputedStyle(t.querySelector('td')).display : '-'}`;
  }));
}

export async function foto(page, nombre, full = false) {
  await page.screenshot({ path: `${CAPS}/${nombre}`, fullPage: full });
}

export function salida(nombre) {
  const f = `${QA4}/CEL/${nombre}`;
  fs.writeFileSync(f, '');
  return (...a) => { const s = a.join(' '); console.log(s); fs.appendFileSync(f, s + '\n'); };
}
