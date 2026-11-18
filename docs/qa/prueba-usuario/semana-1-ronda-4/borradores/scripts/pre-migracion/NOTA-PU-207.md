# PU-207 / PU-208 con datos cargados ANTES de la migración (lo hizo el Lead antes del reset)

- Base: `tecnoservicios_prueba_usuario` con los datos de la ronda 3 (links pegados sin "Nombre para mostrar", guardados con el dominio).
- `php artisan migrate --force` corrió solo las dos pendientes: `2026_11_18_100000_nombre_de_los_links_por_la_ultima_parte` (101 ms) y `2026_11_18_100100_historial_de_documentos_quitados_sin_comillas` (0,8 ms), sin error.
- `antes.txt` / `despues.txt`: tabla `adjuntos` (links). Antes: 30 links con nombre "drive.example" / "drive.google.com". Después: nombre = última parte decodificada ("Plano nave año.pdf", "oferta tecnica ñandú.pdf", "oferta-ñandú", "sin-s", "QA3-B link lento ñ"; `https://drive.google.com/file/d/1AbCdEfGh/view` → "1AbCdEfGh").
- `actividades-antes.txt` / `actividades-despues.txt`: filas de historial con el dominio o comillas simples. Antes 22, después 1 (la que queda es un cambio de `carpeta_drive_url`, no un "agregado").
- Capturas en `semana-1-ronda-4/capturas/`:
  - `PU-207-r4-premig-1.png` (ficha obra 5) y `PU-207-r4-premig-2.png` (ficha obra 9 2-901-2026): los links se ven por el nombre del archivo.
  - `PU-207-r4-premig-hist-1.png` (Editar obra 5) y `PU-207-r4-premig-hist-2.png` (Editar obra 9): el historial dice "Archivo o link: agregado (Plano nave año.pdf)", "agregado (1AbCdEfGh)", "agregado (oferta-ñandú)", "Oferta técnica: agregado (oferta-2-823-2026…)", "Agregó el certificado QA3-C Certificado N.º 1" (sin comillas).
- Después de esto el Lead hizo `migrate:fresh --seed`: estos datos ya no existen.
