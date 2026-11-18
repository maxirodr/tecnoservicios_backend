import { abrir, ctx, vigilar, contarEnvios, FRONT, sleep, texto, alertas, cap, db } from './lib.mjs';
function cuit(base) { const w = [5,4,3,2,7,6,5,4,3,2]; for (let b = base; ; b++) { const s = String(b); const r = [...s].reduce((a, d, i) => a + d * w[i], 0) % 11; const v = 11 - r; if (v === 10) continue; return s + (v === 11 ? 0 : v); } }
const n = Math.floor(Math.random() * 900) + 100;
const C = cuit(Number(`30719${String(n).padStart(3,'0')}10`.slice(0,10)));
await abrir(async (b) => {
  const c = await ctx(b, 'diego'); const p = await c.newPage(); vigilar(p, 'prov');
  const env = contarEnvios(p);
  await p.goto(`${FRONT}/proveedores/nuevo`); await p.waitForLoadState('networkidle'); await sleep(500);
  console.log('FORM:', (await texto(p)).slice(0, 800));
  const nombre = `QA4-REG Ferretería Ñandú ${n}`;
  const d = p.getByRole('dialog');
  await d.getByLabel(/^Nombre/).first().fill(nombre);
  await d.getByLabel('CUIT').fill(C);
  const rub = d.getByLabel('Rubro habitual'); const ops = await rub.locator('option').allInnerTexts(); console.log('rubros', ops.join('|'));
  await rub.selectOption({ index: 2 });
  await d.getByLabel('Contacto · teléfono').fill('291 455-9876');
  await d.getByLabel('Contacto · mail').fill(`qa4reg.${n}@proveedor-ejemplo.com`);
  await d.getByRole('button', { name: 'Guardar proveedor' }).dblclick();
  await sleep(2500);
  console.log('envíos:', env.join(' ; '), '· url', p.url(), 'alertas', await alertas(p));
  console.log((await texto(p)).slice(0, 600));
  await cap(p, 'REG-ok-alta-prov');
  const id = p.url().match(/proveedores\/(\d+)/)?.[1];
  // edición
  await p.getByRole('button', { name: 'Editar' }).or(p.getByRole('link', { name: 'Editar' })).first().click(); await sleep(800);
  const dlg = p.getByRole('dialog'); const scope = (await dlg.count()) ? dlg : p;
  console.log('EDIT:', (await scope.innerText()).slice(0, 500));
  await scope.getByLabel('Contacto · teléfono').fill('291 455-0000');
  await scope.getByLabel(/^Nombre/).first().fill(nombre + ' SRL');
  env.length = 0;
  await scope.getByRole('button', { name: /Guardar/ }).first().dblclick();
  await sleep(2500);
  console.log('envíos:', env.join(' ; '), '· url', p.url(), 'alertas', await alertas(p), 'dialogs', await p.getByRole('dialog').count());
  console.log((await texto(p)).slice(0, 600));
  await cap(p, 'REG-ok-editar-prov');
  console.log('DB', db(`select id,nombre,cuit,telefono,email,rubro_habitual_id from proveedores where id=${id}`));
});
