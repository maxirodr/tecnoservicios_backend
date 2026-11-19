// Comprueba si cada storageState guardado sigue con sesión válida (GET /api/me).
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
const require = createRequire('B:/ProgramFiles/NexoSmart/projects/tecnoservicios_project/tecnoservicios_frontend/package.json');
const { request } = require('@playwright/test');
const dir = 'C:/Users/juanc/AppData/Local/Temp/claude/C--Users-juanc/3fd8de0f-88e6-40e4-a658-bb42248ba145/scratchpad/qa3/sesiones';
for (const f of fs.readdirSync(dir).filter((n) => n.endsWith('.json'))) {
  const ctx = await request.newContext({
    baseURL: 'http://localhost:8340',
    storageState: path.join(dir, f),
    extraHTTPHeaders: { Origin: 'http://localhost:5316', Referer: 'http://localhost:5316/', Accept: 'application/json' },
  });
  const r = await ctx.get('/api/me');
  console.log(f, r.status());
  await ctx.dispose();
}
