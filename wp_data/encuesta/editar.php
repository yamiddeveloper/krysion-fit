<?php
// editar.php

// --- Configuración ---
$archivo = '../wp-content/themes/hello-elementor/page-inicio-personalizado.php'; 
$password = getenv('EDITOR_PASSWORD');
if ($password === false || $password === '') {
    die('Falta la variable de entorno EDITOR_PASSWORD');
}
// --- Manejo del POST ---
$message = '';
$message_type = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo'])) {
    if (!isset($_POST['pass']) || $_POST['pass'] !== $password) {
        $message = '🔒 Contraseña incorrecta.';
        $message_type = 'error';
    } else {
        $bytes = @file_put_contents($archivo, $_POST['codigo'], LOCK_EX);
        if ($bytes === false) {
            $message = '❌ Error al guardar. Comprueba permisos.';
            $message_type = 'error';
        } else {
            $message = '✅ ¡Cambios guardados con éxito!';
            $message_type = 'success';
        }
    }
}

// --- Lectura segura ---
$raw = is_readable($archivo) ? file_get_contents($archivo) : "<?php\n// Archivo no legible.\n";
$contenido = htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Editor KrysionFit - <?= htmlentities($archivo) ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>👨‍💻</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- CodeMirror para resaltado de sintaxis -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/theme/dracula.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/clike/clike.min.js"></script>


    <style>
        :root {
            --primary: #F2600C;
            --primary-dark: #F2490C;
            --primary-darkest: #731F0D;
            --bg-dark: #050505;
            --bg-section: #0D0D0D;
            --text-light: #FFFFFF;
            --text-muted: #999999;
            --card-border: rgba(255, 255, 255, 0.08);
            --radius: 22px;
            --shadow-soft: 0 18px 45px rgba(0,0,0,0.65);
            --font-main: 'Outfit', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body { 
            height: 100%;
            overflow: hidden;
        }

        body { 
            font-family: var(--font-main); 
            background-color: var(--bg-dark); 
            color: var(--text-light);
            display: flex;
            flex-direction: column;
        }

        .glass-card {
            background: var(--bg-section);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-soft);
        }

        .btn-primary {
            background-color: var(--primary);
            transition: all 0.3s ease;
            border-radius: 12px;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(242, 96, 12, 0.3);
        }

        /* Contenedor principal con altura fija */
        .main-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding: 1rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            overflow: hidden;
        }

        .header-section {
            flex-shrink: 0;
            padding: 0 0.5rem;
        }

        .message-section {
            flex-shrink: 0;
        }

        .editor-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-height: 0;
        }

        .footer-section {
            flex-shrink: 0;
        }

        .editor-container {
            background: #121212;
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .editor-toolbar {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(255, 255, 255, 0.02);
        }

        .editor-wrapper {
            flex: 1;
            overflow: hidden;
            position: relative;
            min-height: 0;
        }

        /* Estilos de CodeMirror personalizados */
        .CodeMirror {
            height: 100% !important;
            font-family: 'Fira Code', monospace !important;
            font-size: 14px !important;
            line-height: 1.6 !important;
            background: #0a0a0a !important;
        }

        .CodeMirror-gutters {
            background: #0a0a0a !important;
            border-right: 1px solid rgba(255, 255, 255, 0.05) !important;
        }

        .CodeMirror-linenumber {
            color: #666 !important;
        }

        .CodeMirror-cursor {
            border-left: 2px solid var(--primary) !important;
        }

        .CodeMirror-selected {
            background: rgba(242, 96, 12, 0.2) !important;
        }

        /* Scrollbar personalizado para CodeMirror */
        .CodeMirror-vscrollbar::-webkit-scrollbar,
        .CodeMirror-hscrollbar::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        .CodeMirror-vscrollbar::-webkit-scrollbar-track,
        .CodeMirror-hscrollbar::-webkit-scrollbar-track {
            background: #0a0a0a;
        }

        .CodeMirror-vscrollbar::-webkit-scrollbar-thumb,
        .CodeMirror-hscrollbar::-webkit-scrollbar-thumb {
            background: rgba(242, 96, 12, 0.3);
            border-radius: 10px;
            border: 2px solid #0a0a0a;
        }

        .CodeMirror-vscrollbar::-webkit-scrollbar-thumb:hover,
        .CodeMirror-hscrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(242, 96, 12, 0.5);
        }

        input[type="password"] {
            background: var(--bg-dark);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: white;
        }

        input[type="password"]:focus {
            border-color: var(--primary);
            outline: none;
        }

        .page-footer {
            flex-shrink: 0;
            padding: 1rem;
            text-align: center;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 0.5rem;
            }
            .CodeMirror {
                font-size: 12px !important;
            }
        }
    </style>
</head>
<body>

    <div class="main-container">
        <div class="content-wrapper">
            
            <!-- Header -->
            <div class="header-section">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-semibold tracking-tight">Editor <span style="color: var(--primary)">Krysion Fit</span></h1>
                        <p style="color: var(--text-muted)" class="text-xs md:text-sm">Editando: <span class="font-mono text-white"><?= htmlentities($archivo) ?></span></p>
                    </div>
                    <div class="text-right hidden md:block">
                        <span class="text-xs uppercase tracking-widest" style="color: var(--text-muted)">Krysion Fit Code Editor System</span>
                    </div>
                </div>
            </div>

            <!-- Mensaje -->
            <?php if ($message): ?>
                <div class="message-section">
                    <div class="p-3 md:p-4 rounded-xl border <?= $message_type === 'success' ? 'bg-green-900/20 border-green-500/50 text-green-400' : 'bg-red-900/20 border-red-500/50 text-red-400' ?>">
                        <?= htmlentities($message) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Editor -->
            <form method="post" id="editorForm" class="editor-section">
                <div class="editor-container shadow-2xl">
                    <!-- Barra superior del editor -->
                    <div class="editor-toolbar">
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-[#ff5f56]"></div>
                            <div class="w-3 h-3 rounded-full bg-[#ffbd2e]"></div>
                            <div class="w-3 h-3 rounded-full bg-[#27c93f]"></div>
                        </div>
                        <span class="text-xs font-mono hidden md:block" style="color: var(--text-muted)">PHP Engine v8.x</span>
                    </div>

                    <!-- Área de Código con CodeMirror -->
                    <div class="editor-wrapper">
                        <textarea id="code-editor" name="codigo" style="display:none;"><?= $contenido ?></textarea>
                    </div>
                </div>
            </form>

            <!-- Footer Actions -->
            <div class="footer-section">
                <div class="glass-card p-4 md:p-6 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <div class="relative w-full md:w-64">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-white/30"></i>
                            <input form="editorForm" type="password" name="pass" placeholder="Contraseña de acceso" class="w-full pl-11 pr-4 py-3 text-sm" required>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <a href="/" target="_blank" class="flex-1 md:flex-none text-center px-4 md:px-6 py-3 rounded-xl border border-white/10 hover:bg-white/5 transition-all text-sm font-medium">
                            <i class="fas fa-external-link-alt mr-2"></i> <span class="hidden md:inline">Previsualizar</span><span class="md:hidden">Vista</span>
                        </a>
                        <button form="editorForm" type="submit" class="flex-1 md:flex-none btn-primary px-6 md:px-8 py-3 text-white font-semibold text-sm flex items-center justify-center gap-2">
                            <i class="fas fa-cloud-upload-alt"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <footer class="page-footer">
        <p style="color: var(--text-muted)" class="text-xs tracking-widest uppercase">
            &copy; <?= date('Y') ?> &bull; Secure Code Environment
        </p>
    </footer>

    <script>
        // Inicializar CodeMirror
        const textarea = document.getElementById('code-editor');
        const editor = CodeMirror.fromTextArea(textarea, {
            mode: 'application/x-httpd-php',
            theme: 'dracula',
            lineNumbers: true,
            lineWrapping: false,
            indentUnit: 4,
            indentWithTabs: false,
            smartIndent: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            extraKeys: {
                "Tab": function(cm) {
                    if (cm.somethingSelected()) {
                        cm.indentSelection("add");
                    } else {
                        cm.replaceSelection("    ", "end");
                    }
                },
                "Ctrl-S": function(cm) {
                    document.getElementById('editorForm').submit();
                },
                "Cmd-S": function(cm) {
                    document.getElementById('editorForm').submit();
                }
            }
        });

        // Sincronizar con textarea antes de enviar
        const form = document.getElementById('editorForm');
        form.addEventListener('submit', function(e) {
            editor.save(); // Guarda el contenido de CodeMirror en el textarea
        });

        // Atajo adicional para guardar
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                editor.save();
                form.submit();
            }
        });

        // Auto-refresh del editor al cambiar tamaño de ventana
        window.addEventListener('resize', function() {
            editor.refresh();
        });
    </script>
</body>
</html>