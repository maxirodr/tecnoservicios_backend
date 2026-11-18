// O-1.2 en el celular: "Estamos guardando. Esperá unos segundos." dentro del diálogo, que se vea entero y sin desborde.
// Uso: node cel-guardando.mjs se|p7 <n>
import { chromium, abrir, ir, foto, salida } from './lib.mjs';
import { demorar } from './foco-lib.mjs';
const dispo = process.argv[2] ?? 'se';
const n = process.argv[3] ?? '1';
const log = salida(`out-cel-guardando-${dispo}-${n}.txt`);
const browser = await chromium.launch();

const medirAviso = (page) => page.evaluate(() => {
  const d = document.querySelector('[role=dialog]');
  const a = d?.querySelector('.dialogo__aviso');
  if (!d) return 'SIN DIÁLOGO (se cerró)';
  const r = a?.getBoundingClientRect();
  const btns = [...d.querySelectorAll('.dialogo__pie button')].map((b) => { const q = b.getBoundingClientRect(); return `"${b.innerText.trim().replace(/\s+/g, ' ')}" ${Math.round(q.left)},${Math.round(q.top)} ${Math.round(q.width)}×${Math.round(q.height)}`; });
  const doc = document.documentElement;
  return `aviso="${a?.innerText ?? ''}" rect=${r ? `${Math.round(r.left)},${Math.round(r.top)} ${Math.round(r.width)}×${Math.round(r.height)}` : '-'} enPantalla=${r ? r.top >= 0 && r.bottom <= innerHeight && r.left >= 0 && r.right <= innerWidth : '-'} recortado=${a ? a.scrollWidth > a.clientWidth + 1 : '-'} · botones: ${btns.join(' | ')} · página ${doc.scrollWidth}/${doc.clientWidth} · diálogo scrollW ${d.scrollWidth}/${d.clientWidth}`;
});

async function probar(page, abrirDialogo, completar, guardarNombre, etiqueta, captura) {
  await abrirDialogo();
  const dlg = page.locator('[role=dialog]'); await dlg.waitFor();
  await completar(dlg);
  await demorar(page, 5000);
  const g = dlg.getByRole('button', { name: guardarNombre, exact: true });
  await g.scrollIntoViewIfNeeded(); await g.tap();
  await page.waitForTimeout(400);
  const c = dlg.getByRole('button', { name: 'Cancelar', exact: true });
  await c.tap({ force: true }).catch((e) => log('   (tap Cancelar falló: ' + e.message.split('\n')[0] + ')'));
  await page.waitForTimeout(300);
  log(`  [${etiqueta}] tras tocar "Cancelar" guardando: ${await medirAviso(page)}`);
  await foto(page, captura);
  // toque afuera del diálogo (arriba a la izquierda, sobre el fondo)
  await page.touchscreen.tap(5, 5); await page.waitForTimeout(300);
  log(`  [${etiqueta}] tras tocar afuera: ${await medirAviso(page)}`);
  await page.waitForTimeout(6000);
  log(`  [${etiqueta}] al terminar: diálogos=${await page.locator('[role=dialog]').count()} · avisos=${JSON.stringify(await page.locator('[role=status]').allInnerTexts())}`);
  await page.unroute('**/api/**');
}

try {
  {
    const { ctx, page } = await abrir(browser, 'hernan', dispo);
    await ir(page, '/obras/10');
    log(`# ${dispo} · Hernán · /obras/10 · documento con red lenta 5 s`);
    await probar(page,
      async () => { const b = page.getByRole('button', { name: '+ agregar certificado o documento' }); await b.scrollIntoViewIfNeeded(); await b.tap(); },
      async (dlg) => {
        await dlg.getByRole('combobox', { name: 'Tipo' }).selectOption({ label: 'Otro documento' });
        await dlg.getByRole('textbox', { name: 'Fecha' }).fill('2026-11-18');
        await dlg.getByRole('textbox', { name: 'Nombre' }).fill(`QA4-CEL Documento celular ${dispo} ${n}`);
        await dlg.getByRole('button', { name: 'Pegar link' }).tap();
        await dlg.locator('input[type=url]').fill(`https://drive.example/QA4-CEL/cel ${dispo} ${n}.pdf`);
        await dlg.getByRole('button', { name: 'Agregar link' }).tap();
        await dlg.getByText(`cel ${dispo} ${n}.pdf`).first().waitFor({ timeout: 15000 });
      }, 'Guardar', 'documento', `O-1-2-r4-${dispo}-doc-${n}.png`);
    log('  consola/red: ' + (page._log.join(' | ') || 'limpia'));
    await ctx.close();
  }
  {
    const { ctx, page } = await abrir(browser, 'carla', dispo);
    await ir(page, '/proveedores');
    log(`# ${dispo} · Carla · /proveedores · Nuevo proveedor con red lenta 5 s`);
    await probar(page,
      async () => { await page.getByRole('button', { name: '+ Nuevo proveedor' }).tap(); },
      async (dlg) => {
        await dlg.getByRole('textbox', { name: 'Nombre' }).fill(`QA4-CEL Ferretería ${dispo.toUpperCase()} ${n}`);
        await dlg.getByRole('textbox', { name: /mail/i }).fill(`ventas.qa4cel.${dispo}${n}@proveedor-ejemplo.com`).catch(() => {});
        await dlg.getByRole('textbox', { name: /tel/i }).fill('291 455-0' + (dispo === 'se' ? '41' : '42') + n).catch(() => {});
        await page.waitForTimeout(800);
      }, 'Guardar proveedor', 'proveedor', `O-1-2-r4-${dispo}-prov-${n}.png`);
    log('  consola/red: ' + (page._log.join(' | ') || 'limpia'));
    await ctx.close();
  }
} finally { await browser.close(); }
