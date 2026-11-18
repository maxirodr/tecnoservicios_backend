import { abrir, ctx, vigilar, FRONT, sleep, cap } from './lib.mjs';
const [, , ID = '9', persona = 'diego', nombre = 'REG-ok-editar-obra-historial'] = process.argv;
await abrir(async (b) => {
  const c = await ctx(b, persona); const p = await c.newPage(); vigilar(p, 'hist');
  await p.goto(`${FRONT}/obras/${ID}/editar`); await p.waitForLoadState('networkidle'); await sleep(800);
  const h = await p.locator('main').innerText();
  const i = h.search(/HISTORIAL|Historial/);
  console.log('HIST:', h.slice(i, i + 2500));
  const s = p.getByText(/Historial/).first(); await s.scrollIntoViewIfNeeded().catch(()=>{});
  await cap(p, nombre);
});
