# Krysion Fit

Krysion Fit es una plataforma de asesoría de fitness online 100% personalizada. Este proyecto utiliza WordPress como CMS y se despliega mediante Docker, incluyendo un túnel de Cloudflare para exposición pública segura.

## 🚀 Tecnologías

*   **HTML/CSS/JS**: Para personalizaciones del frontend.
*   **WordPress**: Sistema de gestión de contenidos (CMS) principal.
*   **Docker & Docker Compose**: Orquestación de contenedores para la base de datos, la aplicación y el túnel.
*   **MySQL**: Base de datos para WordPress.
*   **Cloudflare Tunnel**: Para exponer la aplicación de forma segura a internet (sin necesidad de token).

## 📂 Estructura del Proyecto

*   `docker-compose.yml`: Configuración de los servicios de Docker (MySQL, WordPress, Cloudflare Tunnel).
*   `database.sql`: Exportación de la base de datos con todo el contenido del sitio (páginas, posts, usuarios, configuración).
*   `src/`: Directorio con los archivos de WordPress (temas, plugins, uploads, etc.).
*   `uploads.ini`: Configuración personalizada de PHP para la subida de archivos.

## 🛠️ Instalación y Uso

### Prerrequisitos

*   Docker y Docker Compose instalados en tu sistema.

### Pasos para levantar el proyecto

1.  Clona este repositorio o descarga los archivos.
2.  Abre una terminal en la raíz del proyecto.
3.  Ejecuta el siguiente comando para iniciar los contenedores:

    ```bash
    docker-compose up -d
    ```

4.  Obtén la URL pública del túnel ejecutando:

    ```bash
    docker-compose logs tunnel
    ```

5.  Busca en los logs la línea que contiene la URL (entre `---`), por ejemplo:

    ```
    -------------------------------------------
    https://random-subdomain.trycloudflare.com
    -------------------------------------------
    ```

6.  Accede al sitio usando esa URL.

### Gestión de Contenedores

*   **Detener los servicios**: `docker-compose down`
*   **Ver logs en tiempo real**: `docker-compose logs -f`
*   **Ver solo logs del túnel**: `docker-compose logs tunnel`

## 👤 Autor

Desarrollado para Krysion Fit - Asesoría de Fitness Personalizada.
