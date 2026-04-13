# REPORTE_WHATSAPP

## Objetivo
Se corrigió y centralizó el flujo de WhatsApp/Green API en `suaveurban-sistema` para evitar credenciales repetidas, rutas rotas y errores de envío.

---

## 1) Archivos revisados y conectados
### Con cambios directos
- `sistema/enviar_promocion.php`
- `sistema/app/config/bootstrap.php`
- `sistema/app/helpers/greenapi_helper.php`

### Nuevos archivos creados
- `sistema/app/config/whatsapp.php`
- `sistema/config/whatsapp.php` (wrapper legacy)
- `sistema/app/helpers/whatsapp_helper.php` (helper común central)
- `sistema/helpers/whatsapp_helper.php` (wrapper legacy)
- `sistema/api_whatsapp_cliente.php` (endpoint de envío individual)

---

## 2) Centralización de credenciales y configuración
Se dejó una única ruta de carga de credenciales:
- `cargarCredencialesGreenApi()` en `app/helpers/greenapi_helper.php`.

Y una configuración central de host/timeout:
- `app/config/whatsapp.php`:
  - `GREENAPI_API_HOST`
  - `GREENAPI_REQUEST_TIMEOUT`

Con esto se eliminó el armado hardcodeado repetido de URL Green API en los módulos.

---

## 3) Funciones comunes nuevas (helper central)
En `app/helpers/whatsapp_helper.php`:
- `whatsappLimpiarTelefono()`
- `whatsappNormalizarTelefonoMX521()`
- `whatsappObtenerCredenciales()`
- `whatsappConstruirUrl()`
- `whatsappEnviarMensaje()`

Beneficios:
- validación de teléfono/mensaje,
- validación de credenciales,
- URL Green API en un solo lugar,
- manejo homogéneo de errores de cURL/API.

---

## 4) Correcciones aplicadas al flujo
### `enviar_promocion.php`
- Ya no arma URL ni credenciales localmente.
- Usa helper central para credenciales y envío (`whatsappObtenerCredenciales`, `whatsappEnviarMensaje`).
- Mantiene controles de sesión y validaciones de datos.

### Bootstrap global
- `app/config/bootstrap.php` ahora incluye `../helpers/whatsapp_helper.php`.
- Cualquier módulo que cargue bootstrap tiene disponibles las funciones comunes de WhatsApp.

### Endpoint nuevo
- `api_whatsapp_cliente.php` permite envío individual de mensaje con validaciones:
  - sesión,
  - método POST,
  - teléfono,
  - mensaje,
  - credenciales.

---

## 5) Manejo de errores (estabilidad)
Se añadieron respuestas controladas para evitar fallos silenciosos:
- helper inexistente,
- credenciales faltantes,
- teléfono vacío,
- mensaje vacío,
- error de cURL o error de Green API.

---

## 6) Módulos solicitados no encontrados en este repositorio
Se buscaron y **no existen** en el estado actual del repo:
- `pedidos`
- `remisiones`
- `entregas`
- `clientes`
- `api_whatsapp_cliente.php` (sí faltaba y se creó)

Nota: al no estar esos módulos en este repositorio, no fue posible conectar eventos internos de esos flujos aquí. Quedan listos para integrarse consumiendo `whatsapp_helper.php` o `api_whatsapp_cliente.php`.

---

## 7) Compatibilidad
- No se cambió diseño visual.
- Se mantuvo compatibilidad legacy creando wrappers (`config/whatsapp.php`, `helpers/whatsapp_helper.php`).
- No se eliminaron archivos existentes.

---

## 8) Validación técnica ejecutada
- Lint de todos los archivos PHP (`php -l`) sin errores.
- Escaneo de `require/include` estáticos sin dependencias faltantes.

Estado final: flujo de WhatsApp centralizado y estable para los módulos presentes en este repositorio.
