# PlusPagos — WooCommerce Payment Gateway

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue?logo=wordpress)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-4.0%2B-7f54b3?logo=woocommerce)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-GPLv2-green)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.1.0-orange)](https://github.com/lucassebastianfiorio/pluspagos-gateway)

Plugin de WooCommerce que integra la pasarela de pago **PlusPagos** para aceptar pagos con tarjeta de crédito y débito en tiendas argentinas.

---

## 📋 Descripción

PlusPagos es una pasarela de pago argentina que permite a los comercios aceptar pagos con tarjetas de crédito y débito. Este plugin integra el **Botón de Pago PlusPagos** con WooCommerce mediante el método de integración POST, redirigiendo al comprador al entorno seguro de PlusPagos para completar el pago.

### ¿Cómo funciona el flujo de pago?

```
Cliente → "Realizar pedido" → WooCommerce crea la orden (on-hold)
    → Redirige automáticamente al formulario de PlusPagos
    → Cliente paga en PlusPagos
    → PlusPagos redirige de vuelta al sitio (URL de retorno propia)
    → PlusPagos envía webhook con el resultado del pago
    → WooCommerce actualiza el estado del pedido automáticamente
```

---

## ✨ Características

- ✅ Integración mediante POST con encriptación AES-256-CBC
- ✅ Firma SHA-256 para seguridad en las transacciones
- ✅ Soporte para entornos **Sandbox** y **Producción**
- ✅ Cargos adicionales configurables (fijo y/o porcentual)
- ✅ Opción para no aplicar cargos en envíos gratuitos
- ✅ URLs de retorno y cancelación personalizables
- ✅ Webhook para actualización automática del estado del pedido
- ✅ Protección contra doble envío a la pasarela (flag de sesión por orden)
- ✅ Reintento de pago habilitado si el pago es cancelado o rechazado
- ✅ Compatible con Custom Order Tables (HPOS) de WooCommerce

---

## 🔧 Requisitos

| Componente    | Versión mínima       |
| ------------- | -------------------- |
| PHP           | 7.4+                 |
| WordPress     | 5.0+                 |
| WooCommerce   | 4.0+                 |
| Extensión PHP | `openssl` habilitado |

---

## 📦 Instalación

### Opción 1: Instalación manual (recomendada)

1. Clonar o descargar este repositorio:
   ```bash
   git clone https://github.com/lucassebastianfiorio/pluspagos-gateway.git pluspagos-gateway
   ```
2. Colocar la carpeta `pluspagos-gateway` dentro de `/wp-content/plugins/`.
3. Desde el panel de WordPress, ir a **Plugins → Plugins instalados** y activar **PlusPagos**.

### Opción 2: Subir por el panel de WordPress

1. Comprimir la carpeta `pluspagos-gateway` en un archivo `.zip`.
2. Ir a **Plugins → Añadir nuevo → Subir plugin**.
3. Seleccionar el `.zip` y hacer clic en **Instalar ahora**.
4. Activar el plugin.

---

## ⚙️ Configuración

Una vez activado, ir a **WooCommerce → Ajustes → Pagos → PlusPagos** y completar los siguientes campos:

### General

| Campo                      | Descripción                                                                  |
| -------------------------- | ---------------------------------------------------------------------------- |
| **Habilitar/Deshabilitar** | Activa o desactiva el método de pago                                         |
| **Título**                 | Texto que verá el cliente en el checkout (ej: _Tarjeta de Crédito / Débito_) |
| **Descripción**            | Descripción del método de pago en el checkout                                |

### Entorno de Pruebas (Sandbox)

| Campo                    | Descripción                                     | Default                                  |
| ------------------------ | ----------------------------------------------- | ---------------------------------------- |
| **Modo Test**            | Activa el entorno de pruebas                    | No                                       |
| **URL POST Test**        | URL de la plataforma Sandbox                    | `https://sandboxpp.asjservicios.com.ar/` |
| **ID Comercio (Test)**   | GUID de comercio para Sandbox                   | —                                        |
| **Clave Secreta (Test)** | Secret Key para firma y encriptación en Sandbox | —                                        |

### Entorno de Producción

| Campo                          | Descripción                                        | Default                                |
| ------------------------------ | -------------------------------------------------- | -------------------------------------- |
| **URL POST Producción**        | URL de la plataforma productiva                    | `https://botonpp.asjservicios.com.ar/` |
| **ID Comercio (Producción)**   | GUID de comercio para Producción                   | —                                      |
| **Clave Secreta (Producción)** | Secret Key para firma y encriptación en Producción | —                                      |

### Información del Comercio

| Campo                   | Descripción                                                           |
| ----------------------- | --------------------------------------------------------------------- |
| **Nombre del Comercio** | Nombre que se muestra en PlusPagos (por defecto, el nombre del sitio) |
| **Título de artículo**  | Descripción del producto enviada a PlusPagos (ej: _Compra Online_)    |

### Cargos Adicionales

| Campo                            | Descripción                                        |
| -------------------------------- | -------------------------------------------------- |
| **Descripción Cargo Adicional**  | Texto descriptivo del cargo extra                  |
| **Cargo Fijo ($)**               | Monto fijo a sumar al total del pedido             |
| **Cargo Porcentaje (%)**         | Porcentaje a sumar sobre el total                  |
| **Desactivar en envío gratuito** | No aplica cargos si el método de envío es gratuito |

### URLs de Retorno

| Campo                  | Descripción                           | Default                           |
| ---------------------- | ------------------------------------- | --------------------------------- |
| **URL de Éxito**       | Destino tras pago exitoso             | URL de retorno interna del plugin |
| **URL de Cancelación** | Destino si el cliente cancela el pago | URL de cancelación de WooCommerce |

> **Nota:** Si se dejan vacíos, el plugin usa valores por defecto correctos. No es necesario completarlos salvo que se quiera una URL personalizada.

---

## 🔔 Configuración del Webhook en PlusPagos

Para que el estado de los pedidos se actualice automáticamente, es **imprescindible** registrar la siguiente URL en el panel de PlusPagos como URL de notificaciones:

```
https://tu-sitio.com/?wc-api=pluspagos_gateway
```

Reemplazar `tu-sitio.com` con el dominio real del sitio.

---

## 📊 Estados de pedido

El plugin mapea los estados de PlusPagos a estados de WooCommerce de la siguiente manera:

| EstadoId | Estado PlusPagos | Estado WooCommerce      |
| -------- | ---------------- | ----------------------- |
| `1`      | CREADA           | En espera (on-hold)     |
| `2`      | EN_PAGO          | En espera (on-hold)     |
| `3`      | REALIZADA ✅     | Procesando / Completado |
| `4`      | RECHAZADA        | Cancelado               |
| `7`      | EXPIRADA         | Cancelado               |
| `8`      | CANCELADA        | Cancelado               |
| `9`      | DEVUELTA         | Reembolsado             |
| `10`     | PENDIENTE        | En espera (on-hold)     |
| `11`     | VENCIDA          | Cancelado               |

> El pedido queda en **"En espera"** hasta que PlusPagos confirma el pago via webhook. Cuando llega `EstadoId=3` (REALIZADA), el pedido pasa automáticamente a **"Procesando"**.

---

## 🔐 Seguridad

El plugin implementa las medidas de seguridad especificadas por PlusPagos:

- **AES-256-CBC**: Los campos sensibles (`CallbackSuccess`, `CallbackCancel`, `Monto`, `SucursalComercio`) se encriptan usando la Secret Key del comercio como passphrase.
- **SHA-256**: Se genera una firma (_Hash_) con la IP del cliente, ID de comercio, sucursal, monto y Secret Key para validar la integridad de la transacción.
- **Flag anti-doble envío**: Cada orden se marca con `_pluspagos_payment_sent` una vez que el formulario se envía a PlusPagos, evitando que el cliente sea redirigido dos veces a la pasarela.
- **URL de retorno propia**: El `CallbackSuccess` apunta a un endpoint interno (`?pluspagos_return=1`) que valida la orden antes de redirigir a la página de confirmación.

---

## 🗂️ Estructura del plugin

```
pluspagos-gateway/
├── pluspagos-gateway.php       # Archivo principal del plugin
├── lib/
│   ├── AESEncrypter.php        # Encriptación AES-256-CBC (provista por PlusPagos)
│   └── SHA256Encript.php       # Generación de firma SHA-256 (provista por PlusPagos)
├── img/
│   └── logos-tarjetas.png      # Icono de tarjetas en el checkout
└── README.md
```

---

## 🧩 Hooks y filtros

| Hook                                     | Tipo   | Descripción                                               |
| ---------------------------------------- | ------ | --------------------------------------------------------- |
| `woocommerce_payment_gateways`           | Filter | Registra la pasarela en WooCommerce                       |
| `woocommerce_api_pluspagos_gateway`      | Action | Endpoint del webhook de PlusPagos                         |
| `woocommerce_thankyou_pluspagos_gateway` | Action | Muestra el formulario de pago en la thank-you page        |
| `woocommerce_pluspagos_icon`             | Filter | Permite personalizar el ícono de la pasarela              |
| `init`                                   | Action | Maneja el retorno desde PlusPagos (`?pluspagos_return=1`) |

---

## 🔄 Changelog

### v1.1.0

- Corrección del mapa de `EstadoId` según documentación oficial de PlusPagos
- Implementación de flag `_pluspagos_payment_sent` para evitar doble redirección a la pasarela
- Nuevo endpoint `handle_return()` para el retorno desde PlusPagos
- Soporte para `EstadoId=9` (DEVUELTA → reembolsado)
- Reset de flag en pagos cancelados/rechazados para permitir reintento

### v1.0.0

- Versión inicial
- Integración POST con PlusPagos (Sandbox y Producción)
- Encriptación AES-256-CBC y firma SHA-256
- Cargos adicionales fijos y porcentuales
- Webhook para actualización de estados

---

## 👤 Autor

**Lucas S. Fiorio**  
🌐 [lucasfiorio.tech](https://lucasfiorio.tech)

---

## 📄 Licencia

Este plugin se distribuye bajo la licencia [GPLv2 o superior](https://www.gnu.org/licenses/gpl-2.0.html), en línea con el ecosistema de WordPress.
