<?php
if (!session_id()) {
    session_start();
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php wp_title('|', true, 'right'); bloginfo('name'); ?> | Planes de entrenamiento personalizados</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
  --primary:#F2600C;
  --primary-dark:#F2490C;
  --primary-darkest:#731F0D;
  --bg-dark:#050505;
  --bg-section:#0D0D0D;
  --text-light:#FFFFFF;
  --text-muted:#999999;
  --card-border:rgba(255, 255, 255, 0.08);
  --radius:22px;
  --shadow-soft:0 18px 45px rgba(0,0,0,0.65);
  --font-main: 'Outfit', sans-serif;
}

/* GLOBAL */
*{
  box-sizing:border-box;
  margin:0;
  padding:0;
}
html{
  scroll-behavior:smooth;
}
body,
button,
input,
textarea{
  font-family:'Outfit',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
  background:var(--bg-dark);
  color:var(--text-light);
  line-height:1.6;
}
a{
  text-decoration:none;
  color:inherit;
}
section{
  scroll-margin-top:90px;
}
.container{
  max-width:1180px;
  margin:0 auto;
  padding:0 22px;
}

/* NAV */
nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 30px;
  background: rgba(5, 5, 5, 0.85);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  position: sticky;
  top: 0;
  z-index: 50;
  border-bottom: 1px solid var(--card-border);
}
nav .logo {
  color: var(--primary);
  font-weight: 800;
  font-size: 1.4rem;
  letter-spacing: -0.02em;
  text-decoration: none;
  font-style: italic;
}
nav a {
  text-decoration: none;
}
nav ul {
  list-style: none;
  display: flex;
  gap: 32px;
}
nav ul li a {
  color: var(--text-light);
  font-size: 0.95rem;
  font-weight: 500;
  opacity: 0.85;
  position: relative;
  padding-bottom: 3px;
  transition: 0.3s ease;
}
nav ul li a::after {
  display: none;
}
nav ul li a:hover {
  color: var(--primary);
  opacity: 1;
}
nav .btn-area {
  background: transparent;
  color: var(--text-light);
  padding: 10px 24px;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.9rem;
  border: 1px solid rgba(255,255,255,0.2);
  transition: 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
nav .btn-area:hover {
  background: var(--primary);
  border-color: var(--primary);
  color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(242, 96, 12, 0.4);
}

/* HERO */
.hero{
  position:relative;
  padding:130px 0 110px;
  background:
    radial-gradient(circle at 10% 20%, rgba(242, 96, 12, 0.08) 0%, transparent 40%),
    radial-gradient(circle at 90% 80%, rgba(242, 96, 12, 0.05) 0%, transparent 40%),
    linear-gradient(rgba(13,13,13,0.72), rgba(13,13,13,0.92)),
    url('/wp-content/uploads/2026/02/krysion-fit (1).jpeg');
  background-size: cover;
  background-position: center 67%; 
  overflow:hidden;
}

.hero .grid{
  position:relative;
  z-index:2;
  display:grid;
  grid-template-columns:1.1fr 0.9fr;
  gap:70px;
  align-items:center;
}
.hero-pill{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:6px 14px;
  border-radius:999px;
  background: rgba(255,255,255,0.05);
  border:1px solid rgba(255,255,255,0.1);
  backdrop-filter: blur(10px);
  color: var(--text-light);
  font-size:0.75rem;
  text-transform:uppercase;
  letter-spacing:0.12em;
  margin-bottom:16px;
  font-weight: 600;
}
.hero-pill span{
  display:inline-flex;
  width:8px;
  height:8px;
  border-radius:999px;
  background:#5EC465;
  box-shadow:0 0 0 4px rgba(94,196,101,0.25);
  animation: pulso 1.2s ease-in-out infinite;
}

.hero h1{
  font-size:clamp(2rem, 5vw + 1rem, 4.8rem);
  font-weight:800;
  line-height:1.05;
  letter-spacing:-0.03em;
  color:#FFFFFF;
  margin-bottom:14px;
}
.hero h1 span{
  color: var(--primary);
  background: linear-gradient(to right, var(--primary), #FF8C42); 
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.hero .hero-sub{
  font-size:1.1rem;
  font-weight:500;
  color:#FFFFFF;
  margin-bottom:6px;
  opacity: 0.9;
}
.hero p{
  font-size:1rem;
  color: var(--text-muted);
  margin-top:8px;
  max-width:540px;
  line-height: 1.6;
}

/* STATS */
.stats{
  display:flex;
  gap:18px;
  margin-top:32px;
  flex-wrap:wrap;
}
.stat{
  border-radius:18px;
  padding:18px 22px;
  min-width:160px;
  background:linear-gradient(145deg,rgba(13,13,13,0.96),rgba(13,13,13,0.8));
  border:1px solid rgba(242,242,242,0.4);
  box-shadow:0 16px 40px rgba(13,13,13,0.9);
}
.stat h3{
  color:var(--primary);
  font-size:1.9rem;
  font-weight:700;
  margin:0 0 2px;
}
.stat p{
  margin:0;
  font-size:0.85rem;
  color:#F2F2F2;
}

/* HERO BUTTONS */
.btns{
  margin-top:40px;
  display:flex;
  gap:14px;
  flex-wrap:wrap;
}
.btn{
  padding:14px 26px;
  border-radius:999px;
  font-weight:600;
  font-size:0.9rem;
  letter-spacing:0.08em;
  text-transform:uppercase;
  transition:0.25s ease;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:6px;
}
.btn-green{
  background:var(--primary);
  color:#F2F2F2;
  box-shadow:0 0 18px rgba(242,96,12,0.55);
}
.btn-green:hover{
  background:var(--primary-dark);
  color:#F2F2F2;
  box-shadow:0 0 26px rgba(242,96,12,0.9);
  transform:translateY(-1px);
}
.btn-outline{
  border:1.5px solid rgba(242,242,242,0.8);
  color:#F2F2F2;
  background:rgba(13,13,13,0.9);
}
.btn-outline:hover{
  border-color:var(--primary);
  background:var(--primary);
  color:#F2F2F2;
}

/* COACH CARD */
.coach-card{
  background:linear-gradient(135deg,rgba(13,13,13,0.95),rgba(13,13,13,0.8));
  border-radius:32px;
  padding:34px 30px 32px;
  text-align:center;
  border:1px solid rgba(242,242,242,0.28);
  box-shadow:var(--shadow-soft);
  position:relative;
  overflow:hidden;
}
.coach-card::before{
  content:"";
  position:absolute;
  inset:-40%;
  background:radial-gradient(circle at top,rgba(242,96,12,0.28),transparent 60%);
  opacity:0.65;
  z-index:0;
}
.coach-inner{
  position:relative;
  z-index:1;
}
.coach-tag{
  display:inline-flex;
  align-items:center;
  gap:6px;
  font-size:0.7rem;
  text-transform:uppercase;
  letter-spacing:0.12em;
  color:#F2F2F2;
  border:1px solid rgba(242,96,12,0.6);
  border-radius:999px;
  padding:4px 10px;
  margin-bottom:16px;
  background:rgba(13,13,13,0.9);
}
.coach-tag span{
  width:7px;
  height:7px;
  border-radius:999px;
  background: var(--primary-dark);
}
.coach-card img{
  width:108px;
  height:108px;
  border-radius:999px;
  object-fit:cover;
  display:block;
  margin:0 auto 16px;
  border:4px solid var(--primary);
  box-shadow:0 0 0 6px rgba(242,96,12,0.2);
}
.coach-card h3{
  font-size:1.05rem;
  font-weight:600;
  margin-bottom:4px;
}
.coach-card p{
  font-size:0.9rem;
  color:#F2F2F2;
  margin-bottom:14px;
}
.coach-note{
  font-size:0.8rem;
  color:#9ca3af;
  margin-bottom:16px;
}
.btn-small{
  background:var(--primary);
  color:#F2F2F2;
  padding:9px 20px;
  border-radius:999px;
  font-weight:600;
  font-size:0.78rem;
  text-transform:uppercase;
  letter-spacing:0.1em;
  display:inline-flex;
  align-items:center;
  gap:6px;
  transition:0.25s ease;
}
.btn-small::after{
  content:"↗";
  font-size:0.8rem;
}
.btn-small:hover{
  background:var(--primary-dark);
  color:#F2F2F2;
}

/* CÓMO FUNCIONA */
.steps{
  padding:74px 0 82px;
  background:#0D0D0D;
}
.section-title{
  text-align:center;
  max-width:620px;
  margin:0 auto 38px;
}
.section-title h2{
  font-size:2rem;
  font-weight:800;
  margin-bottom:8px;
  letter-spacing:-0.02em;
}
.section-title p{
  color:var(--text-muted);
  font-size:0.95rem;
}
.steps-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
  gap:24px;
}
.step-card{
  background:#0D0D0D;
  border-radius:var(--radius);
  border:1px solid rgba(242,242,242,0.35);
  padding:20px 20px 22px;
  box-shadow:0 16px 40px rgba(13,13,13,0.9);
}
.step-header{
  display:flex;
  align-items:center;
  gap:12px;
  margin-bottom:8px;
}
.step-num{
  width:30px;
  height:30px;
  border-radius:999px;
  background:rgba(242,96,12,0.12);
  border:1px solid rgba(242,96,12,0.7);
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:0.9rem;
  color:var(--primary);
}
.step-card h3{
  font-size:0.98rem;
}
.step-card p{
  font-size:0.9rem;
  color:var(--text-muted);
}

/* PLANES */
.section{
  padding:82px 0 86px;
  background:var(--bg-section);
}
.cards{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
  gap:28px;
}
.section h2{
  font-size:2.1rem;
  font-weight:800;
  text-align:center;
  margin-bottom:8px;
  letter-spacing:-0.02em;
}
.section p.subtitle{
  text-align:center;
  color:var(--text-muted);
  margin-bottom:40px;
}
.plan{
  background:radial-gradient(circle at top left,rgba(13,13,13,0.98),rgba(13,13,13,0.98));
  border:1px solid rgba(242,242,242,0.4);
  border-radius:var(--radius);
  padding:24px 22px 24px;
  display:flex;
  flex-direction:column;
  justify-content:space-between;
  box-shadow:0 18px 48px rgba(13,13,13,0.9);
  position:relative;
  overflow:hidden;
}
.plan:nth-child(2){
  border-color:var(--primary);
  box-shadow:0 22px 60px rgba(242,96,12,0.55);
  transform:translateY(-4px);
}
.badge-plan{
  position:absolute;
  top:16px;
  right:18px;
  background:rgba(242,96,12,0.18);
  color:#F2F2F2;
  border-radius:999px;
  padding:4px 10px;
  font-size:0.72rem;
  text-transform:uppercase;
  letter-spacing:0.12em;
  border:1px solid rgba(242,96,12,0.7);
}
.plan h3{
  margin-bottom:4px;
  font-size:1.05rem;
}
.plan .price{
  font-size:1.8rem;
  color:var(--primary);
  font-weight:800;
}
.plan small{
  color:var(--text-muted);
}
.plan .rec{
  background:rgba(13,13,13,0.95);
  border-radius:16px;
  padding:9px 11px;
  margin:14px 0 10px;
  color:#F2F2F2;
  font-size:.87rem;
  border:1px solid rgba(242,96,12,0.3);
}
.plan ul{
  list-style:none;
  margin:10px 0 12px;
  color:var(--text-muted);
  padding-left:0;
  font-size:0.9rem;
}
.plan ul li{
  margin:5px 0;
}
.plan ul li::before{
  content:"✔ ";
  color:var(--primary);
}
.plan .diff{
  background:#0D0D0D;
  padding:10px 11px;
  border-radius:14px;
  margin-top:auto;
  color:#F2F2F2;
  font-size:.85rem;
  border:1px solid rgba(242,96,12,0.3);
}
.plan .btn-green{
  margin-top:18px;
  text-align:center;
}

/* WHATSAPP */
.whatsapp{
  background:#0D0D0D;
  padding:90px 0;
  text-align:center;
}
.whatsapp .whatsapp-box{
  background:radial-gradient(circle at top,rgba(242,96,12,0.18),rgba(13,13,13,0.98));
  border:1px solid rgba(242,96,12,0.7);
  border-radius:26px;
  padding:54px 40px 50px;
  max-width:640px;
  margin:0 auto;
  text-align:center;
  box-shadow:var(--shadow-soft);
}
.whatsapp .whatsapp-box h2{
  font-size:1.9rem;
  font-weight:800;
  margin-bottom:10px;
}
.whatsapp .whatsapp-box p{
  color:#F2F2F2;
  margin-bottom:22px;
}
.whatsapp .price{
  font-size:2rem;
  color:var(--primary);
  font-weight:800;
  margin-bottom:12px;
}
.whatsapp .opts{
  color:var(--text-muted);
  font-weight:500;
  margin-bottom:34px;
}

/* PRODUCTOS */
.productos{
  background:#0D0D0D;
  padding:90px 0 90px;
  text-align:center;
}
.productos h2{
  font-size:2rem;
  font-weight:800;
  margin-bottom:8px;
}
.productos p{
  color:var(--text-muted);
  margin-bottom:42px;
}
.items{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
  gap:28px;
}
.item{
  background:radial-gradient(circle at top left,#0D0D0D,#0D0D0D);
  border:1px solid rgba(242,242,242,0.5);
  border-radius:var(--radius);
  overflow:hidden;
  box-shadow:var(--shadow-soft);
  transition:transform 0.3s ease, box-shadow 0.3s ease;
}
.item:hover{
  transform:translateY(-4px);
  box-shadow:0 22px 60px rgba(13,13,13,0.95);
}
.img-box{
  background:#0D0D0D;
  padding:28px;
  display:flex;
  justify-content:center;
  align-items:center;
}
.img-box img{
  width:140px;
  height:140px;
  object-fit:contain;
  border-radius:12px;
  filter:drop-shadow(0 0 10px rgba(242,96,12,0.45));
}
.content{
  padding:24px 24px 26px;
  text-align:left;
}
.content h3{
  font-size:1.02rem;
  font-weight:600;
  margin-bottom:8px;
}
.content p{
  color:var(--text-muted);
  font-size:0.92rem;
  margin-bottom:14px;
}
.price{
  font-size:1.6rem;
  font-weight:800;
  color:var(--primary);
  margin-bottom:6px;
}
.tag{
  display:inline-block;
  background:#731F0D;
  color:#F2F2F2;
  border-radius:999px;
  padding:5px 12px;
  font-size:0.8rem;
  margin-top:8px;
}

/* CTA */
.cta{
  padding:82px 0 90px;
  text-align:center;
  background:#0D0D0D;
}
.cta h2{
  font-size:1.9rem;
  margin-bottom:6px;
  letter-spacing:-0.02em;
}
.cta p{
  color:var(--text-muted);
  margin-bottom:24px;
}
.cta .btns{
  display:flex;
  justify-content:center;
  gap:14px;
  flex-wrap:wrap;
}

/* FOOTER */
footer{
  background:#050505;
  padding:50px 0;
  text-align:center;
  color:var(--text-muted);
  font-size:0.9rem;
  border-top:1px solid var(--card-border);
}
footer .logo{
  color:var(--primary);
  font-weight:800;
  font-size:1.2rem;
  margin-bottom:12px;
  font-style: italic;
  letter-spacing: -0.02em;
}
footer p {
  margin-bottom: 12px;
}
footer a{
  color: var(--text-light);
  margin:0 12px;
  font-weight:500;
  transition: color 0.3s ease;
}
footer a:hover{
  color:var(--primary);
}

@keyframes pulso {
  0% {
    box-shadow: 0 0 0 0 rgba(94,196,101,0.6);
    transform: scale(1);
  }
  50% {
    box-shadow: 0 0 12px 6px rgba(94,196,101,0.3);
    transform: scale(1.1);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(94,196,101,0.6);
    transform: scale(1);
  }
}

/* RESPONSIVE */
@media (max-width:900px){
  nav ul{
    display:none;
  }
  /* Busca esta parte en tu código y cámbiala así: */
  .hero {
    position: relative;
    padding: 130px 0 110px;
    background:
    linear-gradient(rgba(13,13,13,0.72), rgba(13,13,13,0.88)),
    background-size: cover;
    background-position: center 50%; /* Cambiado de 70% a 20% para subir la imagen y que se vea lo de abajo */
    background-attachment: fixed; 
    overflow:hidden;
  }
  .hero .grid{
    grid-template-columns:1fr;
    gap:42px;
    text-align:center;
  }
  .hero p{
    margin-left:auto;
    margin-right:auto;
  }
  .stats{
    justify-content:center;
  }
  .stat{
      width: auto;
  }
  .coach-card{
    max-width:360px;
    margin:0 auto;
  }
}
@media (max-width:600px){
  .hero h1{
    font-size:2.5rem;
  }
  .btn,
  .btn-green,
  .btn-outline{
    width:100%;
    justify-content:center;
  }
}
</style>
</head>

<body <?php body_class(); ?>>
<nav>
        <a href="<?php echo esc_url( home_url('/') ); ?>">
            <div class="logo" style="font-style: italic;">
                KRYSION FIT
            </div>
        </a>

        <ul>
            <li><a href="<?php echo esc_url( home_url('/#top') ); ?>">Inicio</a></li>
            <li><a href="<?php echo esc_url( home_url('/#coach') ); ?>">Coach</a></li>
            <li><a href="<?php echo esc_url( home_url('/#como-funciona') ); ?>">Cómo funciona</a></li>
            <li><a href="<?php echo esc_url( home_url('/#planes') ); ?>">Precios</a></li>
            <li><a href="<?php echo esc_url( home_url('/#contacto') ); ?>">Contacto</a></li>
        </ul>
        <div>
        <?php if ( is_user_logged_in() ) : ?>
            <?php if ( current_user_can('administrator') ) : ?>
                <a href="<?php echo esc_url( home_url('/planes-de-entrenamiento-y-nutricion/') ); ?>" class="btn-area">
                    Panel
                </a>
                <a href="<?php echo esc_url( admin_url() ); ?>" class="btn-area" style="margin-left: 10px;">
                    wp-admin
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url( home_url('/encuesta/area_privada.php') ); ?>" class="btn-area">
                    Mi cuenta
                </a>
            <?php endif; ?>
        <?php else : ?>
            <a href="<?php echo esc_url( home_url('/encuesta/login.php') ); ?>" class="btn-area">
                Área privada
            </a>
        <?php endif; ?>
        </div>
    </nav>


<section class="hero" id="top">
  <div class="container grid" id="coach">
    <div>
      <div class="hero-pill">
        <span></span> Asesoría online 100% personalizada
      </div>
      <h1>Pierde un buen % de <span>Grasa</span> este año</h1>
      <p class="hero-sub">La transformación comienza aquí</p>
      <p>
        En Krysion programamos tu itinerario para que logres perder el máximo de grasa en 45 días
      </p>

      <div class="stats">
        <div class="stat">
          <h3>5</h3>
          <p>Entrenadores</p>
        </div>
        <div class="stat">
          <h3>3</h3>
          <p>Premios mensuales</p>
        </div>
        <div class="stat">
          <h3>8am–7pm</h3>
          <p>Soporte WhatsApp</p>
        </div>
      </div>

      <div class="btns">
        <a href="#planes" class="btn btn-green">Ver planes</a>
        <a href="#como-funciona" class="btn btn-outline">Cómo funciona</a>
      </div>
    </div>

    <div>
      <div class="coach-card">
        <div class="coach-inner">
          <div class="coach-tag"><span></span> Head Coach</div>
          <img src="/wp-content/uploads/2026/02/WhatsApp Image 2026-01-13 at 1.18.38 AM.jpeg"
               alt="Freddy Zapata - Entrenador Certificado">
          <h3>Vicente Silva</h3>
          <p>Experto en perder grasa y definir abdomen</p>
          <a href="/wp-content/uploads/2026/02/INSTRUCTORADO-PERSONAL-TRAINER-Y-MUSCULACION (1).pdf" target="_blank" class="btn-small">Ver certificado</a>
        </div>
      </div>
      <div class="coach-card" style="margin-top: 20px;">
        <div class="coach-inner">
          <div class="coach-tag"><span></span> Coach</div>
          <img src="/wp-content/uploads/2026/02/entrenador-2.jpeg"
               alt="Entrenador Krysion Fit">
          <h3>Anthony Vargas</h3>
          <p>Experto en entrenamiento funcional en casa</p>
          <a href="https://www.instagram.com/daddithoni?igsh=MTkwanhxbTN3eTQyNA==" target="_blank" class="btn-small">Ver perfil</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="steps" id="como-funciona">
  <div class="container">
    <div class="section-title">
      <h2>¿Cómo funciona la asesoría?</h2>
      <p>Un proceso claro con acompañamiento para perder grasa, evitar la flacidez y efecto rebote</p>
    </div>
    <div class="steps-grid">
      <div class="step-card">
        <div class="step-header">
          <div class="step-num">1</div>
          <h3>Nos cuentas tu situación</h3>
        </div>
        <p>
          Completas el formulario (5 min), agendas tu primera videollamada (15 min) y se analiza tu punto de partida
        </p>
      </div>
      <div class="step-card">
        <div class="step-header">
          <div class="step-num">2</div>
          <h3>Diseñamos tu plan a medida</h3>
        </div>
        <p>
          Un entrenador humano arma tu plan de entrenamiento y nutrición según tu cuerpo, tiempo disponible y contexto real
        </p>
      </div>
      <div class="step-card">
        <div class="step-header">
          <div class="step-num">3</div>
          <h3>Seguimiento y ajustes semanales</h3>
        </div>
        <p>
          Se programa un check-in semanal (1 videollamada 15 min), revisamos tu progreso, resolvemos dudas y ajustamos el plan para que avances sin estancarte
          ni abandonar a la mitad. Tu constancia te permite competir por premios valorizados en 300 soles - 90 dólares
        </p>
      </div>
    </div>
  </div>
</section>

<section class="section" id="planes">
  <div class="container">
    <h2>Planes de asesoría</h2>
    <p class="subtitle">Elige el nivel de acompañamiento que necesitas para lograr tu transformación.</p>

    <div class="cards">
      <div class="plan">
        <h3>KRYSION BASE</h3>
        <p class="price">S/20 o $6</p>
        <small>45 días completos</small>
        <div class="rec">
          Ideal si necesitas instrucciones claras para perder el máximo de grasa en 45 días
        </div>
        <ul>
          <li>Plan de entrenamiento y nutrición hecho a medida por un entrenador humano</li>
          <li>Acceso a plataforma digital con tu rutina, nutrición, trackers monitoreo y foro de preguntas</li>
          <li>Seguimiento por WhatsApp para resolver dudas generales</li>
        </ul>
        <div class="diff">
          💡 <strong>Diferenciación:</strong> No trabajamos con PDFs ni IA. Tu plan se arma pensando en tu contexto real, horarios y nivel.
        </div>
        <a href="/encuesta?plan=KRYSION+BASE&price=18" class="btn btn-green">Quiero este plan</a>
      </div>

      <div class="plan">
        <span class="badge-plan">Más elegido</span>
        <h3>KRYSION PLUS</h3>
        <p class="price">S/60 o $18</p>
        <small>45 días completos</small>
        <div class="rec">
          Ideal si necesitas guía constante
        </div>
        <ul>
          <li>Todo lo de Krysion Base</li>
          <li>1 videollamada semanal (15 min) para ver tu físico y tomar medidas</li>
          <li>Ajuste de tus comidas para mantener tu plan calórico</li>
          <li>Corrección y feedback de tus ejercicios en video</li>
          <li>Consejos y tips nutricionales específicos según tu caso</li>
          <li>Acceso a grupo de competición sana por premios, y llegar a su mejor versión de forma acompañada</li>
        </ul>
        <div class="diff">
          💡 <strong>Diferenciación:</strong> Tienes un monitoreo más minucioso. Cada semana revisamos tu proceso y hacemos ajustes para que no te estanques.
        </div>
        <a href="/encuesta?plan=KRYSION+PLUS&price=36" class="btn btn-green">Quiero este plan</a>
      </div>

      <div class="plan">
        <h3>KRYSION ELITE</h3>
        <p class="price">S/370 o $109</p>
        <small>50 días</small>
        <div class="rec">
          Para quienes desean la experiencia presencial
        </div>
        <ul>
          <li>Todo lo de Krysion Plus</li>
          <li>Entrevista inicial vía Zoom para analizar tu estilo de vida</li>
          <li>Entrenamientos presenciales coordinados según tu horario</li>
          <li>1 clase online con temas de progreso en calistenia</li>
        </ul>
        <div class="diff">
          💡 <strong>Diferenciación:</strong> Entrenamiento Presencial 1-1
        </div>
        <a href="/encuesta?plan=KRYSION+ELITE&price=109" class="btn btn-green">Quiero este plan</a>
      </div>
    </div>
  </div>
</section>

<section class="whatsapp">
  <div class="container">
    <div class="whatsapp-box">
      <h2>📱 Acceso al grupo privado de WhatsApp</h2>
      <p>
        Recibe mentoría diaria, tips fitness, retos de 10 días y comparte tu progreso con personas que también están transformando su cuerpo igual que tú.
      </p>
      <div class="price">$5 USD</div>
      <div class="opts">
        ✅ Disponible para todo el mundo &nbsp;&nbsp; ⚡ Acceso inmediato
      </div>
      <a href="/encuesta?plan=GRUPO+WHATSAPP&price=3" target="_blank" class="btn btn-green">
        💳 Comprar acceso ahora
      </a>
    </div>
  </div>
</section>

<section class="productos" id="productos">
  <div class="container">
    <h2>💪 Productos complementarios</h2>
    <p>Extras pensados para potenciar tu rendimiento y acompañar tu proceso fitness.</p>

    <div class="items">
      <div class="item">
        <div class="img-box">
          <img src="/wp-content/uploads/2025/10/ea563866-fd9f-4552-8a46-09270d8075b9.jpeg" alt="Alpha Lipoic Acid Naturebell">
        </div>
        <div class="content">
          <h3>🧠 Ácido Alfa-Lipoico (ALA)</h3>
          <p>Suplemento antioxidante que apoya el metabolismo energético, la función nerviosa y el rendimiento físico.</p>
          <div class="price">S/185 O $55 USD</div>
          <small>+ Costo de envío</small><br>
          <span class="tag">⚠ Solo disponible en Perú</span>
          <a href="/encuesta?plan=ALA+NATUREBELL&price=55" target="_blank" class="btn btn-green">Comprar ahora</a>
        </div>
      </div>

      <div class="item">
        <div class="img-box">
          <img src="/wp-content/uploads/2025/10/f37debd5-85cf-43ef-82d9-3c825218e02f.jpeg" alt="Cadena Dark Gótica Fitness">
        </div>
        <div class="content">
          <h3>Collar de colmillo 🦾</h3>
          <p>Cadena con colgante de colmillo y cabeza de lobo. Un accesorio con estilo oscuro para acompañarte en tus entrenamientos.</p>
          <div class="price">S/47 o $14 USD</div>
          <small>+ Costo de envío</small><br>
          <span class="tag">⚠ Solo disponible en Perú</span>
          <a href="/encuesta?plan=COLLAR+COLMILLO&price=8" target="_blank" class="btn btn-green">Comprar ahora</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="cta" id="contacto">
  <div class="container">
    <h2>¿Listo para empezar tu cambio?</h2>
    <p>Escríbenos y da el primer paso hacia la mejor versión de ti.</p>
    <div class="btns">
      <a href="https://wa.me/51907356?text=Hola,%20quiero%20una%20asesoría%20personalizada" 
         target="_blank" 
         class="btn btn-green">💬 Contactar por WhatsApp</a>
    </div>
  </div>
</section>

<footer>
  <div class="logo" style="font-style: italic; font-weight: bold;">KRYSION FIT</div>
  <p>Transformando cuerpos</p>
  <p>
    <a href="#top">Inicio</a>·
    <a href="#coach">Coach</a>·
    <a href="#como-funciona">Cómo funciona</a>·
    <a href="#planes">Precios</a>·
    <a href="#contacto">Contacto</a>
  </p>
  <p>© 2025 Krysion Fit. Todos los derechos reservados.</p>
</footer>
</body>
</html>
<?php
?>