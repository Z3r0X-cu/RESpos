# RESpos
Sistema POS/gestión para restaurante, PHP + SQLite, creado por Z3r0X.

## Requisitos
- XAMPP para Windows, Linux o macOS.
- PHP con PDO SQLite. En XAMPP normalmente se incluye; si no, habilita `pdo_sqlite` y `sqlite3` en `php.ini`.
- GD es recomendable para normalizar imágenes a 500×500 y crear la copia JPG usada en documentos PDF.

## Instalación
1. Copia `RESpos` dentro de `htdocs`.
2. Abre `http://localhost/RESpos/`.
3. La base `database/respos.sqlite` se crea automáticamente.
4. Acceso inicial: `admin` / `admin123`. Cambia la contraseña creando usuarios desde Administración → Usuarios y desactivando la cuenta inicial cuando corresponda.

## Persistencia y traslado
La base está en `database/respos.sqlite`. Las imágenes están en `assets/uploads/` y los PDF en `storage/pdfs/`. Para trasladar el restaurante a otra PC se copia toda la carpeta RESpos, incluyendo esos directorios y el SQLite.

## Módulos incluidos
- Administración: configuración, mesas, menú/categorías, empleados y usuarios.
- Recepción: salón, comandas por mesa, reservas, cobro, factura + comprobante PDF y calculadora.
- Economía: compras con PDF, almacén, despachos, inventario PDF, caja diaria/mensual y banco.
- Carta pública responsive con conversión de moneda y datos/imagen del restaurante.
- Auditoría básica de accesos y operaciones administrativas.

## URL del proyecto
La URL está centralizada en `config/app.php` mediante `PROJECT_GITHUB_URL`. La ruta de instalación se autodetecta; si se requiere una ruta fija puede definirse `APP_BASE_URL`.

## Moneda
Los precios maestros se guardan en CUP. Las tasas de USD/EUR/JPY/MXN se administran desde Configuración y se usan para la visualización y cobro en otras monedas.

## Nota fiscal
RESpos conserva ventas, pagos, caja, inventario y documentos. Las obligaciones fiscales concretas del establecimiento deben configurarse conforme a la normativa cubana vigente y a los requisitos de la ONAT; este software no debe considerarse por sí solo una certificación fiscal.


## PDF y permisos en Linux/XAMPP

RESpos genera los documentos en `storage/pdfs/`. Los nuevos PDFs se crean con permisos de lectura para Apache y para el usuario local. El sistema también limpia cualquier salida previa antes de transmitir un PDF, evitando mezclar HTML con el archivo.

Si una instalación anterior ya tiene PDFs creados con permisos restrictivos, el administrador puede ejecutar desde la raíz de RESpos:

`php tools/fix_pdf_permissions.php`

Los PDFs pueden abrirse desde el explorador de documentos sin acceder directamente a la ruta física.
