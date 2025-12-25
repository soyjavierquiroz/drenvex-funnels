# Drenvex Funnels

Plugin WordPress **router-first** para el ecosistema **Drenvex**.

`drenvex-funnels` se instala en **sitios intermedios de tráfico** (ej: `funnels.drenvex.com`) y actúa como un **router inteligente**, desacoplado del CORE y de las landings finales.

> **El router solo transporta contexto.  
> El CORE decide la verdad.  
> La landing solo captura.**

---

## 📌 Propósito

Este plugin resuelve el problema de **enlaces canónicos de afiliación** que deben:

- validar referrals
- preservar atribución **solo si es válida**
- redirigir a **landings reales en cualquier dominio**
- no perder tráfico por errores humanos
- no duplicar lógica del CORE

Ejemplo de enlace canónico compartido por un VEXER:

```

[https://funnels.drenvex.com/r/ABX92/webinar](https://funnels.drenvex.com/r/ABX92/webinar)

```

Landing real (externa al router):

```

[https://funnel.kuruk.in/inicio](https://funnel.kuruk.in/inicio)

```

---

## 🧠 Rol del Plugin

### ✅ Lo que SÍ hace

- Intercepta URLs `/r/{referral_code}/{funnel_slug}`
- Valida `referral_code` contra el **CORE Drenvex**
- Resuelve `funnel_slug → URL destino`
- Redirige (HTTP 302) agregando contexto
- Preserva tráfico incluso con referral inválido

### ❌ Lo que NO hace

- ❌ No crea usuarios WordPress
- ❌ No registra leads
- ❌ No decide atribución
- ❌ No guarda lógica de negocio
- ❌ No depende de un dominio específico
- ❌ No renderiza landings ni funnels

---

## 🧩 Arquitectura General

```

Usuario
│
▼
/r/{referral}/{funnel}
│
▼
drenvex-funnels (Router)
│
├─ referral válido   → redirect con dx_ref
└─ referral inválido → redirect SIN dx_ref
│
▼
Landing real (externa)
│
▼
Captura de leads (Piotnet / otro)
│
▼
CORE

```

---

## 🔑 Integración con el CORE

### Endpoint usado (validación de referral)

```

GET /wp-json/drenvex/v1/referral/{referral_code}

```

### Autenticación

Header obligatorio:

```

X-DX-API-KEY: TU_API_KEY_PRIVADA

````

### Respuestas relevantes

**Referral válido**
```json
{ "valid": true }
````

**Referral inválido**

```json
{ "valid": false }
```

📌 El router **no interpreta motivos**.
Solo respeta `valid: true | false`.

---

## 🔁 Reglas de Redirección (congeladas)

| Estado del referral | Comportamiento                     |
| ------------------- | ---------------------------------- |
| Válido              | Redirect con `dx_ref`              |
| Inválido            | Redirect sin `dx_ref`              |
| Error técnico       | Redirect genérico (sin atribución) |

👉 **Un referral inválido NO bloquea el tráfico.
Solo bloquea la atribución.**

---

## 🧭 URL Final Construida

### Referral válido

```
https://funnel.kuruk.in/inicio
?dx_ref=ABX92
&dx_funnel=webinar
```

### Referral inválido

```
https://funnel.kuruk.in/inicio
?dx_funnel=webinar
```

---

## 🛠️ UI de Administración

### Ubicación

En el admin de WordPress:

```
Drenvex → Funnels
```

### Función

Configurar el **mapa de funnels**:

```
funnel_slug → URL destino real
```

### Modelo de datos (wp_options)

```php
[
  'webinar'  => 'https://funnel.kuruk.in/inicio',
  'registro' => 'https://funnel.kuruk.in/registro'
]
```

📌 Persistencia simple
📌 Sin tabla propia (por ahora)
📌 Preparado para migrar si escala

---

## ✅ Validaciones en la UI

### Funnel Slug

* lowercase
* sin espacios
* único
* sanitizado (`sanitize_title`)

### URL Destino

* válida (`esc_url_raw`)
* `https` recomendado

---

## 🚨 Manejo de Errores

### Funnel slug inexistente

* Error controlado (no redirect)

### Referral inválido

* Redirect **igual**
* Sin `dx_ref`

### CORE inaccesible

* Redirect genérico
* No se pierde tráfico

---

## ⚡ Performance

* Validación cacheable (CORE)
* Redirect inmediato (302)
* Sin render
* Sin JS
* Sin dependencias visuales

Este plugin está diseñado para **funnels de alto volumen**.

---

## 🔐 Seguridad

* API Key nunca se expone al frontend
* Validación server-to-server
* Sin cookies
* Sin sesiones
* Sin dependencia del navegador

---

## 🧩 Integración con Landings

Este plugin es **agnóstico** del destino:

* WordPress (Thrive, Elementor, Gutenberg)
* Webflow
* SaaS externo
* HTML estático

La landing solo debe:

* leer `dx_ref` (si existe)
* capturar el lead
* enviar datos al CORE

---

## 🧱 Estructura del Plugin

```
drenvex-funnels/
├─ drenvex-funnels.php
├─ readme.md
├─ uninstall.php
└─ includes/
   ├─ class-plugin.php
   ├─ class-activator.php
   ├─ class-deactivator.php
   ├─ http/
   │  └─ class-core-client.php
   ├─ routing/
   │  └─ class-router.php
   └─ admin/
      └─ class-funnels-admin.php
```

---

## 🚀 Roadmap (alto nivel)

* Perfil del VEXER (UX / branding)
* Shortcodes dinámicos
* Cache avanzado
* Import/export de funnels
* Analytics por slug
* Reglas por país / campaña

Nada de esto rompe el contrato actual.

---

## 🧠 Principio Rector

> **El router no piensa.
> El CORE no presenta.
> La landing no decide.**

---

## 📄 Licencia

GPL-2.0-or-later
Proyecto privado del ecosistema **Drenvex**.
