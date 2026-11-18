// QA Lead · ronda 3: ingreso real por pantalla de cada persona y storageState por persona.
// Uso: node ingresar.mjs diego dora hernan ...   (crea Dora y los qa-lead si se pasa --alta)
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

const FRONT_DIR = 'B:/ProgramFiles/NexoSmart/projects/tecnoservicios_project/tecnoservicios_frontend';
const BACK_LOGS = 'B:/ProgramFiles/NexoSmart/projects/tecnoservicios_project/tecnoservicios_backend/storage/logs';
const require = createRequire(path.join(FRONT_DIR, 'package.json'));
const { chromium } = require('@playwright/test');

const FRONT = 'http://localhost:5316';
const API = 'http://localhost:8340';
const SESIONES = path.join(path.dirname(new URL(import.meta.url).pathname).replace(/^\/([A-Z]:)/, '$1'), 'sesiones');
fs.mkdirSync(SESIONES, { recursive: true });

function logMail() {
  const f = fs.readdirSync(BACK_LOGS).filter((n) => /^laravel-\d{4}-\d{2}-\d{2}\.log$/.test(n)).sort().at(-1);
  return f ? path.join(BACK_LOGS, f) : null;
}
function marca() { const f = logMail(); return { f, size: f ? fs.statSync(f).size : 0 }; }
async function codigo(email, desde) {
  const fin = Date.now() + 30_000;
  while (Date.now() < fin) {
    const f = logMail();
    if (f) {
      const inicio = f === desde.f ? desde.size : 0;
      const texto = fs.readFileSync(f).subarray(inicio).toString('utf8');
      const mails = texto.split(/^\[\d{4}-\d{2}-\d{2} [\d:]+\] /m).filter((m) => new RegExp(`^To: .*${email.replace(/[.+]/g, '\\$&')}`, 'mi').test(m));
      const c = [...(mails.at(-1) ?? '').matchAll(/(\d{6})\s*<\/p>/g)].at(-1)?.[1];
      if (c) return c;
    }
    await new Promise((r) => setTimeout(r, 300));
  }
  throw new Error(`No llegó el mail para ${email}`);
}

async function ingresar(browser, clave) {
  const email = `${clave}@empresa-ejemplo.com`;
  const ctx = await browser.newContext({ locale: 'es-AR', timezoneId: 'America/Argentina/Buenos_Aires', viewport: { width: 1366, height: 768 } });
  const page = await ctx.newPage();
  await page.goto(`${FRONT}/ingresar`);
  await page.getByRole('textbox', { name: 'Mail' }).fill(email);
  const desde = marca();
  await page.getByRole('button', { name: 'Enviar código' }).click();
  await page.getByText(`Te mandamos un código a ${email}`).waitFor({ timeout: 20_000 });
  const c = await codigo(email, desde);
  await page.getByRole('textbox', { name: 'Código' }).fill(c);
  await page.getByRole('button', { name: 'Ingresar' }).click();
  await page.waitForURL((u) => !u.pathname.startsWith('/ingresar'), { timeout: 30_000 });
  await page.waitForLoadState('networkidle');
  const archivo = path.join(SESIONES, `${clave}.json`);
  await ctx.storageState({ path: archivo });
  console.log(`ok ${clave} → ${page.url()} · ${archivo}`);
  return { ctx, page };
}

async function alta(page, nombre, email, sector_id, jefe_id = null) {
  const cookies = await page.context().cookies(API);
  const xsrf = decodeURIComponent(cookies.find((c) => c.name === 'XSRF-TOKEN')?.value ?? '');
  const r = await page.request.post(`${API}/api/usuarios`, {
    headers: { Origin: FRONT, Referer: `${FRONT}/`, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': xsrf, 'Idempotency-Key': crypto.randomUUID() },
    data: { nombre, email, sector_id, jefe_id, telefono: null },
  });
  console.log(`alta ${email} → ${r.status()} ${(await r.text()).slice(0, 160)}`);
}

const args = process.argv.slice(2);
const conAlta = args.includes('--alta');
const personas = args.filter((a) => !a.startsWith('--'));
const browser = await chromium.launch();
try {
  for (const p of personas) {
    const { ctx, page } = await ingresar(browser, p);
    if (p === 'diego' && conAlta) {
      await alta(page, 'Dora', 'dora@empresa-ejemplo.com', 1);
      await alta(page, 'QA Lead R4 A', 'qa-lead-r4a@empresa-ejemplo.com', 7);
      await alta(page, 'QA Lead R4 B', 'qa-lead-r4b@empresa-ejemplo.com', 7);
      await alta(page, 'QA Lead R4 Reg', 'qa-lead-r4reg@empresa-ejemplo.com', 7);
      await alta(page, 'QA Lead R4 Reg2', 'qa-lead-r4reg2@empresa-ejemplo.com', 5);
      await alta(page, 'QA Lead R4 BK', 'qa-lead-r4bk@empresa-ejemplo.com', 9);
      await ctx.storageState({ path: path.join(SESIONES, 'diego.json') });
    }
    await ctx.close();
  }
} finally {
  await browser.close();
}

