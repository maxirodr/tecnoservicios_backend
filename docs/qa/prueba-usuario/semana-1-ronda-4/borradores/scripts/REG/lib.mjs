// REG · ronda 4 · helpers (sin secretos en la salida).
import fs from 'node:fs'; import { execFileSync } from 'node:child_process'; import { createRequire } from 'node:module';
const require = createRequire('B:/ProgramFiles/NexoSmart/projects/tecnoservicios_project/tecnoservicios_frontend/package.json');
export const { chromium, devices } = require('@playwright/test');
export const FRONT = 'http://localhost:5316';
export const API = 'http://localhost:8340';
export const QA4 = 'C:/Users/juanc/AppData/Local/Temp/claude/C--Users-juanc/3fd8de0f-88e6-40e4-a658-bb42248ba145/scratchpad/qa4';
export const DIR = `${QA4}/REG`;
export const SES = `${QA4}/sesiones`;
export const CAP = 'B:/ProgramFiles/NexoSmart/projects/tecnoservicios_project/tecnoservicios_backend/docs/qa/prueba-usuario/semana-1-ronda-4/capturas';
export const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
export async function abrir(fn) { const browser = await chromium.launch(); try { await fn(browser); } finally { await browser.close(); } }
export async function ctx(browser, persona, extra = {}) {
  return browser.newContext({ ...(persona ? { storageState: `${SES}/${persona}.json` } : {}), locale: 'es-AR', timezoneId: 'America/Argentina/Buenos_Aires', viewport: { width: 1366, height: 768 }, ...extra });
}
export function vigilar(page, tag) {
  page.on('console', (m) => { if (['error', 'warning'].includes(m.type())) console.log(`[${tag} console.${m.type()}]`, m.text().slice(0, 200)); });
  page.on('pageerror', (e) => console.log(`[${tag} pageerror]`, e.message));
  page.on('response', (r) => { const u = r.url(); if (!u.includes('/api/')) return; const m = r.request().method(); if (m !== 'GET' || r.status() >= 400) console.log(`[${tag} net] ${m} ${u.replace(API, '')} -> ${r.status()}`); });
  page.on('dialog', (d) => { console.log(`[${tag} native dialog]`, d.type(), d.message()); d.dismiss(); });
}
export function contarEnvios(page, re = /\/api\//) { const l = []; page.on('request', (r) => { if (r.method() !== 'GET' && re.test(r.url())) l.push(`${r.method()} ${r.url().replace(API, '')}`); }); return l; }
export async function demora(page, ms, filtro = (req) => req.method() !== 'GET') { await page.route('**/api/**', async (r) => { if (filtro(r.request())) await sleep(ms); await r.fallback(); }); }
export async function texto(page) { return (await page.locator('main').innerText().catch(() => '')).replace(/\s+\n/g, '\n'); }
export async function alertas(page) { return page.locator('[role=alert],[role=status]').allInnerTexts(); }
export async function cap(page, nombre, full = true) { await page.screenshot({ path: `${CAP}/${nombre}.png`, fullPage: full }); console.log(`captura ${nombre}.png`); }
const esc = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
export const campo = (p, label) => p.getByLabel(new RegExp('^' + esc(label) + '( \\*)?$')).first();
export const db = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['--user=root', '--host=127.0.0.1', '--default-character-set=utf8mb4', 'tecnoservicios_prueba_usuario', '-N', '-e', q]).toString().trim();
// ---- sesión vencida e ingreso real (qa-lead-r4reg uid 10, r4reg2 uid 11) ----
const REDIS = 'C:/redis/redis-cli.exe'; const PREF = 'tecnoservicios_prueba_tecnoservicios_prueba_cache_';
export function redis(...a) { return execFileSync(REDIS, ['-n', '0', ...a]).toString().trim(); }
export async function claveSesion(c) {
  const ck = (await c.cookies(API)).find((x) => x.name === 'tecnoservicios-session');
  if (!ck) throw new Error('sin cookie de sesión');
  return PREF + execFileSync('C:/Php/php.exe', [`${QA4}/B/sid.php`, ck.value]).toString().trim();
}
export async function vencer(c) {
  const clave = await claveSesion(c);
  const valor = redis('GET', clave);
  const uid = valor.match(/"login_web_[0-9a-f]+":(\d+)/)?.[1] ?? valor.match(/login_web_[0-9a-f]+";i:(\d+)/)?.[1];
  if (!['10', '11'].includes(uid)) throw new Error(`no es qa-lead-r4reg/2 (uid ${uid}); no se borra`);
  const n = redis('DEL', clave);
  console.log(`vencida uid ${uid} (DEL → ${n}, EXISTS → ${redis('EXISTS', clave)})`);
  return () => redis('EXISTS', clave);
}
const BACK_LOGS = 'B:/ProgramFiles/NexoSmart/projects/tecnoservicios_project/tecnoservicios_backend/storage/logs';
function logMail() { const f = fs.readdirSync(BACK_LOGS).filter((n) => /^laravel-\d{4}-\d{2}-\d{2}\.log$/.test(n)).sort().at(-1); return `${BACK_LOGS}/${f}`; }
export async function pedirCodigo(page, quien = 'r4reg') {
  const CONT = `${DIR}/ingresos.txt`;
  const usados = fs.existsSync(CONT) ? fs.readFileSync(CONT, 'utf8').trim().split('\n').filter(Boolean).length : 0;
  if (usados >= 4) throw new Error('presupuesto de pedidos de código agotado');
  const email = `qa-lead-${quien}@empresa-ejemplo.com`;
  await page.getByRole('textbox', { name: 'Mail' }).fill(email);
  const f = logMail(); const desde = fs.statSync(f).size;
  fs.appendFileSync(CONT, `${new Date().toISOString()} ${quien}\n`);
  await page.getByRole('button', { name: 'Enviar código' }).click();
  await page.getByText(`Te mandamos un código a ${email}`).waitFor({ timeout: 20_000 });
  let c = null; const fin = Date.now() + 30000;
  while (!c && Date.now() < fin) {
    const t = fs.readFileSync(f).subarray(desde).toString('utf8');
    const mails = t.split(/^\[\d{4}-\d{2}-\d{2} [\d:]+\] /m).filter((m) => new RegExp(`^To: .*qa-lead-${quien}@`, 'mi').test(m));
    c = [...(mails.at(-1) ?? '').matchAll(/(\d{6})\s*<\/p>/g)].at(-1)?.[1]; if (!c) await sleep(300);
  }
  if (!c) throw new Error('no llegó el código');
  await page.getByRole('textbox', { name: 'Código' }).fill(c);
  await page.getByRole('button', { name: 'Ingresar' }).click();
  await page.waitForURL((u) => !u.pathname.startsWith('/ingresar'), { timeout: 30_000 });
  await page.waitForLoadState('networkidle');
  console.log(`ingresó ${quien} (pedido ${usados + 1}/4) → ${new URL(page.url()).pathname}${new URL(page.url()).search}`);
}
