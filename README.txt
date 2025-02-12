# Instructivo para Configurar API-TO-DO en Local

## Requisitos Previos
1. Instalar XAMPP o WAMPP
2. Git instalado (para clonar el repositorio)

## Pasos de Instalación

1. Clonar el repositorio:
   ```bash
   git clone [URL_DEL_REPOSITORIO]
   ```

2. Configurar el servidor web:
   - Iniciar XAMPP/WAMPP
   - Iniciar el servicio de Apache
   - Iniciar el servicio de MySQL

3. Configurar la base de datos:
   - Abrir phpMyAdmin (http://localhost/phpmyadmin)
   - Crear una nueva base de datos llamada "api_to_do"
   - Importar el archivo SQL ubicado en la raíz del proyecto

4. Configurar el proyecto:
   - Copiar el archivo `.env.example` a `.env`
   - Actualizar las credenciales de la base de datos en el archivo `.env`:
     ```
     DB_HOST=localhost
     DB_USER=root
     DB_PASSWORD=
     DB_NAME=todo_db
     ```

5. Ubicar el proyecto:
   - Mover la carpeta del proyecto al directorio `htdocs` de XAMPP/WAMPP
   - La ruta típica es: `C:\xampp\htdocs\` (Windows) o `/opt/lampp/htdocs/` (Linux)

## Verificar la Instalación

1. Abrir el navegador web
2. Acceder a: `http://localhost/[nombre_del_proyecto]/api/`
3. Deberías ver un mensaje de "API funcionando correctamente"

## Endpoints Disponibles

- GET `/api/tasks` - Obtener todas las tareas
- POST `/api/tasks` - Crear nueva tarea
- PUT `/api/tasks/{id}` - Actualizar tarea existente
- DELETE `/api/tasks/{id}` - Eliminar tarea

## Solución de Problemas Comunes

1. Error de conexión a la base de datos:
   - Verificar que el servicio MySQL esté corriendo
   - Comprobar las credenciales en el archivo `.env`

2. Error 404:
   - Verificar que la ruta del proyecto sea correcta
   - Comprobar que Apache esté corriendo

3. Errores de permisos:
   - Asegurarse que la carpeta del proyecto tenga los permisos correctos
   - Windows: dar permisos de lectura/escritura
   - Linux: `chmod -R 755 [nombre_del_proyecto]`

