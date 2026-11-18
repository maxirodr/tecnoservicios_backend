import { abrir, ctx, vigilar, FRONT, sleep } from './lib.mjs';
await abrir(async (b) => {
  const c = await ctx(b, 'diego'); const p = await c.newPage(); vigilar(p, 'x');
  for (const btn of ['+ agregar presupuesto adicional', '+ agregar certificado o documento', 'Cambiar estado / pasar a Terminada']) {
    await p.goto(`${FRONT}/obras/9`); await p.waitForLoadState('networkidle'); await sleep(400);
    await p.getByRole('button', { name: btn }).first().click(); await sleep(800);
    const d = p.getByRole('dialog').first();
    console.log('=== ', btn, '\n', (await d.innerText()).replace(/\n+/g, ' | '));
    console.log('inputs:', await d.locator('input,select,textarea').evaluateAll((es) => es.map((e) => `${e.tagName}[name=${e.name}][type=${e.type}] label=${e.labels?.[0]?.innerText ?? ''}`).join('\n')));
  }
});
