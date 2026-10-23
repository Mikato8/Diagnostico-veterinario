# Arquitectura

## Principios

1. **Operar en Hostinger sin fricción.** PHP puro, sin Composer, con un front controller y assets estáticos.
2. **PostgreSQL real.** Sin ORM que oculte el SQL; consultas parametrizadas y esquema versionado.
3. **Dos transportes, una API de datos.** `pdo_pgsql` cuando existe; endpoint HTTPS de Neon cuando el hosting no expone PostgreSQL.
4. **Clínica primero.** El caso es la unidad editorial; el resto (búsqueda, diferenciales, recursos) gira alrededor de él.
5. **Privacidad por diseño.** Anonimización obligatoria, consentimiento explícito y mínimo dato personal.

## Capas

```text
public/index.php
   └─ App\Core\App
        ├─ Request / Response
        ├─ Router (routes/web.php)
        ├─ Csrf
        └─ Controller
             ├─ Repository
             │    └─ Database
             │         ├─ PdoDatabase
             │         └─ HttpDatabase
             └─ View (layouts + plantillas)
```

### `app/Core`

| Clase | Responsabilidad |
| --- | --- |
| `App` | Ciclo de petición, manejo de errores, cabeceras. |
| `Router` | Rutas con parámetros `{slug}`, 404/405. |
| `Request` / `Response` | Entrada normalizada y salida con cabeceras de seguridad. |
| `Database` | Fachada diferida; elige PDO o HTTP. |
| `PdoDatabase` | `PDO_PGSQL`, placeholders `$n` traducidos a `:pN`, transacciones. |
| `HttpDatabase` | POST a `https://HOST/sql` con `Neon-Connection-String`; sin transacciones interactivas. |
| `Auth` | Sesión, roles, verificación, rate limit de login. |
| `Csrf` | Token por sesión y validación global de `POST`. |
| `Validator` | Reglas declarativas y mensajes en español. |
| `Upload` | Validación MIME/tamaño/dimensiones y re-codificación con GD. |
| `View` | Render de plantillas con layout. |
| `SqlSplitter` | Divide migraciones respetando `$$`, comillas y comentarios. |

### `app/Repositories`

- `CaseRepository`: búsqueda full-text, detalle, casos similares, CRUD editorial, comentarios, reacciones.
- `DiseaseRepository`: fichas, signos por especie y cálculo de diferenciales.
- `UserRepository`, `ResourceRepository`, `CatalogRepository`.

### `app/Controllers`

- `HomeController`, `CaseController`, `AuthController`, `ProfileController`.
- `DiagnosticController`, `ResourceController`, `PageController`.
- `AdminController`, `HealthController`, `SitemapController`.

## Flujo editorial

```text
borrador ──► en_revision ──► publicado
   ▲              │
   │              ├──► cambios_solicitados ──► en_revision
   │              └──► rechazado ───────────► en_revision
   └──────────────────────────────────────────┘
```

- El autor puede editar `borrador`, `cambios_solicitados` y `rechazado`.
- Un editor/admin puede editar y revisar cualquier estado.
- La publicación exige consentimiento, anonimización, diagnóstico final y `published_at`.
- Cada decisión se registra en `case_reviews` y `audit_log`.

## Búsqueda

`cases.search_vector` es una columna generada con `to_tsvector('spanish', …)` sobre título, motivo, anamnesis, exploración, estudios, diferenciales, diagnóstico, discusión y referencias.

```sql
where c.search_vector @@ websearch_to_tsquery('spanish', $1)
   or c.title ilike $2
   or c.final_diagnosis ilike $3
order by ts_rank_cd(c.search_vector, websearch_to_tsquery('spanish', $1)) desc
```

Índices:

- GIN sobre `search_vector`.
- GIN trigram sobre `title` y `final_diagnosis`.
- Índices parciales para casos publicados y destacados.

## Apoyo diagnóstico

Cada enfermedad tiene un perfil de signos con peso 1–5 y una marca de signo clave. El usuario selecciona hasta 20 signos.

Para cada enfermedad candidata:

```text
cobertura = signos coincidentes / signos seleccionados
perfil    = signos coincidentes / signos totales de la enfermedad
score     = 0.65 * cobertura + 0.35 * perfil
```

Se muestran:

- Coincidencia (%) y número de signos del perfil.
- Signos que coinciden.
- Signos clave que faltan por explorar.
- Urgencia y riesgo zoonótico.

El puntaje es una **heurística de similitud**, no una probabilidad diagnóstica. La interfaz lo declara explícitamente.

## Base de datos

- PostgreSQL 15+; probado en Neon PostgreSQL 18.
- Extensiones: `pg_trgm`, `citext`.
- Claves primarias `bigint generated always as identity`.
- `timestamptz` en todas las fechas.
- Restricciones `CHECK` para estados, roles, sexo, desenlace y licencias.
- Trigger `set_updated_at()` en tablas editables.
- Folio de caso con secuencia `case_folio_seq`.
- Búsqueda y similitud con índices GIN.

### Conexión

- **Aplicación:** `DATABASE_URL` agrupada (`-pooler`), recomendada para muchas conexiones cortas.
- **Migraciones:** `DATABASE_URL_UNPOOLED`, sin PgBouncer.
- **HTTP:** `DB_DRIVER=http`; cada consulta es una petición HTTPS independiente. Para operaciones multi-tabla se usan CTEs o escrituras secuenciales idempotentes.

## Recursos para no especialistas

Cada recurso externo tiene una ficha interna (`/recursos/{slug}`) con:

- `what_for`: uso concreto en lenguaje clínico.
- `how_to`: pasos ordenados.
- `requirements`: cuenta, solicitud, idioma, conocimientos.
- `guide`: contexto, limitaciones y advertencias.
- `action_label`: texto del botón externo.

Además, `/recursos/kit` ofrece plantillas DOCX generadas con `tools/generar_kit_recursos.py`, y `/recursos/glosario` explica términos técnicos. El recomendador acepta objetivo, especie y tema/enfermedad: busca fichas de enfermedad y casos del portal, y ordena los recursos externos por categoría, especie y coincidencia de texto. El objetivo es que un veterinario pueda decidir y actuar sin clonar repositorios ni instalar software.

## Seguridad

- Sesión PHP con `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS.
- Regeneración de ID al iniciar sesión.
- CSRF global en métodos mutantes.
- Rate limit: 12 fallos / 15 minutos por correo o IP.
- Autorización por rol y ownership en cada acción.
- Consultas siempre parametrizadas.
- Salida escapada con `e()`.
- Uploads: MIME real, tamaño, dimensiones, re-codificación con GD, nombre aleatorio y bloqueo de PHP.
- CSP: `default-src 'self'`; sin scripts inline ni CDNs.
- `APP_DEBUG=false` en producción; los errores se registran sin exponer detalles.

## Decisiones

| Decisión | Motivo |
| --- | --- |
| Sin Composer | Menos fricción en hosting compartido; auditoría sencilla. |
| Sin framework | El dominio es acotado; se evita peso y magia. |
| SQL explícito | Control de índices, FTS y planes de consulta. |
| HTTP de Neon | Permite PostgreSQL en hostings que sólo ofrecen PHP/MySQL. |
| Contenido en español | El portal está dirigido a médicos veterinarios de México. |
| Casos demo marcados | Permiten mostrar el flujo sin confundirlos con pacientes reales. |

## Extensiones futuras

- Revisión por pares con asignación y notificaciones.
- Exportación PDF/BibTeX.
- Búsqueda semántica con `pgvector` o Lakebase Search.
- API pública con tokens.
- Panel epidemiológico por estado/especie.
- Integración con WAHIS/SENASICA.
