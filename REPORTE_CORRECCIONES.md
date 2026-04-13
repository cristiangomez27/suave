# REPORTE_CORRECCIONES

## Alcance de la corrección
Se hizo una revisión completa del repositorio `sistema/` enfocada en:
- dependencias (`include/require`) y rutas,
- estabilidad de conexión a BD,
- helpers compartidos,
- prevención de errores 500 por funciones/rutas faltantes,
- compatibilidad retroactiva con rutas legacy.

---

## Qué archivos corregí

### 1) Núcleo de configuración y arranque
- `sistema/app/config/database.php`
  - Agregada lectura por variables de entorno (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `APP_URL`) con fallback a valores actuales.
  - Validación de disponibilidad de `mysqli` para evitar fatal críptico.
  - Conexión `mysqli` con control explícito de error y `http_response_code(500)` ante fallo real de conexión.
  - Definición segura de `APP_URL` sin redeclarar constante.

- `sistema/app/config/bootstrap.php` (**nuevo**)
  - Bootstrap centralizado que carga en orden:
    - `database.php`
    - `functions.php`
    - `permisos.php`
    - `helpers/websocket_notify.php`
  - Objetivo: reducir errores por funciones no cargadas y mantener un flujo homogéneo.

- `sistema/config/bootstrap.php` (**nuevo wrapper de compatibilidad**)
  - Mantiene compatibilidad para rutas legacy hacia bootstrap.

### 2) Helpers y compatibilidad de ejecución
- `sistema/app/helpers/greenapi_helper.php`
  - Corrección de acceso a `$_SERVER['DOCUMENT_ROOT']` con validación previa para evitar warnings/notices.
  - Mantenimiento de rutas alternativas para localizar credenciales Green API según entorno.

- `sistema/enviar_promocion.php`
  - Sustitución de `str_starts_with` por `substr(...) === '521'` para evitar fatales en entornos sin esa función.

### 3) Flujo entre módulos (includes)
Se ajustaron módulos para cargar `bootstrap.php` canónico en lugar de solo `database.php`, evitando funciones compartidas faltantes en flujo real:
- `sistema/index.php`
- `sistema/configuracion.php`
- `sistema/dashboard.php`
- `sistema/usuarios.php`
- `sistema/mensajes_cargar.php`
- `sistema/mensajes_enviar.php`
- `sistema/mensajes_estado.php`
- `sistema/promociones_whatsapp.php`
- `sistema/enviar_promocion.php`
- `sistema/recuperar_password.php`
- `sistema/reset_password.php`
- `sistema/guardar_password.php`

---

## Qué archivos nuevos creé
1. `sistema/app/config/bootstrap.php`
2. `sistema/config/bootstrap.php` (wrapper legacy)
3. `REPORTE_CORRECCIONES.md`

---

## Dependencias faltantes detectadas
- **No se detectaron** rutas estáticas de `include/require` apuntando a archivos inexistentes tras los ajustes.
- Se detectó riesgo de carga parcial de funciones compartidas cuando los módulos cargaban únicamente `database.php`; por eso se centralizó bootstrap.

---

## Qué rutas corregí
- Rutas canónicas de módulos principales:
  - de `__DIR__ . '/app/config/database.php'`
  - a `__DIR__ . '/app/config/bootstrap.php'`

- Rutas de compatibilidad:
  - agregado wrapper `sistema/config/bootstrap.php` -> `sistema/app/config/bootstrap.php`.

- Resolución de credenciales Green API:
  - tolerancia a distintos `DOCUMENT_ROOT` sin warnings.

---

## Qué errores podían causar fallo
1. **Error 500 por conexión o extensión no disponible**
   - Si `mysqli` faltaba, había fatal no controlado.
   - Ahora se responde con error explícito y código 500.

2. **Funciones compartidas no cargadas en todos los módulos**
   - Carga parcial podía causar funciones indefinidas según flujo.
   - Ahora bootstrap centraliza carga y evita ese fallo.

3. **Warnings/notices por `DOCUMENT_ROOT` no definido**
   - En ciertos contextos (CLI/cron), `$_SERVER['DOCUMENT_ROOT']` puede no existir.
   - Se corrigió con validación defensiva.

4. **Compatibilidad PHP por `str_starts_with`**
   - Potencial fatal en entornos sin esa función.
   - Reemplazado por alternativa compatible.

---

## Archivos duplicados/obsoletos (NO eliminados)
Se mantienen por compatibilidad y **no se borraron**:
- `sistema/config/database.php`
- `sistema/config/mail.php`
- `sistema/config/functions.php`
- `sistema/config/permisos.php`
- `sistema/helpers/mail.php`
- `sistema/helpers/greenapi_helper.php`
- `sistema/private/secure_greenapi.php`
- `sistema/websocket_notify.php`

> Si se confirma que no hay consumidores externos legacy, estos wrappers podrían eliminarse en una fase posterior.

---

## Qué requiere revisión manual
1. **Credenciales sensibles en código**
   - `sistema/app/config/database.php`
   - `sistema/app/config/mail.php`
   - `sistema/app/private/secure_greenapi.php`
   Recomendación: migrar a `.env` + secret manager.

2. **Prueba funcional E2E en entorno real**
   - login/logout,
   - recuperación/restablecimiento,
   - gestión de usuarios,
   - mensajería,
   - promociones/envíos.

3. **Validación de permisos por rol**
   - Confirmar reglas por rol en producción con datos reales.

---

## Estado final
- Flujo más estable y homogéneo por bootstrap central.
- Includes y dependencias estáticas sin faltantes detectados.
- Compatibilidad legacy preservada.
- Diseño visual y nombres de módulos sin cambios.
