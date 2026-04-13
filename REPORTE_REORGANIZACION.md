# REPORTE_REORGANIZACION

## 1) Análisis de estructura actual
Se dejó una estructura unificada bajo `sistema/app/` para implementaciones reales, y rutas legacy como wrappers de compatibilidad:

- Implementación real (canónica):
  - `sistema/app/config/`
  - `sistema/app/helpers/`
  - `sistema/app/private/`
- Compatibilidad legacy:
  - `sistema/config/`
  - `sistema/helpers/`
  - `sistema/private/`
  - `sistema/websocket_notify.php`

Con esto, los includes antiguos siguen funcionando y el sistema puede migrarse gradualmente sin romper producción.

---

## 2) Duplicados, dependencias e includes

### Archivos duplicados (intencionales por compatibilidad)
Estos archivos ahora existen en versión canónica + wrapper legacy:

- `config/database.php` -> `app/config/database.php`
- `config/mail.php` -> `app/config/mail.php`
- `config/functions.php` -> `app/config/functions.php`
- `config/permisos.php` -> `app/config/permisos.php`
- `helpers/mail.php` -> `app/helpers/mail.php`
- `helpers/greenapi_helper.php` -> `app/helpers/greenapi_helper.php`
- `private/secure_greenapi.php` -> `app/private/secure_greenapi.php`
- `websocket_notify.php` -> `app/helpers/websocket_notify.php`

> Nota: no son duplicados "malos"; son wrappers de transición para mantener compatibilidad hacia atrás.

### Dependencias faltantes / rotas detectadas
- No se detectaron dependencias faltantes de sintaxis o includes tras la reorganización.
- Se detectó inconsistencia estructural previa: `config/functions.php`, `config/permisos.php` y `websocket_notify.php` estaban fuera de la convención canónica de `app/`.

### Includes incorrectos corregidos
- Se consolidó la estrategia de carga canónica en `sistema/app/...` y se mantuvieron wrappers legacy.
- Se creó wrapper para `websocket_notify.php` para evitar ruptura si hay código externo que hace `require_once 'websocket_notify.php'`.

---

## 3) Estructura final propuesta

```text
sistema/
  app/
    config/
      database.php
      mail.php
      functions.php
      permisos.php
    helpers/
      mail.php
      greenapi_helper.php
      websocket_notify.php
    private/
      secure_greenapi.php

  config/                 # wrappers legacy
    database.php
    mail.php
    functions.php
    permisos.php

  helpers/                # wrappers legacy
    mail.php
    greenapi_helper.php

  private/                # wrapper legacy
    secure_greenapi.php

  *.php                   # módulos/páginas públicas existentes (sin cambio visual)
```

---

## 4) Cambios aplicados

### Qué moví
- Implementación de utilidades generales a ubicación canónica:
  - `config/functions.php` -> `app/config/functions.php`
  - `config/permisos.php` -> `app/config/permisos.php`
  - `websocket_notify.php` -> `app/helpers/websocket_notify.php`

### Qué creé
- Nuevos archivos canónicos:
  - `sistema/app/config/functions.php`
  - `sistema/app/config/permisos.php`
  - `sistema/app/helpers/websocket_notify.php`

### Qué eliminé
- No se eliminó ningún archivo operativo.
- En su lugar, se transformaron rutas legacy en wrappers para compatibilidad.

### Qué rutas corregí
- `sistema/config/functions.php` ahora es wrapper a `sistema/app/config/functions.php`.
- `sistema/config/permisos.php` ahora es wrapper a `sistema/app/config/permisos.php`.
- `sistema/websocket_notify.php` ahora es wrapper a `sistema/app/helpers/websocket_notify.php`.

---

## 5) Archivos que requieren revisión manual
1. **Credenciales en texto plano**:
   - `app/config/database.php`
   - `app/config/mail.php`
   - `app/private/secure_greenapi.php`

   Recomendación: migrar a variables de entorno (`.env`) y cargar con bootstrap seguro.

2. **Wrappers legacy que podrían eliminarse más adelante (solo al confirmar no uso externo)**:
   - `sistema/config/database.php`
   - `sistema/config/mail.php`
   - `sistema/config/functions.php`
   - `sistema/config/permisos.php`
   - `sistema/helpers/mail.php`
   - `sistema/helpers/greenapi_helper.php`
   - `sistema/private/secure_greenapi.php`
   - `sistema/websocket_notify.php`

3. **Validación funcional manual sugerida** (post-reorganización):
   - Login/logout
   - Recuperación/restablecimiento de contraseña
   - Envío de mensajes/promociones
   - Panel de usuarios y dashboard

---

## Estado final
- Se unificó estructura de carpetas y includes sin romper compatibilidad.
- Se agregaron wrappers donde faltaban dependencias de transición.
- No se modificó diseño visual ni nombres de módulos visibles.
