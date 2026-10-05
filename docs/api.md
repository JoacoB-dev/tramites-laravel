# API REST v1

API chica para que otros sistemas (por ejemplo, un municipio que recibe planos) consulten si una matrícula está habilitada y el estado de los trámites. Todo bajo el prefijo `/api/v1`, siempre responde JSON.

## Autenticación

Tokens personales de **Laravel Sanctum**. Se pide un token con email y contraseña y se manda en cada llamada:

```
Authorization: Bearer <token>
Accept: application/json
```

Los tokens vencen a los 30 días. El login tiene límite de **5 intentos por minuto** (por email + IP) y el resto de la API **60 pedidos por minuto** por usuario.

### `POST /api/v1/login`

| Campo         | Tipo   | Obligatorio | Descripción                                 |
|---------------|--------|-------------|---------------------------------------------|
| `email`       | string | sí          |                                             |
| `password`    | string | sí          |                                             |
| `dispositivo` | string | no          | Nombre del token (para identificarlo luego) |

```bash
curl -s -X POST http://localhost:8081/api/v1/login \
  -H 'Accept: application/json' \
  -d 'email=empleado@demo.local&password=demo1234&dispositivo=postman'
```

`201 Created`

```json
{
  "token": "3|Qe1u...",
  "tipo": "Bearer",
  "vence": "2026-11-04T10:15:00-03:00",
  "usuario": { "nombre": "Esteban Empleado", "rol": "empleado" }
}
```

Credenciales incorrectas → `422` con `errors.email`.

### `POST /api/v1/logout`

Revoca el token con el que se hizo la llamada. `200` → `{"mensaje": "Token revocado."}`

## Matrículas

Devuelven solo datos públicos (no expone email, teléfono ni domicilio).

### `GET /api/v1/matriculas/{matricula}`

```bash
curl -s http://localhost:8081/api/v1/matriculas/AR-10234 \
  -H 'Accept: application/json' -H "Authorization: Bearer $TOKEN"
```

```json
{
  "data": {
    "matricula": "AR-10234",
    "apellido": "Gómez",
    "nombre": "Martina",
    "cuit": "27-30123456-8",
    "profesion": "Arquitecto/a",
    "estado": "activa",
    "estado_etiqueta": "Activa",
    "habilitado": true,
    "al_dia_con_cuotas": true,
    "fecha_matriculacion": "2015-03-02"
  }
}
```

`estado` puede ser `activa`, `suspendida` o `baja`. Si la matrícula no existe → `404`.

### `GET /api/v1/matriculas?cuit=27-30123456-8`

Busca por CUIT (con o sin guiones). Si el CUIT no pasa el dígito verificador → `422` con `errors.cuit`; si es válido pero no hay nadie con ese CUIT → `404`.

## Trámites

### `GET /api/v1/tramites`

- Un **profesional** ve solo sus trámites.
- **Empleados** y **admin** ven todos.

Filtro opcional: `?estado=iniciado|en_revision|observado|aprobado|rechazado` (otro valor → `422`). Paginado de a 20 (`?page=2`), con `links` y `meta` estándar de Laravel.

```json
{
  "data": [
    {
      "numero": "TR-2026-00003",
      "tipo": { "codigo": "cambio_domicilio", "nombre": "Cambio de domicilio profesional" },
      "matricula": "AR-10234",
      "estado": "iniciado",
      "estado_etiqueta": "Iniciado",
      "iniciado_en": "2026-10-03T22:06:38-03:00",
      "resuelto_en": null
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "per_page": 20, "total": 13 }
}
```

### `GET /api/v1/tramites/{numero}`

Igual que el anterior pero incluye el `historial` de cambios de estado. Si el trámite es de otro profesional → `403`; si no existe → `404`.

```json
{
  "data": {
    "numero": "TR-2026-00002",
    "estado": "observado",
    "historial": [
      { "estado": "iniciado", "observacion": "Trámite iniciado desde el portal.", "fecha": "..." },
      { "estado": "en_revision", "observacion": null, "fecha": "..." },
      { "estado": "observado", "observacion": "Falta la firma en la lámina 3 y la memoria técnica. ...", "fecha": "..." }
    ]
  }
}
```

## Errores

| Código | Cuándo                                                       |
|--------|--------------------------------------------------------------|
| 401    | Falta el token o venció (`{"message": "Unauthenticated."}`)  |
| 403    | El token es válido pero no tiene permiso sobre ese recurso   |
| 404    | No existe                                                    |
| 422    | Validación (`message` + `errors` por campo)                  |
| 429    | Se superó el límite de pedidos                               |

## Verificación pública de certificados (no es parte de la API)

`GET /verificar/{codigo}` es una página web sin login (la URL y el código están impresos en el PDF del certificado). Tiene límite de 30 pedidos por minuto por IP para que no se pueda adivinar códigos por fuerza bruta.
