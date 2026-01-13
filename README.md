# 📖 DOCUMENTACIÓN BACKEND - SISTEMA DE RESTAURANTE

## 🏗️ ARQUITECTURA DEL PROYECTO

### Stack Tecnológico
- **Framework:** Laravel 11
- **Base de datos:** MySQL 8.0
- **Autenticación:** JWT (php-open-source-saver/jwt-auth 2.8.3)
- **Arquitectura:** API RESTful
- **Entorno:** XAMPP (PHP 8.2.12)

### Estructura de Carpetas
```
restauranteback/
├── app/
│   ├── Http/
│   │   ├── Controllers/API/
│   │   │   ├── AuthController.php
│   │   │   ├── CategoriaController.php
│   │   │   ├── ProductoController.php
│   │   │   ├── MesaController.php
│   │   │   ├── OrdenController.php
│   │   │   ├── ComprobanteController.php
│   │   │   └── CajaController.php
│   │   └── Middleware/
│   │       └── CheckRole.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Rol.php
│   │   ├── Categoria.php
│   │   ├── Producto.php
│   │   ├── Mesa.php
│   │   ├── Orden.php
│   │   ├── DetalleOrden.php
│   │   ├── Comprobante.php
│   │   ├── Caja.php
│   │   └── ConfiguracionNegocio.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── config/
│   ├── auth.php
│   └── jwt.php
└── .env
```

---

## 🗄️ BASE DE DATOS

### Diagrama de Relaciones
```
roles (1) ←──── (N) users
categorias (1) ←──── (N) productos
mesas (1) ←──── (N) ordenes
users (1) ←──── (N) ordenes
users (1) ←──── (N) cajas
ordenes (1) ←──── (N) detalle_ordenes
productos (1) ←──── (N) detalle_ordenes
ordenes (1) ←──── (1) comprobantes
```

### Tablas

#### 1. **roles**
Almacena los roles del sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID autoincremental |
| nombre | varchar(50) | Nombre del rol |
| descripcion | text | Descripción del rol |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

**Roles por defecto:**
- Administrador
- Cajero
- Mesero
- Cocinero

---

#### 2. **users**
Usuarios del sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID autoincremental |
| rol_id | bigint | FK a roles |
| nombre | varchar(100) | Nombre |
| apellido | varchar(100) | Apellido |
| email | varchar(255) | Email único |
| password | varchar(255) | Contraseña hasheada |
| telefono | varchar(20) | Teléfono |
| activo | boolean | Estado activo/inactivo |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

**Usuarios de prueba:**
- admin@restaurante.com / admin123 (Administrador)
- cajero@restaurante.com / cajero123 (Cajero)
- mesero@restaurante.com / mesero123 (Mesero)

---

#### 3. **categorias**
Categorías de productos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID autoincremental |
| nombre | varchar(100) | Nombre único |
| descripcion | text | Descripción |
| orden | integer | Orden de visualización |
| activo | boolean | Estado activo/inactivo |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

**Categorías por defecto:**
- Entradas
- Platos de fondo
- Bebidas
- Postres
- Extras

---

#### 4. **productos**
Productos del restaurante (platos y productos comprados).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID autoincremental |
| categoria_id | bigint | FK a categorias |
| nombre | varchar(150) | Nombre del producto |
| descripcion | text | Descripción |
| precio_venta | decimal(10,2) | Precio de venta |
| tipo_producto | enum | 'preparado', 'comprado' |
| precio_compra | decimal(10,2) | Precio de compra (nullable) |
| stock_actual | integer | Stock actual (nullable) |
| stock_minimo | integer | Stock mínimo (nullable) |
| unidad_medida | varchar(20) | Unidad de medida (nullable) |
| sku | varchar(50) | Código SKU (nullable) |
| imagen | varchar(255) | URL de imagen (nullable) |
| activo | boolean | Estado activo/inactivo |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

**Diferencia entre tipos:**
- **preparado:** Platos que se preparan en cocina (no requieren control de stock)
- **comprado:** Productos que se compran (bebidas, etc.) con control de inventario

---

#### 5. **mesas**
Mesas del restaurante.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID autoincremental |
| numero | varchar(10) | Número de mesa único |
| capacidad | integer | Capacidad de personas |
| ubicacion | varchar(50) | Ubicación (Salón, Terraza, etc.) |
| estado | enum | 'libre', 'ocupada', 'reservada', 'mantenimiento' |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

---

#### 6. **ordenes**
Órdenes de pedidos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID autoincremental |
| mesa_id | bigint | FK a mesas (nullable) |
| usuario_id | bigint | FK a users (mesero/cajero) |
| estado | enum | 'pendiente', 'en_preparacion', 'servido', 'pagado', 'cancelado' |
| subtotal | decimal(10,2) | Subtotal sin impuestos |
| descuento | decimal(10,2) | Descuento aplicado |
| impuesto | decimal(10,2) | IGV (18%) |
| total | decimal(10,2) | Total a pagar |
| tipo_servicio | enum | 'salon', 'delivery', 'para_llevar' |
| numero_personas | integer | Número de comensales |
| notas | text | Notas especiales |
| pagado_at | timestamp | Fecha de pago |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

---

#### 7. **detalle_ordenes**
Detalle de productos por orden.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID autoincremental |
| orden_id | bigint | FK a ordenes |
| producto_id | bigint | FK a productos |
| cantidad | integer | Cantidad |
| precio_unitario | decimal(10,2) | Precio al momento del pedido |
| subtotal | decimal(10,2) | Cantidad * precio_unitario |
| notas | text | Notas del producto |
| estado | enum | 'pendiente', 'preparando', 'listo', 'servido' |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

---

#### 8. **comprobantes**
Boletas y facturas generadas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID autoincremental |
| orden_id | bigint | FK a ordenes |
| tipo | enum | 'boleta', 'factura', 'ninguno' |
| serie | varchar(10) | Serie del comprobante |
| numero | integer | Número correlativo |
| cliente_documento | varchar(20) | DNI/RUC del cliente |
| cliente_nombre | varchar(255) | Nombre del cliente |
| cliente_direccion | varchar(255) | Dirección del cliente |
| subtotal | decimal(10,2) | Subtotal |
| igv | decimal(10,2) | Impuesto IGV |
| total | decimal(10,2) | Total |
| metodo_pago | enum | 'efectivo', 'tarjeta', 'yape', 'plin', 'transferencia' |
| estado | enum | 'emitido', 'anulado' |
| motivo_anulacion | varchar(255) | Motivo de anulación |
| anulado_at | timestamp | Fecha de anulación |
| created_at | timestamp | Fecha de emisión |
| updated_at | timestamp | Fecha de actualización |

---

#### 9. **cajas**
Control de caja por turno.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID autoincremental |
| usuario_id | bigint | FK a users (cajero) |
| fecha_apertura | timestamp | Fecha/hora de apertura |
| fecha_cierre | timestamp | Fecha/hora de cierre |
| monto_inicial | decimal(10,2) | Monto inicial |
| monto_esperado | decimal(10,2) | Monto esperado (calculado) |
| monto_real | decimal(10,2) | Monto real contado |
| diferencia | decimal(10,2) | Diferencia (real - esperado) |
| notas | text | Notas del cierre |
| estado | enum | 'abierta', 'cerrada' |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

---

#### 10. **configuracion_negocio**
Configuración general del sistema (tabla única).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID = 1 (único registro) |
| nombre_negocio | varchar(255) | Nombre del restaurante |
| ruc | varchar(11) | RUC |
| direccion | varchar(255) | Dirección |
| telefono | varchar(20) | Teléfono |
| email | varchar(255) | Email |
| logo | varchar(255) | URL del logo |
| emite_boletas | boolean | Puede emitir boletas |
| emite_facturas | boolean | Puede emitir facturas |
| serie_boleta | varchar(10) | Serie para boletas |
| serie_factura | varchar(10) | Serie para facturas |
| numero_actual_boleta | integer | Último número de boleta |
| numero_actual_factura | integer | Último número de factura |
| configuracion_completada | boolean | Wizard completado |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

---

## 🔌 ENDPOINTS API

### Base URL
```
http://localhost:8000/api
```

---

## 🔐 AUTENTICACIÓN

### POST `/auth/login`
Iniciar sesión y obtener token JWT.

**Request:**
```json
{
  "email": "admin@restaurante.com",
  "password": "admin123"
}
```

**Response (200):**
```json
{
  "success": true,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
  "token_type": "bearer",
  "expires_in": 28800,
  "user": {
    "id": 1,
    "nombre": "Admin",
    "apellido": "Sistema",
    "email": "admin@restaurante.com",
    "rol": {
      "id": 1,
      "nombre": "Administrador"
    }
  }
}
```

**Response (401):**
```json
{
  "success": false,
  "message": "Credenciales incorrectas"
}
```

---

### GET `/auth/me`
Obtener información del usuario autenticado.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "nombre": "Admin",
    "apellido": "Sistema",
    "email": "admin@restaurante.com",
    "telefono": "999888777",
    "rol": {
      "id": 1,
      "nombre": "Administrador"
    },
    "activo": true
  }
}
```

---

### POST `/auth/logout`
Cerrar sesión e invalidar token.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Sesión cerrada correctamente"
}
```

---

## 📦 CATEGORÍAS

### GET `/categorias`
Listar todas las categorías activas.

**Permisos:** Todos los usuarios autenticados

**Response (200):**
```json
{
  "success": true,
  "categorias": [
    {
      "id": 1,
      "nombre": "Entradas",
      "descripcion": "Aperitivos y entradas",
      "orden": 1,
      "activo": true
    }
  ]
}
```

---

### GET `/categorias/con-productos`
Obtener categorías con sus productos.

**Response (200):**
```json
{
  "success": true,
  "categorias": [
    {
      "id": 1,
      "nombre": "Entradas",
      "productos": [
        {
          "id": 1,
          "nombre": "Ceviche",
          "precio_venta": 30.00
        }
      ]
    }
  ]
}
```

---

### GET `/categorias/{id}`
Obtener una categoría específica.

---

### POST `/categorias`
Crear nueva categoría.

**Permisos:** Solo Administrador

**Request:**
```json
{
  "nombre": "Pizzas",
  "descripcion": "Pizzas artesanales",
  "orden": 6
}
```

---

### PUT `/categorias/{id}`
Actualizar categoría.

**Permisos:** Solo Administrador

---

### DELETE `/categorias/{id}`
Desactivar categoría.

**Permisos:** Solo Administrador

---

## 🍽️ PRODUCTOS

### GET `/productos`
Listar productos activos.

**Query params:**
- `categoria_id` (opcional): Filtrar por categoría
- `tipo_producto` (opcional): 'preparado' o 'comprado'

**Response (200):**
```json
{
  "success": true,
  "productos": [
    {
      "id": 3,
      "categoria_id": 2,
      "nombre": "Lomo Saltado",
      "descripcion": "Plato tradicional peruano",
      "precio_venta": 25.00,
      "tipo_producto": "preparado",
      "categoria": {
        "id": 2,
        "nombre": "Platos de fondo"
      }
    }
  ]
}
```

---

### GET `/productos/{id}`
Obtener producto específico.

---

### POST `/productos`
Crear nuevo producto.

**Permisos:** Administrador, Cajero

**Request (producto preparado):**
```json
{
  "categoria_id": 2,
  "nombre": "Arroz con pollo",
  "descripcion": "Arroz amarillo con pollo guisado",
  "precio_venta": 18.00,
  "tipo_producto": "preparado"
}
```

**Request (producto comprado):**
```json
{
  "categoria_id": 3,
  "nombre": "Coca Cola 500ml",
  "precio_compra": 2.50,
  "precio_venta": 5.00,
  "stock_actual": 50,
  "stock_minimo": 10,
  "unidad_medida": "unidad",
  "sku": "BEB-CC-500",
  "tipo_producto": "comprado"
}
```

---

### PUT `/productos/{id}`
Actualizar producto.

**Permisos:** Administrador, Cajero

---

### DELETE `/productos/{id}`
Desactivar producto.

**Permisos:** Administrador, Cajero

---

### GET `/productos/stock-bajo`
Productos con stock bajo o agotado.

**Response (200):**
```json
{
  "success": true,
  "productos": [
    {
      "id": 7,
      "nombre": "Inca Kola 500ml",
      "stock_actual": 5,
      "stock_minimo": 10
    }
  ],
  "total": 1
}
```

---

### PATCH `/productos/{id}/stock`
Actualizar stock de un producto.

**Request:**
```json
{
  "stock_actual": 25
}
```

---

## 🪑 MESAS

### GET `/mesas`
Listar todas las mesas.

**Query params:**
- `estado` (opcional): 'libre', 'ocupada', 'reservada', 'mantenimiento'
- `ubicacion` (opcional): Filtrar por ubicación

**Response (200):**
```json
{
  "success": true,
  "mesas": [
    {
      "id": 1,
      "numero": "1",
      "capacidad": 4,
      "ubicacion": "Salón principal",
      "estado": "libre",
      "orden_activa": null
    }
  ]
}
```

---

### GET `/mesas/{id}`
Obtener mesa específica con su orden activa.

---

### POST `/mesas`
Crear nueva mesa.

**Permisos:** Solo Administrador

**Request:**
```json
{
  "numero": "14",
  "capacidad": 4,
  "ubicacion": "Salón principal",
  "estado": "libre"
}
```

---

### PUT `/mesas/{id}`
Actualizar mesa.

**Permisos:** Solo Administrador

---

### DELETE `/mesas/{id}`
Eliminar mesa (solo si no tiene historial).

**Permisos:** Solo Administrador

---

### PATCH `/mesas/{id}/estado`
Cambiar estado de una mesa.

**Request:**
```json
{
  "estado": "ocupada"
}
```

---

### GET `/mesas/libres`
Obtener solo mesas libres.

---

### GET `/mesas/ocupadas`
Obtener mesas ocupadas con sus órdenes.

---

### GET `/mesas/resumen`
Resumen del estado de todas las mesas.

**Response (200):**
```json
{
  "success": true,
  "resumen": {
    "total": 13,
    "libres": 8,
    "ocupadas": 3,
    "reservadas": 1,
    "mantenimiento": 1,
    "porcentaje_ocupacion": 23.08
  }
}
```

---

## 📝 ÓRDENES

### GET `/ordenes`
Listar órdenes.

**Query params:**
- `estado` (opcional): Filtrar por estado
- `fecha` (opcional): Filtrar por fecha (YYYY-MM-DD)
- `todas` (opcional): Incluir todas las órdenes históricas

**Por defecto:** Solo órdenes del día actual

**Response (200):**
```json
{
  "success": true,
  "ordenes": [
    {
      "id": 1,
      "mesa_id": 1,
      "usuario_id": 3,
      "estado": "servido",
      "subtotal": 60.00,
      "impuesto": 10.80,
      "total": 70.80,
      "tipo_servicio": "salon",
      "numero_personas": 2,
      "mesa": {
        "numero": "1"
      },
      "usuario": {
        "nombre": "Juan",
        "apellido": "Mesero"
      },
      "detalles": [
        {
          "producto_id": 3,
          "nombre": "Lomo Saltado",
          "cantidad": 2,
          "precio_unitario": 25.00,
          "subtotal": 50.00
        }
      ]
    }
  ]
}
```

---

### GET `/ordenes/{id}`
Obtener orden específica con detalles completos.

---

### POST `/ordenes`
Crear nueva orden.

**Permisos:** Administrador, Mesero, Cajero

**Request:**
```json
{
  "mesa_id": 1,
  "tipo_servicio": "salon",
  "numero_personas": 4,
  "notas": "Sin cebolla en el lomo",
  "productos": [
    {
      "producto_id": 3,
      "cantidad": 2,
      "notas": "Término medio"
    },
    {
      "producto_id": 6,
      "cantidad": 2
    }
  ]
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Orden creada exitosamente",
  "orden": {
    "id": 5,
    "mesa_id": 1,
    "estado": "pendiente",
    "total": 70.80
  }
}
```

---

### POST `/ordenes/{id}/productos`
Agregar productos a una orden existente.

**Request:**
```json
{
  "productos": [
    {
      "producto_id": 10,
      "cantidad": 1
    }
  ]
}
```

---

### PATCH `/ordenes/{id}/estado`
Cambiar estado de una orden.

**Request:**
```json
{
  "estado": "en_preparacion"
}
```

**Estados válidos:**
- `pendiente`
- `en_preparacion`
- `servido`
- `pagado`
- `cancelado`

---

### POST `/ordenes/{id}/cancelar`
Cancelar una orden (devuelve stock).

---

### GET `/ordenes/activas`
Obtener órdenes activas (pendientes, en preparación, servido).

---

### GET `/ordenes/cocina`
Vista para cocina (órdenes pendientes y en preparación).

---

## 🧾 COMPROBANTES

### GET `/comprobantes`
Listar comprobantes emitidos.

**Permisos:** Administrador, Cajero

**Query params:**
- `tipo` (opcional): 'boleta', 'factura', 'ninguno'
- `fecha` (opcional): Filtrar por fecha
- `metodo_pago` (opcional): Filtrar por método de pago

**Por defecto:** Comprobantes del día

---

### GET `/comprobantes/{id}`
Obtener comprobante con detalles de la orden.

---

### POST `/comprobantes/generar`
Generar comprobante para una orden.

**Permisos:** Administrador, Cajero

**Request:**
```json
{
  "orden_id": 5,
  "tipo": "boleta",
  "metodo_pago": "efectivo",
  "cliente_documento": "72345678",
  "cliente_nombre": "Juan Pérez",
  "cliente_direccion": "Av. Principal 123"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Comprobante generado exitosamente",
  "comprobante": {
    "id": 1,
    "tipo": "boleta",
    "serie": "B001",
    "numero": 1,
    "total": 70.80,
    "estado": "emitido"
  }
}
```

---

### POST `/comprobantes/{id}/anular`
Anular un comprobante.

**Request:**
```json
{
  "motivo_anulacion": "Error en el monto"
}
```

---

### GET `/comprobantes/resumen-dia`
Resumen de comprobantes del día.

**Response (200):**
```json
{
  "success": true,
  "resumen": {
    "fecha": "2026-01-13",
    "boletas": {
      "cantidad": 12,
      "total": "850.00"
    },
    "facturas": {
      "cantidad": 3,
      "total": "420.00"
    },
    "total_general": "1270.00",
    "por_metodo_pago": [
      {
        "metodo_pago": "efectivo",
        "total": "650.00"
      },
      {
        "metodo_pago": "tarjeta",
        "total": "620.00"
      }
    ]
  }
}
```

---

## 💰 CAJA

### GET `/caja/actual`
Obtener caja actual abierta del usuario autenticado.

**Permisos:** Administrador, Cajero

**Response (200):**
```json
{
  "success": true,
  "caja": {
    "id": 1,
    "usuario_id": 2,
    "fecha_apertura": "2026-01-13T08:00:00",
    "monto_inicial": 200.00,
    "ventas_efectivo": 650.00,
    "monto_esperado": 850.00,
    "estado": "abierta"
  }
}
```

---

### POST `/caja/abrir`
Abrir una nueva caja.

**Request:**
```json
{
  "monto_inicial": 200.00
}
```

---

### POST `/caja/{id}/cerrar`
Cerrar la caja actual.

**Request:**
```json
{
  "monto_real": 845.50,
  "notas": "Faltó S/4.50, verificar cambio de S/5"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Caja cerrada exitosamente",
  "caja": {
    "id": 1,
    "monto_inicial": 200.00,
    "monto_esperado": 850.00,
    "monto_real": 845.50,
    "diferencia": -4.50,
    "estado": "cerrada"
  }
}
```

---

### GET `/caja/historial`
Historial de cajas cerradas.

**Query params:**
- `fecha_desde` (opcional)
- `fecha_hasta` (opcional)
- `estado` (opcional)

**Nota:** Los usuarios solo ven sus propias cajas. Administrador ve todas.

---

### GET `/caja/{id}`
Obtener detalle de una caja específica.

---

## 🔐 MIDDLEWARE Y PERMISOS

### Middleware CheckRole

Protege rutas según el rol del usuario.

**Uso en rutas:**
```php
Route::middleware('role:Administrador')->group(function () {
    // Solo administradores
});

Route::middleware('role:Administrador,Cajero')->group(function () {
    // Administradores y Cajeros
});
```

**Response (403):**
```json
{
  "success": false,
  "message": "No tienes permisos para acceder a este recurso",
  "rol_requerido": ["Administrador"],
  "tu_rol": "Mesero"
}
```

---

## 👥 PERMISOS POR ROL

| Recurso | Administrador | Cajero | Mesero | Cocinero |
|---------|--------------|--------|--------|----------|
| **Categorías CRUD** | ✅ | ❌ Lectura | ❌ Lectura | ❌ Lectura |
| **Productos CRUD** | ✅ | ✅ | ❌ Lectura | ❌ Lectura |
| **Stock productos** | ✅ | ✅ | ❌ | ❌ |
| **Mesas CRUD** | ✅ | ❌ Lectura + Estado | ❌ Lectura + Estado | ❌ |
| **Órdenes CRUD** | ✅ | ✅ | ✅ | ❌ Ver cocina |
| **Comprobantes** | ✅ | ✅ | ❌ | ❌ |
| **Caja** | ✅ Ver todas | ✅ Propia | ❌ | ❌ |

---

## ⚙️ CONFIGURACIÓN

### Archivo .env

```env
# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=restaurante_db
DB_USERNAME=root
DB_PASSWORD=

# JWT
JWT_SECRET=tu_clave_secreta_generada
JWT_TTL=480  # 8 horas
JWT_REFRESH_TTL=20160  # 14 días
```

### CORS

En `config/cors.php`:
```php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:4200'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

---

## 🚀 COMANDOS ÚTILES

### Desarrollo
```bash
# Iniciar servidor
php artisan serve

# Ver rutas
php artisan route:list

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Regenerar base de datos
php artisan migrate:fresh --seed
```

### Testing con Postman
```bash
# Login
POST http://localhost:8000/api/auth/login
Body: {"email": "admin@restaurante.com", "password": "admin123"}

# Usar token en otras peticiones
Headers:
  Authorization: Bearer {token}
  Accept: application/json
```

---

## 📊 FLUJO TÍPICO DE UNA ORDEN

1. **Mesero hace login** → `POST /auth/login`
2. **Ve mesas disponibles** → `GET /mesas/libres`
3. **Crea orden en mesa** → `POST /ordenes`
   - Sistema cambia estado de mesa a "ocupada"
   - Descuenta stock de productos comprados
4. **Cocina ve orden** → `GET /ordenes/cocina`
5. **Cocina cambia estado** → `PATCH /ordenes/{id}/estado` → "en_preparacion"
6. **Mesero sirve** → `PATCH /ordenes/{id}/estado` → "servido"
7. **Cliente pide cuenta**
8. **Cajero genera comprobante** → `POST /comprobantes/generar`
   - Sistema cambia orden a "pagado"
   - Libera la mesa
9. **Fin del turno: cierre de caja** → `POST /caja/{id}/cerrar`

---

## 🐛 TROUBLESHOOTING

### Error: "Unauthenticated"
- Verificar que el token esté en el header
- Verificar que el token no haya expirado (8 horas)
- Hacer login nuevamente

### Error: "No tienes permisos"
- Verificar el rol del usuario
- Verificar que la ruta tenga los permisos correctos

### Error: "Column not found"
- Ejecutar: `php artisan migrate:fresh --seed`

### Error: "Extension sodium missing"
- Habilitar `extension=sodium` en `php.ini`
- Reiniciar Apache

---

## 📝 NOTAS IMPORTANTES

1. **Todos los precios** están en **Soles (S/)** con 2 decimales
2. **IGV** es del **18%** (Perú)
3. **Token JWT** expira en **8 horas** (configurable)
4. **Stock** se descuenta automáticamente al crear órdenes
5. **Stock se devuelve** al cancelar órdenes
6. **Mesas** cambian de estado automáticamente con órdenes
7. **Comprobantes** tienen numeración correlativa automática
8. **Soft delete**: Productos y categorías se desactivan, no se eliminan

---

## 🔄 PRÓXIMAS MEJORAS

- [ ] Sistema de reservas
- [ ] Gestión de clientes frecuentes
- [ ] Control de gastos
- [ ] Inventario avanzado (ingredientes, recetas)
- [ ] Reportes avanzados (gráficos, exportar Excel/PDF)
- [ ] Integración con SUNAT (emisión electrónica)
- [ ] Sistema de propinas
- [ ] Programa de puntos de fidelidad
- [ ] Notificaciones push para cocina
- [ ] Delivery con seguimiento GPS

---

## 📧 SOPORTE

Para dudas o problemas:
- Revisar esta documentación
- Verificar logs en `storage/logs/laravel.log`
- Usar Postman para probar endpoints
- Verificar que Laravel y MySQL estén corriendo

---

**Versión:** 1.0  
**Fecha:** Enero 2026  
**Desarrollador:** David  
**Framework:** Laravel 11 + Angular 18