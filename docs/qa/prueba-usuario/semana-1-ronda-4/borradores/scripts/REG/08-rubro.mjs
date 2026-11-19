import { abrir, ctx, vigilar, contarEnvios, FRONT, sleep, alertas, cap, db } from './lib.mjs';
const OBRA = '9';
const al = async (p) => (await alertas(p)).filter(Boolean).map((t) => t.replace(/\n+/g, ' ¶ '));
const est = () => db(`select group_concat(concat(p.numero,':',p.modo_carga,':',ifnull(p.monto_obra,'-'),':',ifnull((select group_concat(concat(r.clave,'=',m.monto) order by r.id) from presupuesto_montos m join rubros r on r.id=m.rubro_id where m.presupuesto_id=p.id),'-')) separator ' || ') from presupuestos p where p.obra_id=${OBRA}`);
await abrir(async (b) => {
  const cA = await ctx(b, 'diego'), cB = await ctx(b, 'hernan');
  const A = await cA.newPage(); vigilar(A, 'A'); const env = contarEnvios(A);
  await A.goto(`${FRONT}/obras/${OBRA}/presupuesto-por-rubro`); await A.getByRole('button', { name: 'Guardar' }).waitFor(); await sleep(600);
  const sels = A.locator('select'); console.log('modos:', await sels.first().locator('option').allInnerTexts());
  const P = '2-981-2026 REV01', Q = '2-981-2026 A1 REV02';
  await A.getByLabel(`MATERIALES · ${P}`).fill('1.234.567,89');
  await A.getByLabel(`INSUMOS · ${P}`).fill('100000');
  await A.getByLabel(`MATERIALES · ${Q}`).fill('0,11');
  await sels.nth(1).selectOption({ index: 1 }); await sleep(600);
  const dl = A.getByRole('dialog'); if (await dl.count()) { console.log('DIALOGO modo:', (await dl.innerText()).replace(/\n+/g,' | ')); await cap(A, 'REG-ok-rubro-modo-dialogo', false); await dl.getByRole('button').last().click(); await sleep(500); }
  console.log('tras cambiar modo adic:', (await A.locator('thead').innerText()).replace(/\n+/g,' | '));
  const lbls = await A.locator('input').evaluateAll((es) => es.map((e) => e.getAttribute('aria-label') || e.labels?.[0]?.innerText).filter(Boolean));
  console.log('inputs:', lbls.join(' ; '));
  const mo = lbls.find((l) => l.includes(Q) && /Monto/.test(l)); if (mo) await A.getByLabel(mo).fill('500.000,5');
  await sleep(300);
  console.log('total fila/obra:', (await A.locator('tfoot').innerText().catch(() => '')).replace(/\n+/g,' | '));
  await A.getByRole('button', { name: 'Guardar' }).dblclick(); await sleep(3500);
  console.log('envíos:', env.join(' ; '), 'alertas', await al(A)); console.log('DB:', est());
  await cap(A, 'REG-ok-rubro-guardado');
  // recargar
  await A.reload(); await A.getByRole('button', { name: 'Guardar' }).waitFor(); await sleep(800);
  console.log('tras F5 MATERIALES P =', await A.getByLabel(`MATERIALES · ${P}`).inputValue(), 'tfoot', (await A.locator('tfoot').innerText().catch(()=>'')).replace(/\n+/g,' | '));
  // conflicto: Hernán guarda otro rubro en P
  const B = await cB.newPage(); vigilar(B, 'B');
  await B.goto(`${FRONT}/obras/${OBRA}/presupuesto-por-rubro`); await B.getByRole('button', { name: 'Guardar' }).waitFor(); await sleep(600);
  await B.getByLabel(`LOGISTICA · ${P}`).fill('77.000'); await B.getByRole('button', { name: 'Guardar' }).click(); await sleep(3000);
  console.log('B:', await al(B));
  await A.getByLabel(`EPP · ${P}`).fill('5.000'); env.length = 0;
  await A.getByRole('button', { name: 'Guardar' }).click(); await sleep(3000);
  console.log('A conflicto:', env.join(';'), await al(A), 'Actualizar:', await A.getByRole('button', { name: 'Actualizar' }).count());
  await cap(A, 'REG-ok-rubro-409', false);
  await A.getByRole('button', { name: 'Actualizar' }).click(); await sleep(2500);
  console.log('A tras Actualizar: LOG=', await A.getByLabel(`LOGISTICA · ${P}`).inputValue(), 'EPP=', await A.getByLabel(`EPP · ${P}`).inputValue(), await al(A));
  // salir tras Actualizar sin cambios: no debe preguntar
  await A.getByRole('link', { name: '‹ Volver a la obra' }).click(); await sleep(1500);
  console.log('salida tras Actualizar →', new URL(A.url()).pathname, 'dlg', await A.getByRole('dialog').allInnerTexts());
  // volver, cambiar, salir: debe preguntar
  await A.goBack(); await A.getByRole('button', { name: 'Guardar' }).waitFor(); await sleep(800);
  await A.getByLabel(`EPP · ${P}`).fill('6.000');
  await A.getByRole('link', { name: 'Proveedores' }).click(); await sleep(1000);
  console.log('salida con cambios →', new URL(A.url()).pathname, 'dlg', (await A.getByRole('dialog').allInnerTexts()).map((t)=>t.replace(/\n+/g,' | ')));
  await cap(A, 'REG-ok-rubro-salida', false);
  console.log('DB final:', est());
});
