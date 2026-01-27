# Krysion Fit

Krysion Fit es una plataforma de asesoría de fitness online 100% personalizada. Este proyecto utiliza WordPress como CMS y se despliega mediante Docker, incluyendo un túnel de Cloudflare para exposición pública segura.

## 🚀 Tecnologías

*   **HTML/CSS/JS**: Para la landing page y personalizaciones del frontend.
*   **WordPress**: Sistema de gestión de contenidos (CMS) principal.
*   **Docker & Docker Compose**: Orquestación de contenedores para la base de datos, la aplicación y el túnel.
*   **MySQL**: Base de datos para WordPress.
*   **Cloudflare Tunnel**: Para exponer la aplicación de forma segura.

## 📂 Estructura del Proyecto

*   `docker-compose.yml`: Configuración de los servicios de Docker (base de datos, WordPress, túnel).
*   `page.html`: Landing page estática con el diseño principal del sitio (Hero, Planes, Coach, etc.).
*   `src/`: Directorio que contiene el núcleo de WordPress (`wp-admin`, `wp-content`, etc.).
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

4.  Accede al sitio en tu navegador:

    *   **WordPress Local**: [http://localhost:8080](http://localhost:8080)
    *   **Landing Page (Diseño)**: Abre el archivo `page.html` en tu navegador.

### Gestión de Contenedores

*   **Detener los servicios**: `docker-compose down`
*   **Ver logs**: `docker-compose logs -f`

## 👤 Autor

Desarrollado para Krysion Fit - Asesoría de Fitness Personalizada.
