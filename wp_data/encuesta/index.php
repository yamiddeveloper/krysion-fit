<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/wp-load.php';
require 'db.php';

$mensaje = "";
$status = ""; 
$credenciales_creadas = [];

function generarPassword($longitud = 8) {
    $caracteres = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    return substr(str_shuffle($caracteres), 0, $longitud);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
            $targetPath = $uploadDir . $fileName;
            $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
            if(in_array($fileType, ['jpg', 'jpeg', 'png', 'webp'])) {
                move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath);
                $photo_path = $targetPath;
            }
        }

        $email_usuario = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $full_name = htmlspecialchars($_POST['fullName']);
        $password_texto_plano = generarPassword(8);
        $password_hash = password_hash($password_texto_plano, PASSWORD_DEFAULT);

        // --- INTEGRACIÓN CON WORDPRESS ---
        if (!email_exists($email_usuario)) {
            $user_id_wp = wp_insert_user([
                'user_login'    => $email_usuario,
                'user_email'    => $email_usuario,
                'user_pass'     => $password_texto_plano,
                'first_name'    => explode(' ', $full_name)[0],
                'display_name'  => $full_name,
                'role'          => 'subscriber'
            ]);

            if (is_wp_error($user_id_wp)) {
                throw new Exception("Error al crear usuario en WordPress: " . $user_id_wp->get_error_message());
            }
            
            // Meta inicial: pendiente de aprobación hasta que pague
            update_user_meta($user_id_wp, 'kf_user_approval', 'pending');
        }
        // --------------------------------

        $sql = "INSERT INTO planes_personalizados 
                (full_name, email, phone, age, sex, goal, weight, height, photo_path, activity_level, injuries, equipment, nutrition_pref, daily_msg, password_hash)
                VALUES (:full_name, :email, :phone, :age, :sex, :goal, :weight, :height, :photo_path, :activity, :injuries, :equipment, :nutrition, :daily, :password_hash)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $full_name,
            ':email' => $email_usuario,
            ':phone' => htmlspecialchars($_POST['phone']),
            ':age' => (int)$_POST['age'],
            ':sex' => $_POST['sex'] ?? 'male',
            ':goal' => $_POST['goal'] ?? 'lose_fat',
            ':weight' => (float)$_POST['weight'],
            ':height' => (float)$_POST['height'],
            ':photo_path' => $photo_path,
            ':activity' => $_POST['activity'] ?? 'moderate',
            ':injuries' => htmlspecialchars($_POST['injuries'] ?? ''),
            ':equipment' => $_POST['equipment'] ?? 'gym',
            ':nutrition' => $_POST['nutrition'] ?? 'cook',
            ':daily' => $_POST['daily'] ?? 'no',
            ':password_hash' => $password_hash
        ]);

        $credenciales_creadas = ['user' => $email_usuario, 'pass' => $password_texto_plano];
        $status = "success";
        $_SESSION['usuario_id'] = $pdo->lastInsertId();
        unset($_SESSION['acceso_formulario']);
    } catch (Exception $e) {
        $status = "error";
        $mensaje = $e->getMessage();
    }
}
?>

<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Krysion Fit - Configuración</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">

<style>
:root {
  --primary: #F2600C;
  --bg-dark: #0a0a0a;
  --card-bg: rgba(25, 25, 25, 0.95);
  --text-main: #FFFFFF;
  --text-secondary: #999999;
  --font-main: 'Outfit', sans-serif;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background-color: var(--bg-dark);
  color: var(--text-main);
  font-family: var(--font-main);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80px 20px 40px;
  overflow-x: hidden;
}

/* ========== CONTENEDOR PRINCIPAL ========== */
.phone-frame {
  width: 100%;
  max-width: 440px; /* Un poco más ancho el frame */
  min-height: 680px;
  background: var(--card-bg);
  backdrop-filter: blur(30px);
  border-radius: 45px;
  border: 1px solid rgba(255,255,255,0.12);
  padding: 80px 25px 35px; /* Padding lateral reducido de 35 a 25 */
  position: relative;
  box-shadow: 0 35px 70px rgba(0,0,0,0.9);
  display: flex;
  flex-direction: column;
}

/* ========== LOGO CIRCULAR SUPERIOR ========== */
.logo-header {
  position: absolute;
  top: -50px;
  left: 50%;
  transform: translateX(-50%);
  width: 100px;
  height: 100px;
  background: linear-gradient(135deg, #1a1a1a, #0a0a0a);
  border-radius: 50%;
  border: 3px solid rgba(242, 96, 12, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: 0 15px 35px rgba(0,0,0,0.7), 0 0 0 8px rgba(10,10,10,0.5);
  z-index: 10;
}

.logo-header img {
  width: 70%;
  height: 70%;
  object-fit: contain;
}

/* ========== CONTADOR DE PASOS ========== */
.step-counter {
  text-align: center;
  font-size: 11px;
  color: var(--primary);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 2.5px;
  margin-bottom: 25px;
  opacity: 0.9;
}

/* ========== ESTRUCTURA DE PASOS ========== */
.step {
  display: none;
  flex-direction: column;
  flex: 1;
  animation: fadeIn 0.5s ease;
}

.step.active { display: flex; }

@keyframes fadeIn { 
  from { opacity: 0; transform: translateY(15px); } 
  to { opacity: 1; transform: translateY(0); } 
}

/* Áreas del paso */
.step-header-area {
  text-align: center;
  margin-bottom: 25px;
}

.step-content-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  margin-bottom: 20px;
}

.step-footer-area {
  margin-top: auto;
}

/* ========== TÍTULOS Y SUBTÍTULOS ========== */
h2 { 
  font-size: 26px; 
  font-weight: 800; 
  text-align: center; 
  margin-bottom: 12px;
  line-height: 1.2;
  letter-spacing: -0.5px;
}

.subtitle { 
  color: var(--text-secondary); 
  text-align: center; 
  font-size: 14px;
  line-height: 1.5;
}

/* ========== INPUTS Y FORMULARIOS ========== */
input, select, textarea {
  width: 100%;
  padding: 18px 24px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 18px;
  color: #fff;
  font-size: 16px;
  text-align: left;
  outline: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: var(--font-main);
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.form-group {
  margin-bottom: 25px; /* Buen gap entre elementos */
  width: 100%;
}

.form-label {
  display: block;
  font-size: 11px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 1.5px;
  margin-bottom: 10px;
  font-weight: 700;
  padding-left: 4px;
}

input:focus, select:focus, textarea:focus { 
  border-color: var(--primary); 
  background: rgba(242,96,12,0.04); 
  box-shadow: 0 0 0 4px rgba(242,96,12,0.1), inset 0 2px 4px rgba(0,0,0,0.1);
}

input::placeholder, textarea::placeholder {
  color: rgba(255,255,255,0.2);
}

/* Input de número mejorado */
input[type="number"] {
  font-weight: 700;
  font-size: 28px;
  letter-spacing: 1px;
  -moz-appearance: textfield;
}

input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Input de archivo mejorado */
input[type="file"] {
  padding: 35px 20px;
  border: 2px dashed rgba(255,255,255,0.15);
  background: rgba(255,255,255,0.02);
  cursor: pointer;
  font-size: 13px;
  color: var(--text-secondary);
}

input[type="file"]::file-selector-button {
  display: none;
}

textarea {
  text-align: left;
  resize: none;
  min-height: 100px;
  border-radius: 18px;
}

/* ========== BOTONES ========== */
.btn-primary {
  width: 100%;
  padding: 19px;
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: 28px;
  font-weight: 800;
  font-size: 15px;
  cursor: pointer;
  text-transform: uppercase;
  letter-spacing: 1px;
  box-shadow: 0 12px 28px rgba(242,96,12,0.35);
  transition: all 0.3s ease;
}

.btn-primary:hover { 
  transform: translateY(-3px); 
  background: #ff7020; 
  box-shadow: 0 16px 35px rgba(242,96,12,0.5);
}

.btn-secondary {
  width: 100%;
  padding: 18px;
  background: rgba(255,255,255,0.04);
  color: var(--text-secondary);
  border: 1.5px solid rgba(255,255,255,0.1);
  border-radius: 28px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 12px;
  transition: all 0.3s ease;
  text-decoration: none;
  display: block;
  text-align: center;
}

.btn-secondary:hover { 
  background: rgba(255,255,255,0.08); 
  border-color: rgba(255,255,255,0.2);
  color: #fff;
}

.button-group {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.btn-back {
  padding: 18px;
  background: rgba(255,255,255,0.04);
  color: var(--text-secondary);
  border: 1.5px solid rgba(255,255,255,0.1);
  border-radius: 28px;
  font-weight: 700;
  font-size: 13px;
  cursor: pointer;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  transition: all 0.3s ease;
}

.btn-back:hover { 
  background: rgba(255,255,255,0.08); 
  color: #fff; 
}

/* ========== TARJETAS DE OPCIONES (RADIO BUTTONS) ========== */
.option-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 15px;
}

.option-card {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 22px 24px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 24px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.option-card:hover {
  background: rgba(255,255,255,0.06);
  border-color: rgba(255,255,255,0.2);
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.option-card input[type="radio"] {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
  margin: 0;
}

.option-card.active {
  border-color: var(--primary);
  background: rgba(242,96,12,0.08);
  box-shadow: 0 8px 30px rgba(242,96,12,0.15);
}

.card-content {
  display: flex;
  align-items: center;
  gap: 15px;
}

.card-content .icon {
  width: 40px;
  height: 40px;
  background: rgba(255,255,255,0.05);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  transition: all 0.3s ease;
}

.option-card.active .icon {
  background: var(--primary);
  transform: scale(1.1);
}

.card-content .label {
  font-weight: 600;
  font-size: 15px;
  color: #fff;
  text-align: left;
}

.radio-indicator {
  width: 24px;
  height: 24px;
  border: 2px solid rgba(255,255,255,0.2);
  border-radius: 50%;
  position: relative;
  transition: all 0.3s ease;
  flex-shrink: 0;
}

.option-card.active .radio-indicator {
  border-color: var(--primary);
  background: rgba(242,96,12,0.1);
}

.option-card.active .radio-indicator::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 12px;
  height: 12px;
  background: var(--primary);
  border-radius: 50%;
  box-shadow: 0 0 10px var(--primary);
}

/* Wide Options Tweak */
.step:not(.step-1) .option-list {
  margin-left: -10px;
  margin-right: -10px;
}

.step:not(.step-1) .option-card {
  border-radius: 20px;
}

/* ========== BARRA DE PROGRESO ========== */
.progress-container {
  width: 100%;
  height: 8px;
  background: rgba(255,255,255,0.08);
  border-radius: 10px;
  overflow: hidden;
  position: relative;
}

.progress-bar {
  height: 100%;
  width: 0%;
  background: linear-gradient(90deg, var(--primary), #ff8c3a);
  transition: width 0.5s ease;
  box-shadow: 0 0 15px rgba(242,96,12,0.5);
}

.progress-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 10px;
  font-size: 11px;
  color: var(--text-secondary);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* ========== PANTALLA DE BIENVENIDA ========== */
.welcome-step h2 { 
  font-size: 30px; 
  margin-bottom: 20px; 
}

.welcome-step .subtitle { 
  font-size: 15px; 
  line-height: 1.6;
}

/* ========== MODAL ========== */
.modal-overlay {
  position: fixed; 
  inset: 0; 
  background: rgba(0,0,0,0.92);
  backdrop-filter: blur(10px);
  display: none; 
  align-items: center; 
  justify-content: center; 
  z-index: 1000;
  padding: 20px;
}

.modal-overlay.show { display: flex; }

/* ========== RESPONSIVE ========== */
@media (max-width: 480px) {
  body { padding: 70px 15px 30px; }
  
  .phone-frame {
    max-width: 100%;
    border-radius: 35px;
    padding: 70px 28px 28px;
    min-height: 650px;
  }
  
  .logo-header {
    width: 85px;
    height: 85px;
    top: -45px;
  }
  
  h2 { font-size: 23px; }
  .subtitle { font-size: 13px; }
  
  input, select, textarea { font-size: 15px; padding: 16px 18px; }
  input[type="number"] { font-size: 24px; }
  
  .btn-primary, .btn-secondary, .btn-back { font-size: 13px; padding: 16px; }
}
</style>
</head>
<body>

<?php if($status == "success"): ?>
    <!-- ========== PANTALLA DE ÉXITO ========== -->
    <div class="phone-frame" style="min-height: auto; padding-bottom: 40px;">
        <div class="logo-header">
            <img src="/wp-content/uploads/2025/09/favicon.png" alt="Krysion Fit">
        </div>
        
        <div style="text-align:center; margin-top: 10px;">
            <div style="font-size: 55px; margin-bottom: 15px;">🎉</div>
            <h2 style="margin-bottom: 10px;">¡TODO LISTO!</h2>
            <p class="subtitle" style="margin-bottom: 30px;">Tu cuenta ha sido creada exitosamente.</p>
            
            <div style="background:rgba(255,255,255,0.04); padding:28px; border-radius:25px; text-align:left; border:1.5px solid rgba(255,255,255,0.1); margin-bottom:30px;">
                <div style="margin-bottom:22px;">
                    <p style="font-size:10px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1.5px; margin-bottom:8px;">👤 USUARIO (EMAIL)</p>
                    <p style="font-size:16px; font-weight:700; color:var(--primary); background:rgba(0,0,0,0.4); padding:14px; border-radius:15px; border:1px solid rgba(255,255,255,0.05); word-break: break-all;"><?php echo htmlspecialchars($credenciales_creadas['user']); ?></p>
                </div>
                <div>
                    <p style="font-size:10px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1.5px; margin-bottom:8px;">🔑 CONTRASEÑA TEMPORAL</p>
                    <p style="font-size:20px; font-weight:800; color:var(--primary); background:rgba(0,0,0,0.4); padding:14px; border-radius:15px; border:1px solid rgba(255,255,255,0.05); text-align:center; letter-spacing:3px;"><?php echo htmlspecialchars($credenciales_creadas['pass']); ?></p>
                </div>
            </div>

            <p style="font-size:12px; color:var(--text-secondary); margin-bottom:25px; line-height:1.5; padding: 0 10px;">⚠️ Guarda estos datos en un lugar seguro. Ahora solo falta completar el pago para activar tu plan.</p>

            <?php 
            $plan_url = isset($_SESSION['chosen_plan']) ? "?plan=".urlencode($_SESSION['chosen_plan'])."&price=".urlencode($_SESSION['chosen_price']) : "";
            ?>
            <a href="pagar.php<?php echo $plan_url; ?>" class="btn-primary" style="text-decoration:none; display:flex; align-items:center; justify-content:center; gap:12px;">
                <span>CONTINUAR AL PAGO</span>
                <span style="font-size:22px;">💳</span>
            </a>
        </div>
    </div>

<?php else: 
    // Capturamos el plan si viene por URL
    if(isset($_GET['plan'])){
        $_SESSION['chosen_plan'] = $_GET['plan'];
        $_SESSION['chosen_price'] = $_GET['price'] ?? '0';
    }
    $nombre_plan = $_SESSION['chosen_plan'] ?? 'KRYSION BASE';
?>

    <!-- ========== FORMULARIO PRINCIPAL ========== -->
    <div class="phone-frame">
        <div class="logo-header">
            <img src="/wp-content/uploads/2025/09/favicon.png" alt="Krysion Fit">
        </div>



        <form id="mainForm" method="POST" enctype="multipart/form-data" style="flex: 1; display: flex; flex-direction: column;">
            
            <!-- ========== PANTALLA DE BIENVENIDA ========== -->
            <div class="step active welcome-step">
                <div class="step-content-area">
                    <h2>¡Te damos la bienvenida!</h2>
                    
                    <div style="background:rgba(242,96,12,0.12); border:1.5px solid var(--primary); border-radius:20px; padding:18px; margin: 25px 0;">
                        <p style="font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Has seleccionado el plan:</p>
                        <p style="font-size:20px; font-weight:800; color:var(--primary); letter-spacing:-0.5px;"><?php echo htmlspecialchars($nombre_plan); ?></p>
                    </div>
                    
                    <p class="subtitle">Para una experiencia más personalizada necesitamos hacerte unas preguntas antes de activar tu cuenta.</p>
                </div>
                
                <div class="step-footer-area">
                    <button type="button" class="btn-primary" id="startBtn">COMENZAR ENCUESTA</button>
                    <a href="/" class="btn-secondary">VOLVER AL INICIO</a>
                </div>
            </div>

            <!-- ========== PASO 1: DATOS PERSONALES ========== -->
            <div class="step step-1">
                <div class="step-header-area">
                    <h2>¡Genial! Háblanos de TI</h2>
                    <p class="subtitle">Necesitamos estos datos básicos para empezar</p>
                </div>
                <div class="step-content-area">
                    <div class="form-group">
                        <label class="form-label">Tu Nombre</label>
                        <input type="text" name="fullName" required placeholder="Tu nombre completo">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label">Edad</label>
                            <input type="number" name="age" required placeholder="25" min="14" max="99" style="font-size: 20px; padding: 16px; flex: 1;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sexo</label>
                            <div class="option-list" style="display: flex; flex-direction: column; gap: 8px;">
                                <label class="option-card" style="padding: 12px 15px; border-radius: 18px;">
                                    <input type="radio" name="sex" value="male" required>
                                    <div class="card-content" style="gap: 10px;">
                                        <span class="icon" style="width: 32px; height: 32px; font-size: 14px; border-radius: 10px;">♂️</span>
                                        <span class="label" style="font-size: 13px;">Hombre</span>
                                    </div>
                                    <div class="radio-indicator" style="width: 18px; height: 18px;"></div>
                                </label>
                                <label class="option-card" style="padding: 12px 15px; border-radius: 18px;">
                                    <input type="radio" name="sex" value="female" required>
                                    <div class="card-content" style="gap: 10px;">
                                        <span class="icon" style="width: 32px; height: 32px; font-size: 14px; border-radius: 10px;">♀️</span>
                                        <span class="label" style="font-size: 13px;">Mujer</span>
                                    </div>
                                    <div class="radio-indicator" style="width: 18px; height: 18px;"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="step-footer-area">
                    <div class="button-group">
                        <button type="button" class="btn-back cancel-btn">CANCELAR</button>
                        <button type="button" class="btn-primary next-btn">SIGUIENTE</button>
                    </div>
                </div>
            </div>

            <!-- ========== PASO 2: CONTACTO ========== -->
            <div class="step">
                <div class="step-header-area">
                    <h2>¿Cómo te CONTACTAMOS?</h2>
                    <p class="subtitle">Usaremos estos datos para tu cuenta y seguimiento</p>
                </div>
                <div class="step-content-area">
                    <div class="form-group">
                        <label class="form-label">Tu Correo Electrónico</label>
                        <input type="email" name="email" required placeholder="correo@ejemplo.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tu WhatsApp / Teléfono</label>
                        <input type="tel" name="phone" required placeholder="+57 300 123 4567">
                    </div>
                </div>
                <div class="step-footer-area">
                    <div class="button-group">
                        <button type="button" class="btn-back prev-btn">ATRÁS</button>
                        <button type="button" class="btn-primary next-btn">SIGUIENTE</button>
                    </div>
                </div>
            </div>

            <!-- ========== PASO 3: OBJETIVO ========== -->
            <div class="step">
                <div class="step-header-area">
                    <h2>¿Cuál es tu OBJETIVO?</h2>
                    <p class="subtitle">Selecciona tu meta principal</p>
                </div>
                <div class="step-content-area">
                    <div class="option-list">
                        <label class="option-card">
                            <input type="radio" name="goal" value="lose_fat" required>
                            <div class="card-content">
                                <span class="icon">🔥</span>
                                <span class="label">Perder Grasa</span>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="goal" value="gain_muscle" required>
                            <div class="card-content">
                                <span class="icon">💪</span>
                                <span class="label">Ganar Músculo</span>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="goal" value="recomposition" required>
                            <div class="card-content">
                                <span class="icon">🔄</span>
                                <span class="label">Recomposición Corporal</span>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                    </div>
                </div>
                <div class="step-footer-area">
                    <div class="button-group">
                        <button type="button" class="btn-back prev-btn">ATRÁS</button>
                        <button type="button" class="btn-primary next-btn">SIGUIENTE</button>
                    </div>
                </div>
            </div>

            <!-- ========== PASO 4: MEDIDAS ========== -->
            <div class="step">
                <div class="step-header-area">
                    <h2>Tus MEDIDAS actuales</h2>
                    <p class="subtitle">Estos datos son clave para calcular tus calorías</p>
                </div>
                <div class="step-content-area">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label class="form-label">Tu Peso (kg)</label>
                            <input type="number" step="0.1" name="weight" required placeholder="75.5" min="30" max="300" style="font-size: 24px; padding: 18px;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tu Altura (cm)</label>
                            <input type="number" name="height" required placeholder="175" min="120" max="250" style="font-size: 24px; padding: 18px;">
                        </div>
                    </div>
                </div>
                <div class="step-footer-area">
                    <div class="button-group">
                        <button type="button" class="btn-back prev-btn">ATRÁS</button>
                        <button type="button" class="btn-primary next-btn">SIGUIENTE</button>
                    </div>
                </div>
            </div>

            <!-- ========== PASO 5: FOTO ========== -->
            <div class="step">
                <div class="step-header-area">
                    <h2>Sube una FOTO de progreso</h2>
                    <p class="subtitle">Opcional, pero ayuda a ver cambios reales</p>
                </div>
                <div class="step-content-area">
                    <div class="form-group">
                        <label class="form-label">Tu Foto de Perfil / Progreso</label>
                        <input type="file" name="photo" accept="image/*">
                    </div>
                </div>
                <div class="step-footer-area">
                    <div class="button-group">
                        <button type="button" class="btn-back prev-btn">ATRÁS</button>
                        <button type="button" class="btn-primary next-btn">SIGUIENTE</button>
                    </div>
                </div>
            </div>

            <!-- ========== PASO 6: ACTIVIDAD ========== -->
            <div class="step">
                <div class="step-header-area">
                    <h2>¿Cómo es tu día a día?</h2>
                    <p class="subtitle">Nivel de actividad física diaria</p>
                </div>
                <div class="step-content-area">
                    <div class="option-list">
                        <label class="option-card">
                            <input type="radio" name="activity" value="sedentary" required>
                            <div class="card-content">
                                <span class="icon">🪑</span>
                                <span class="label">Sedentario</span>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="activity" value="moderate" required>
                            <div class="card-content">
                                <span class="icon">🚶</span>
                                <span class="label">Moderado</span>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="activity" value="high" required>
                            <div class="card-content">
                                <span class="icon">🏃</span>
                                <span class="label">Alto</span>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                    </div>
                </div>
                <div class="step-footer-area">
                    <div class="button-group">
                        <button type="button" class="btn-back prev-btn">ATRÁS</button>
                        <button type="button" class="btn-primary next-btn">SIGUIENTE</button>
                    </div>
                </div>
            </div>

            <!-- ========== PASO 7: LUGAR ========== -->
            <div class="step">
                <div class="step-header-area">
                    <h2>¿DÓNDE sueles entrenar?</h2>
                    <p class="subtitle">Y cuéntanos si tienes alguna lesión</p>
                </div>
                <div class="step-content-area">
                    <div class="option-list" style="margin-bottom:15px;">
                        <label class="option-card">
                            <input type="radio" name="equipment" value="gym" required>
                            <div class="card-content">
                                <span class="icon">🏋️</span>
                                <span class="label">Gimnasio</span>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="equipment" value="home" required>
                            <div class="card-content">
                                <span class="icon">🏠</span>
                                <span class="label">Casa</span>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label">¿Alguna lesión o molestia?</label>
                        <textarea name="injuries" placeholder="Cuéntanos aquí..." rows="3"></textarea>
                    </div>
                </div>
                <div class="step-footer-area">
                    <div class="button-group">
                        <button type="button" class="btn-back prev-btn">ATRÁS</button>
                        <button type="button" class="btn-primary next-btn">SIGUIENTE</button>
                    </div>
                </div>
            </div>

            <!-- ========== PASO 8: NUTRICIÓN ========== -->
            <div class="step">
                <div class="step-header-area">
                    <h2>Hablemos de comida...</h2>
                    <p class="subtitle">¿Cómo te organizas con la nutrición?</p>
                </div>
                <div class="step-content-area">
                    <div class="option-list">
                        <label class="option-card">
                            <input type="radio" name="nutrition" value="cook" required>
                            <div class="card-content">
                                <span class="icon">🍳</span>
                                <span class="label">Preparo mis comidas</span>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="nutrition" value="buy" required>
                            <div class="card-content">
                                <span class="icon">🥡</span>
                                <span class="label">Compro comida hecha</span>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="nutrition" value="mixed" required>
                            <div class="card-content">
                                <span class="icon">🍱</span>
                                <span class="label">Opción mixta</span>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                    </div>
                </div>
                <div class="step-footer-area">
                    <div class="button-group">
                        <button type="button" class="btn-back prev-btn">ATRÁS</button>
                        <button type="button" class="btn-primary next-btn">SIGUIENTE</button>
                    </div>
                </div>
            </div>

            <!-- ========== PASO 9: MOTIVACIÓN ========== -->
            <div class="step">
                <div class="step-header-area">
                    <h2>¡Ya casi estamos!</h2>
                    <p class="subtitle">¿Mensajes de motivación diarios?</p>
                </div>
                <div class="step-content-area">
                    <div class="option-list">
                        <label class="option-card">
                            <input type="radio" name="daily" value="yes" required>
                            <div class="card-content">
                                <span class="icon">🔔</span>
                                <span class="label">¡Sí, por favor!</span>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                        <label class="option-card">
                            <input type="radio" name="daily" value="no" required>
                            <div class="card-content">
                                <span class="icon">🔇</span>
                                <span class="label">No, por ahora no</span>
                            </div>
                            <div class="radio-indicator"></div>
                        </label>
                    </div>
                </div>
                <div class="step-footer-area">
                    <div class="button-group">
                        <button type="button" class="btn-back prev-btn">ATRÁS</button>
                        <button type="button" class="btn-primary" id="finishBtn">FINALIZAR</button>
                    </div>
                </div>
            </div>

        </form>

        <!-- ========== BARRA DE PROGRESO (FIJA ABAJO) ========== -->
        <div class="step-footer-area" id="progressArea" style="display:none; margin-top: 15px;">
            <div class="progress-container">
                <div class="progress-bar" id="progressBar"></div>
            </div>
            <div class="progress-info">
                <span>Progreso</span>
                <span id="percentText">0%</span>
            </div>
        </div>
    </div>

    <!-- ========== MODAL DE CONFIRMACIÓN ========== -->
    <div class="modal-overlay" id="confirmModal">
        <div class="phone-frame" style="max-width:360px; min-height:auto; padding: 50px 35px 40px;">
            <div style="font-size:55px; margin-bottom:18px; text-align:center;">🚀</div>
            <h2 style="margin-bottom:15px;">¡CASI LISTO!</h2>
            <p class="subtitle" style="margin-bottom:35px; padding: 0 10px;">Estamos listos para crear tu perfil personalizado con toda tu información.</p>
            <button class="btn-primary" id="finalSubmit" style="font-size:16px; padding:20px; margin-bottom:15px;">¡SÍ, CREAR MI PERFIL!</button>
            <p id="cancelModal" style="cursor:pointer; color:var(--text-secondary); font-size:13px; text-decoration:underline; text-align:center;">Revisar mis respuestas</p>
        </div>
    </div>
<?php endif; ?>

<script>
    const steps = document.querySelectorAll('.step');
    const progressBar = document.getElementById('progressBar');

    const progressArea = document.getElementById('progressArea');
    const percentText = document.getElementById('percentText');
    const nextBtns = document.querySelectorAll('.next-btn');
    const prevBtns = document.querySelectorAll('.prev-btn');
    const startBtn = document.getElementById('startBtn');
    const cancelBtn = document.querySelector('.cancel-btn');
    const finishBtn = document.getElementById('finishBtn');
    const confirmModal = document.getElementById('confirmModal');
    
    let currentStep = 0;

    function updateUI() {
        steps.forEach((s, i) => s.classList.toggle('active', i === currentStep));
        
        // No mostrar contador ni progreso en la bienvenida
        if (currentStep === 0) {
            if(progressArea) progressArea.style.display = 'none';
        } else {
            if(progressArea) progressArea.style.display = 'block';
            
            // Ajustamos el progreso restando la bienvenida (paso 0)
            const totalQuestions = steps.length - 1;
            const currentQuestion = currentStep;
            const percent = Math.round((currentQuestion / totalQuestions) * 100);
            
            if(progressBar) progressBar.style.width = percent + '%';
            if(percentText) percentText.innerText = percent + '%';
        }
        
        // Scroll suave al inicio
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Manejo de selección visual en las tarjetas de radio
    document.querySelectorAll('.option-card').forEach(card => {
        card.addEventListener('click', function() {
            const input = this.querySelector('input[type="radio"]');
            const groupName = input.getAttribute('name');
            
            // Desactivar otras tarjetas del mismo grupo
            document.querySelectorAll(`input[name="${groupName}"]`).forEach(radio => {
                radio.closest('.option-card').classList.remove('active');
            });
            
            // Activar esta tarjeta
            input.checked = true;
            this.classList.add('active');
        });
    });

    // Botón de inicio
    if(startBtn) {
        startBtn.addEventListener('click', () => {
            currentStep = 1;
            updateUI();
        });
    }

    // Botón cancelar (volver a bienvenida)
    if(cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            if(confirm('¿Seguro que quieres cancelar? Perderás el progreso.')) {
                currentStep = 0;
                updateUI();
            }
        });
    }

    // Botones siguiente
    nextBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const inputs = steps[currentStep].querySelectorAll('input[required], select[required]');
            let valid = true;
            
            inputs.forEach(i => { 
                if(!i.checkValidity()){ 
                    i.reportValidity(); 
                    valid = false; 
                } 
            });
            
            if(valid) {
                currentStep++;
                updateUI();
            }
        });
    });

    // Botones atrás
    prevBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if(currentStep > 1) {
                currentStep--;
                updateUI();
            }
        });
    });

    // Botón finalizar
    if(finishBtn) {
        finishBtn.addEventListener('click', () => {
            const inputs = steps[currentStep].querySelectorAll('input[required], select[required]');
            let valid = true;
            
            inputs.forEach(i => { 
                if(!i.checkValidity()){ 
                    i.reportValidity(); 
                    valid = false; 
                } 
            });
            
            if(valid) {
                confirmModal.classList.add('show');
            }
        });
    }

    // Modal
    if(document.getElementById('cancelModal')) {
        document.getElementById('cancelModal').addEventListener('click', () => {
            confirmModal.classList.remove('show');
        });
    }

    if(document.getElementById('finalSubmit')) {
        document.getElementById('finalSubmit').addEventListener('click', () => {
            document.getElementById('finalSubmit').innerHTML = "⏳ PROCESANDO...";
            document.getElementById('finalSubmit').disabled = true;
            document.getElementById('mainForm').submit();
        });
    }

    // Inicializar
    updateUI();
</script>
</body>
</html>