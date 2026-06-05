# Version PostgreSQL

Esta copia esta adaptada para usar PostgreSQL por PDO.

## Configuracion

La conexion se lee desde variables de entorno:

- `PGHOST`
- `PGPORT`
- `PGDATABASE`
- `PGUSER`
- `PGPASSWORD`
- `PGSSLMODE`
- `PGMAINTENANCE_DB` opcional, por defecto `postgres`

Si no existen, `config/db.php` usa los valores actuales de la copia.

## Crear base y tablas

Ejecuta una de estas opciones desde la carpeta del proyecto:

```powershell
php setup_postgres.php
```

El instalador primero revisa si existe `PGDATABASE`; si no existe, intenta crearla conectandose a `PGMAINTENANCE_DB`.

Nota: algunos proveedores remotos no permiten `CREATE DATABASE` desde usuarios de aplicacion. En ese caso crea la base desde el panel del proveedor y vuelve a ejecutar `php setup_postgres.php` para crear las tablas.

O carga manualmente:

```powershell
psql -h TU_HOST -p 5432 -U TU_USUARIO -d TU_BD -f database.sql
```

## Crear usuario admin opcional

Antes de ejecutar `setup_postgres.php` puedes definir:

```powershell
$env:ADMIN_USER="admin"
$env:ADMIN_PASSWORD="0806"
php setup_postgres.php
```
