import { chromium, abrir, ir } from './lib.mjs';
const browser = await chromium.launch();
try {
  const { page } = await abrir(browser, process.argv[2], 'pc');
  await ir(page, process.argv[3]);
  if (process.argv[4]) { await page.getByRole('button', { name: process.argv[4] }).first().click(); await page.waitForTimeout(1500); }
  const raiz = process.argv[4] ? '[role=dialog]' : 'main';
  console.log(await page.locator(raiz).last().ariaSnapshot());
} finally { await browser.close(); }
