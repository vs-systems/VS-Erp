<?php
/**
 * Vecino Seguro — Página Pública de Presentación
 * Distribuidor de Sistemas Electrónicos de Seguridad
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vecino Seguro | Distribuidores de Seguridad Electrónica</title>
    <meta name="description" content="Vecino Seguro — Distribuidores de sistemas electrónicos de seguridad, alarmas, iluminación inteligente y robótica. Lo que no encontrás en nuestro catálogo, lo buscamos por vos.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --vs-blue:   #1a6ef5;
            --vs-blue2:  #0f4fc9;
            --vs-cyan:   #06b6d4;
            --vs-dark:   #050d1a;
            --vs-dark2:  #091429;
            --vs-card:   rgba(255, 255, 255, 0.04);
            --vs-border: rgba(255, 255, 255, 0.08);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--vs-dark);
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── PARTÍCULAS / FONDO ── */
        .bg-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% -10%, rgba(26,110,245,.18) 0%, transparent 60%),
                radial-gradient(ellipse 70% 50% at 80% 110%, rgba(6,182,212,.12) 0%, transparent 55%),
                var(--vs-dark);
            pointer-events: none;
        }

        .grid-overlay {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        /* ── HEADER ── */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 40px;
            background: rgba(5, 13, 26, 0.7);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--vs-border);
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .header-logo img {
            height: 38px;
            border-radius: 8px;
        }

        .header-logo-text {
            display: flex;
            flex-direction: column;
        }

        .header-logo-text span:first-child {
            font-size: 15px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.3px;
        }

        .header-logo-text span:last-child {
            font-size: 10px;
            font-weight: 500;
            color: var(--vs-cyan);
            letter-spacing: 0.06em;
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-nav a {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            color: rgba(255,255,255,.75);
            transition: all .2s;
        }

        .header-nav a:hover {
            color: #fff;
            background: var(--vs-card);
        }

        .header-nav a.btn-wa {
            background: rgba(37,211,102,.12);
            color: #25d366;
            border: 1px solid rgba(37,211,102,.2);
        }

        .header-nav a.btn-wa:hover {
            background: rgba(37,211,102,.2);
        }

        /* ── HERO ── */
        .hero {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 120px 24px 80px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(26,110,245,.12);
            border: 1px solid rgba(26,110,245,.3);
            border-radius: 100px;
            padding: 6px 18px;
            font-size: 12px;
            font-weight: 700;
            color: #7ab3ff;
            letter-spacing: .08em;
            margin-bottom: 32px;
            animation: fadeUp .6s ease both;
        }

        .hero-badge span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--vs-blue);
            animation: pulse 1.8s ease infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: .5; transform: scale(1.4); }
        }

        .hero-title {
            font-size: clamp(42px, 7vw, 88px);
            font-weight: 900;
            line-height: 1.06;
            letter-spacing: -2px;
            margin-bottom: 12px;
            animation: fadeUp .7s .1s ease both;
        }

        .hero-title .hl {
            background: linear-gradient(135deg, var(--vs-blue) 0%, var(--vs-cyan) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-tagline {
            font-size: clamp(15px, 2.2vw, 22px);
            font-weight: 400;
            color: rgba(255,255,255,.55);
            max-width: 580px;
            line-height: 1.6;
            margin-bottom: 16px;
            animation: fadeUp .8s .2s ease both;
        }

        .hero-tagline strong {
            color: rgba(255,255,255,.85);
            font-weight: 600;
        }

        .hero-slogan {
            display: inline-block;
            font-size: clamp(13px, 1.6vw, 17px);
            font-weight: 600;
            color: var(--vs-cyan);
            font-style: italic;
            margin-bottom: 56px;
            animation: fadeUp .9s .3s ease both;
            letter-spacing: .02em;
        }

        /* ── BOTONES CTA ── */
        .cta-group {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: center;
            animation: fadeUp 1s .4s ease both;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 34px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: all .25s cubic-bezier(.4,0,.2,1);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0);
            transition: background .2s;
        }

        .btn:hover::after {
            background: rgba(255,255,255,.06);
        }

        /* 1 — Ingresar al sistema */
        .btn-system {
            background: linear-gradient(135deg, var(--vs-blue) 0%, var(--vs-blue2) 100%);
            color: #fff;
            border: 1px solid rgba(26,110,245,.4);
            box-shadow: 0 8px 32px -8px rgba(26,110,245,.5);
        }

        .btn-system:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 40px -8px rgba(26,110,245,.6);
        }

        /* 2 — Registrarse como Gremio */
        .btn-gremio {
            background: rgba(255,255,255,.05);
            color: #fff;
            border: 1px solid rgba(255,255,255,.15);
            backdrop-filter: blur(8px);
        }

        .btn-gremio:hover {
            transform: translateY(-3px);
            border-color: rgba(255,255,255,.3);
            background: rgba(255,255,255,.09);
            box-shadow: 0 12px 30px -8px rgba(0,0,0,.4);
        }

        /* 3 — Ver Catálogo */
        .btn-catalog {
            background: linear-gradient(135deg, rgba(6,182,212,.15) 0%, rgba(6,182,212,.05) 100%);
            color: var(--vs-cyan);
            border: 1px solid rgba(6,182,212,.3);
        }

        .btn-catalog:hover {
            transform: translateY(-3px);
            background: rgba(6,182,212,.2);
            box-shadow: 0 12px 30px -8px rgba(6,182,212,.3);
        }

        .btn .material-symbols-outlined {
            font-size: 20px;
        }

        /* ── FEATURES ── */
        .features {
            position: relative;
            z-index: 1;
            padding: 80px 24px;
        }

        .features-inner {
            max-width: 1100px;
            margin: 0 auto;
        }

        .section-label {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .15em;
            color: var(--vs-blue);
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        .section-title {
            text-align: center;
            font-size: clamp(26px, 4vw, 40px);
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 56px;
            color: rgba(255,255,255,.92);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .feature-card {
            background: var(--vs-card);
            border: 1px solid var(--vs-border);
            border-radius: 20px;
            padding: 28px 24px;
            transition: all .3s ease;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            border-color: rgba(26,110,245,.35);
            background: rgba(26,110,245,.06);
            box-shadow: 0 20px 40px -12px rgba(26,110,245,.2);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            font-size: 22px;
        }

        .feature-card h3 {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 8px;
            color: rgba(255,255,255,.9);
        }

        .feature-card p {
            font-size: 13px;
            line-height: 1.6;
            color: rgba(255,255,255,.45);
        }

        /* ── FOOTER ── */
        footer {
            position: relative;
            z-index: 1;
            padding: 48px 40px;
            border-top: 1px solid var(--vs-border);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .footer-brand p:first-child {
            font-size: 14px;
            font-weight: 800;
            color: rgba(255,255,255,.8);
            margin-bottom: 4px;
        }

        .footer-brand p:last-child {
            font-size: 12px;
            color: rgba(255,255,255,.35);
        }

        .footer-links {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        .footer-links a {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
            color: rgba(255,255,255,.5);
            text-decoration: none;
            transition: color .2s;
        }

        .footer-links a:hover { color: #fff; }

        .footer-links a .material-symbols-outlined {
            font-size: 16px;
        }

        /* ── ANIMACIONES ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 600px) {
            header { padding: 14px 20px; }
            .header-nav a span.material-symbols-outlined { display: none; }
            .header-nav a .btn-label { display: none; }
            .hero { padding: 100px 20px 60px; }
            .cta-group { flex-direction: column; align-items: stretch; }
            .btn { justify-content: center; }
            footer { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>

<body>

    <!-- Fondo -->
    <div class="bg-canvas"></div>
    <div class="grid-overlay"></div>

    <!-- Header -->
    <header>
        <a href="index.php" class="header-logo">
            <img src="src/img/VSLogo_v2.jpg" alt="Vecino Seguro Logo">
            <div class="header-logo-text">
                <span>Vecino Seguro</span>
                <span>Distribuidores · Seguridad Electrónica</span>
            </div>
        </a>
        <nav class="header-nav">
            <a href="catalogo_web.php">
                <span class="material-symbols-outlined">inventory_2</span>
                <span class="btn-label">Catálogo</span>
            </a>
            <a href="registro.php">
                <span class="material-symbols-outlined">how_to_reg</span>
                <span class="btn-label">Registrarse</span>
            </a>
            <a href="https://wa.me/5492235772165" target="_blank" rel="noopener" class="btn-wa">
                <span class="material-symbols-outlined">chat</span>
                <span class="btn-label">WhatsApp</span>
            </a>
        </nav>
    </header>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-badge">
            <span></span>
            DISTRIBUIDOR OFICIAL · SEGURIDAD ELECTRÓNICA
        </div>

        <h1 class="hero-title">
            <span class="hl">Vecino</span><br>Seguro
        </h1>

        <p class="hero-tagline">
            Distribuidores especializados en <strong>sistemas de seguridad electrónica</strong>,
            alarmas profesionales, iluminación inteligente y robótica.
        </p>

        <p class="hero-slogan">
            "Lo que no encontrás en nuestro catálogo, lo buscamos por vos."
        </p>

        <div class="cta-group">
            <a href="login.php" class="btn btn-system" id="btn-ingresar">
                <span class="material-symbols-outlined">login</span>
                Ingresar al sistema
            </a>
            <a href="registro.php" class="btn btn-gremio" id="btn-registro-gremio">
                <span class="material-symbols-outlined">how_to_reg</span>
                Registrarse como Gremio
            </a>
            <a href="catalogo_web.php" class="btn btn-catalog" id="btn-catalogo">
                <span class="material-symbols-outlined">auto_stories</span>
                Ver Catálogo
            </a>
        </div>
    </section>

    <!-- Categorías destacadas -->
    <section class="features">
        <div class="features-inner">
            <p class="section-label">Lo que ofrecemos</p>
            <h2 class="section-title">Todo en seguridad electrónica</h2>

            <div class="features-grid">

                <div class="feature-card">
                    <div class="feature-icon" style="background:rgba(239,68,68,.1); color:#f87171;">
                        <span class="material-symbols-outlined">security</span>
                    </div>
                    <h3>Alarmas & Sensores</h3>
                    <p>Paneles, detectores de movimiento, apertura y vidrios rotos para instalaciones residenciales y comerciales.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background:rgba(245,158,11,.1); color:#fbbf24;">
                        <span class="material-symbols-outlined">video_camera_front</span>
                    </div>
                    <h3>CCTV & Cámaras IP</h3>
                    <p>Cámaras IP, DVR/NVR, visión nocturna y sistemas de almacenamiento en nube y local.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background:rgba(139,92,246,.1); color:#a78bfa;">
                        <span class="material-symbols-outlined">bolt</span>
                    </div>
                    <h3>Control de Acceso</h3>
                    <p>Lectores biométricos, RFID, cerraduras eléctricas y barreras vehiculares.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background:rgba(6,182,212,.1); color:#22d3ee;">
                        <span class="material-symbols-outlined">lightbulb</span>
                    </div>
                    <h3>Iluminación Inteligente</h3>
                    <p>Tiras LED, controladores RGB, sensores de presencia y automatización por escenas.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background:rgba(34,197,94,.1); color:#4ade80;">
                        <span class="material-symbols-outlined">wifi</span>
                    </div>
                    <h3>Networking & Redes</h3>
                    <p>Switches, access points, switches PoE y fibra óptica para proyectos de cualquier escala.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background:rgba(26,110,245,.1); color:#60a5fa;">
                        <span class="material-symbols-outlined">smart_toy</span>
                    </div>
                    <h3>Robótica & Domótica</h3>
                    <p>Automatización del hogar, sensores inteligentes y robots para aplicaciones industriales y educativas.</p>
                </div>

            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-brand">
            <p>Vecino Seguro</p>
            <p>Distribuidor de sistemas electrónicos de seguridad · Mar del Plata, Argentina</p>
        </div>
        <div class="footer-links">
            <a href="https://wa.me/5492235772165" target="_blank" rel="noopener">
                <span class="material-symbols-outlined">chat</span>
                +54 9 223 577-2165
            </a>
            <a href="mailto:vecinoseguro0@gmail.com">
                <span class="material-symbols-outlined">mail</span>
                vecinoseguro0@gmail.com
            </a>
            <a href="dev.php">
                <span class="material-symbols-outlined">code</span>
                Desarrollo de Software
            </a>
        </div>
    </footer>

</body>

</html>