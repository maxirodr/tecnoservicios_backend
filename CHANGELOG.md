# Changelog

Cambios de cada entrega del sistema de Tecnoservicios (Etapa 1, NS-2026-025). Una entrada por
semana del roadmap firmado (cap. 14). Formato: [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).
Detalle de pruebas de cada entrega en `docs/qc/`; notas para el cliente en `docs/entregas/`.

## [Semana 1 · Base] — 2026-11-13

Entrega 1 del roadmap: ingreso, usuarios, sectores y permisos (cap. 11) · obras con sus
presupuestos por rubro y contactos (cap. 6) · proveedores (cap. 10). Arrancan 22 funciones
(F-01, 02, 03, 10, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 48, 55, 56, 58, 59, 60, 61, 68).
Aprobación de QC: `docs/qc/entrega-1.md`.

### Agregado
- **Ingreso con el mail y un código por mail** (F-01, W-16): código de 6 dígitos, 10 minutos, un
  uso, 5 intentos; bloqueo progresivo (1 h, después 24 h) tras 3 códigos quemados; sesión de 90
  días desde el último uso; "Salir".
- **Mi perfil** (F-10, W-19): nombre y teléfono editables; sector y jefatura solo a la vista.
- **Barra según el sector** (F-02, W-17) y celular básico (barra inferior, sin desplazamiento
  horizontal). Inicio abre Obras hasta que llegue Mis tareas (semana 2).
- **Usuarios** (F-58, F-61, W-15, W-39): alta, edición, activo/inactivo, sector y "depende de"
  sin círculos; quien se da de alta entra directo con su mail.
- **Sectores** (F-59, W-40): los 11 del organigrama; agregar, renombrar, sacar o archivar.
- **Tabla de permisos** (F-60, W-15): 12 módulos × sector, con la configuración inicial firmada;
  siempre queda alguien que administra usuarios y permisos.
- **Plazos de la prioridad** (F-68, W-50): urgente hasta 1 día y prioritario hasta 7 de arranque.
- **Obras** (F-15…F-23, W-05…W-07, W-20…W-23): listado por estado con buscador y responsable,
  alta (nace Confirmada, la OC no es obligatoria), ficha con Documentos y contactos, presupuestos
  adicionales, certificados y otros documentos, carpeta de Drive, edición con historial y cambio
  de estado de a un paso.
- **Presupuesto por rubro** (F-48, W-13): por rubro o solo el total, por presupuesto, con
  historial; monto del presupuesto solo para quien lo puede ver.
- **Proveedores** (F-55, F-56, W-14, W-38): base única con buscador sin tildes, alta con aviso de
  parecidos, CUIT con dígito verificador y sin repetidos, edición con registro.
- **Buscador general** (F-03, W-18): obras (N.º, cliente, planta, trabajo) y proveedores, según
  los permisos de cada uno.
- **Registro de quién hizo qué** (F-14) en obras, presupuestos, documentos, usuarios, sectores,
  permisos y proveedores.
- **Adjuntos: archivo o link** (HU-025): PDF, imágenes (también HEIC del celular), Excel, Word,
  CSV y TXT de hasta 20 MB, validados por contenido; descarga solo para quien ve la obra.

### Cambiado respecto del documento firmado (decisiones de Juani)
- **ADR-015:** se entra con un código por mail; no hay contraseña, "Olvidé mi contraseña",
  "Recordarme" ni mail de bienvenida (cambia F-01, F-10 y F-58).
- **ADR-014:** donde el documento decía "link" a un documento, ahora se puede subir el archivo o
  pegar el link (amplía D-022). La carpeta de Drive sigue siendo solo link.
- **W-15 → W-39:** el sector de una persona se cambia en su ventana de edición, no en la lista.
- **DU-A12:** Pedidos, Calendario, Gastos y Rendiciones no aparecen hasta su semana; en Obras,
  "Pedidos abiertos" y "% gastado" muestran "—".

### Seguridad
- Revisión de cierre de semana 1 (`docs/seguridad.md`): límite general de la API, cabeceras de
  seguridad globales, log de errores sin datos personales, proxies de confianza, bloqueo
  progresivo del ingreso, descarga de adjuntos con autorización del dueño (IDOR → 404).
