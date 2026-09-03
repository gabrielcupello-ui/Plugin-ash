# Puente de autenticación entre WordPress y Google Apps Script

El plugin **ARC Portal** puede autenticar usuarios de WordPress contra un endpoint compartido de Google Apps Script y, a la inversa, emitir tokens firmados que las apps de Apps Script pueden validar.

## Modo de uso más sencillo (Opción 1)

Pasar el email del usuario logueado en WordPress como parámetro a cada app:

1. En **Ajustes > ARC Portal** activa **Pasar email de WordPress a las apps**.
2. Las URLs embebidas quedarán como: `https://script.google.com/.../exec?wp_user=usuario@ashrivercollective.com`.
3. Modifica cada app para que, si recibe `wp_user`, verifique que el email exista en su hoja de empleados y evite pedir login manual.

## Modo avanzado (Opción 2): SSO con endpoint compartido

1. Crea una nueva Web App de Apps Script (o añade un `doPost` a una app existente).
2. Pega el código de ejemplo que aparece abajo.
3. Copia la URL de despliegue en **Ajustes > ARC Portal > URL del endpoint compartido de GAS**.
4. Configura el mismo **API Secret** en WordPress y en el script.

### Acciones que debe soportar el endpoint GAS

| Acción     | Entrada                          | Respuesta esperada                         |
|------------|----------------------------------|--------------------------------------------|
| `validate` | `{ action: "validate", email }`  | `{ success: true/false, user: { ... } }`   |
| `authenticate` | `{ action: "authenticate", email, password }` | `{ success: true/false, user: { ... } }` |
| `sync_user` | `{ action: "sync_user", email, name, role, active }` | `{ success: true }` |

### Ejemplo de `doPost` en Apps Script

```javascript
function doPost(e) {
  try {
    const data = JSON.parse(e.postData.contents || '{}');
    const action = data.action;

    if (action === 'validate') {
      return jsonResponse(validateUser_(data.email));
    }

    if (action === 'authenticate') {
      return jsonResponse(authenticateUser_(data.email, data.password));
    }

    if (action === 'sync_user') {
      return jsonResponse(syncUser_(data));
    }

    if (action === 'verify_token') {
      return jsonResponse(verifyToken_(data.token, data.secret));
    }

    return jsonResponse({ success: false, error: 'Acción no soportada' });
  } catch (err) {
    return jsonResponse({ success: false, error: err.toString() });
  }
}

function jsonResponse(payload) {
  return ContentService.createTextOutput(JSON.stringify(payload))
    .setMimeType(ContentService.MimeType.JSON);
}

function validateUser_(email) {
  // Reemplaza con la lógica de tu app: buscar email en hoja Employees/Administrators.
  const user = findUserByEmail_(email);
  return { success: !!user, user: user || null };
}

function authenticateUser_(email, password) {
  const user = findUserByEmail_(email);
  if (!user) return { success: false };
  // Valida la contraseña según el hash que uses en tu app.
  const valid = verifyPassword_(password, user.password);
  return { success: valid, user: valid ? user : null };
}

function syncUser_(data) {
  // Actualiza o crea el usuario en tu base de datos de GAS.
  upsertUser_(data);
  return { success: true };
}

function verifyToken_(token, secret) {
  // Valida un token firmado generado por WordPress.
  // El token tiene el formato: email|expires|userId|hmac
  const parts = String(token || '').split('|');
  if (parts.length !== 3 && parts.length !== 4) return { success: false };
  const [email, expires, userId, hash] = parts.length === 4 ? parts : [parts[0], parts[1], parts[2], ''];
  if (Number(expires) < Date.now() / 1000) return { success: false };
  const payload = email + '|' + expires + '|' + userId;
  const expected = Utilities.computeHmacSignature(Utilities.DigestAlgorithm.SHA_256, payload, secret || 'wp-secret')
    .map(function (b) {
      var h = (b & 0xff).toString(16);
      return h.length === 1 ? '0' + h : h;
    }).join('');
  return { success: hash === expected, email: email, userId: userId };
}
```

## Tokens firmados desde WordPress

El bridge puede generar tokens firmados (`Arc_Portal_GAS_Auth_Bridge::generate_token()`). Las apps de GAS pueden usar `verify_token` para confiar en una sesión iniciada en WordPress sin pedir contraseña.

## Endpoints REST de WordPress

- `POST /wp-json/arc-portal/v1/gas-auth/validate` - Valida un email contra GAS.
- `POST /wp-json/arc-portal/v1/gas-auth/token` - Genera token firmado (requiere login).
