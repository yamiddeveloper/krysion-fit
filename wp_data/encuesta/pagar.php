<?php
session_start();
$plan_name = $_GET['plan'] ?? ($_SESSION['chosen_plan'] ?? 'KRYSION BASE');
$plan_price = $_GET['price'] ?? ($_SESSION['chosen_price'] ?? '18');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripción Krysion Fit</title>
    <!-- Outfit Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- PayPal SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=sb&currency=USD"></script>

    <style>
        :root {
            --primary: #F2600C;
            --primary-dark: #cc520a;
            --bg-dark: #050505;
            --card-surface: rgba(20, 20, 20, 0.7);
            --card-border: rgba(255, 255, 255, 0.1);
            --text-main: #FFFFFF;
            --text-secondary: #999999;
            --radius-lg: 32px;
            --font-main: 'Outfit', sans-serif;
            --yape: #742284;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body { 
            background-color: var(--bg-dark); 
            color: var(--text-main); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: var(--font-main);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(242, 96, 12, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(242, 96, 12, 0.05) 0%, transparent 40%);
            background-attachment: fixed;
            padding: 40px 20px;
        }

        .payment-card { 
            background: var(--card-surface);
            backdrop-filter: blur(30px);
            border: 1px solid var(--card-border); 
            border-radius: var(--radius-lg);
            box-shadow: 0 40px 80px rgba(0,0,0,0.5);
            max-width: 480px;
            width: 100%;
            padding: 60px 40px;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
            position: relative;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .logo-box { margin-bottom: 30px; }
        .logo-box img { width: 70px; height: 70px; border-radius: 50%; border: 2px solid var(--primary); padding: 5px; }

        .plan-badge {
            background: rgba(242,96,12,0.15);
            color: var(--primary);
            padding: 6px 16px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 15px;
        }

        h1 { font-size: 2.2rem; font-weight: 800; margin-bottom: 10px; }
        .price { color: #fff; font-weight: 800; font-size: 3rem; margin-bottom: 30px; }
        .price span { color: var(--primary); }

        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-yape {
            background: var(--yape);
            color: #fff;
            padding: 16px;
            border-radius: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: 0.3s;
            border: none;
            width: 100%;
            font-size: 16px;
        }
        .btn-yape:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(116, 34, 132, 0.4); }

        .btn-wa {
            background: #25D366;
            color: #fff;
            padding: 14px;
            border-radius: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            margin-top: 25px;
            font-size: 14px;
            transition: 0.3s;
        }
        .btn-wa:hover { opacity: 0.9; }

        /* Modal Yape */
        .modal {
            position: fixed; inset: 0; background: rgba(0,0,0,0.9);
            display: none; align-items: center; justify-content: center;
            z-index: 1000; padding: 20px;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #fff; color: #333;
            padding: 40px; border-radius: 30px;
            max-width: 400px; width: 100%; text-align: center;
            position: relative;
        }
        .qr-placeholder {
            width: 250px; height: 250px; background: #f0f0f0;
            margin: 20px auto; border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            border: 2px dashed #ccc;
        }
        .qr-placeholder img { width: 100%; height: 100%; border-radius: 20px; }
        .close-modal {
            position: absolute; top: 15px; right: 20px;
            font-size: 24px; cursor: pointer; color: #999;
        }
    </style>
</head>
<body>

    <div class="payment-card">
        <div class="logo-box">
            <img src="/wp-content/uploads/2026/02/cropped-favicon.png" alt="Krysion Fit">
        </div>
        
        <span class="plan-badge">Activar suscripción</span>
        <h1><?php echo htmlspecialchars($plan_name); ?></h1>
        <div class="price"><span>$</span><?php echo htmlspecialchars($plan_price); ?></div>
        
        <p style="color:var(--text-secondary); margin-bottom:30px; font-size:14px;">Elige tu método de pago preferido para activar tu programa de entrenamiento.</p>

        <div class="payment-methods">
            <!-- PayPal -->
            <div id="paypal-button-container"></div>
            
            <!-- Yape (Solo para Perú) -->
            <button class="btn-yape" id="yapeBtn">
                Pagar con Yape
            </button>
        </div>

        <a href="https://wa.me/51907356?text=Hola,%20acabo%20de%20completar%20mi%20registro%20para%20el%20plan%20<?php echo urlencode($plan_name); ?>" 
           class="btn-wa" target="_blank">
            💬 Consultas por WhatsApp
        </a>
    </div>

    <!-- Modal Yape -->
    <div class="modal" id="yapeModal">
        <div class="modal-content">
            <span class="close-modal" id="closeModal">&times;</span>
            <h2 style="color:#742284; margin-bottom:10px;">Escanea y Yapea</h2>
            <p style="font-size:14px; color:#666;">Envía el monto de <b>S/<?php echo number_format($plan_price * 3.75, 2); ?></b></p>
            
            <div class="qr-placeholder">
                <!-- Reemplazar con imagen real del QR -->
                <img src="/encuesta/uploads/yape.jpeg" alt="QR Yape">
            </div>
            
            <div style="background:#f9f9f9; padding:15px; border-radius:15px; margin-top:10px;">
                <p style="font-size:13px; font-weight:700; color:#333;">IMPORTANTE:</p>
                <p style="font-size:12px; color:#555;">Una vez realizado el depósito, **envía la captura del comprobante por WhatsApp** para activar tu cuenta de inmediato.</p>
            </div>
            
            <a href="https://wa.me/51907356?text=Hola!%20Aquí%20está%20mi%20comprobante%20de%20Yape%20para%20el%20plan%20<?php echo urlencode($plan_name); ?>" 
               target="_blank" 
               class="btn-wa" style="margin-top:15px;">
                Enviar comprobante ahora
            </a>
        </div>
    </div>

    <script>
        // Modal Logic
        const yapeBtn = document.getElementById('yapeBtn');
        const yapeModal = document.getElementById('yapeModal');
        const closeModal = document.getElementById('closeModal');

        yapeBtn.addEventListener('click', () => yapeModal.classList.add('active'));
        closeModal.addEventListener('click', () => yapeModal.classList.remove('active'));
        yapeModal.addEventListener('click', (e) => { if(e.target === yapeModal) yapeModal.classList.remove('active'); });

        // PayPal
        paypal.Buttons({
            style: { shape: 'pill', color: 'gold', layout: 'vertical', label: 'pay' },
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        description: "Plan Personalizado Krysion Fit: <?php echo $plan_name; ?>",
                        amount: { value: '<?php echo $plan_price; ?>' }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    window.location.href = "pago_exitoso.php?plan=<?php echo urlencode($plan_name); ?>";
                });
            }
        }).render('#paypal-button-container');
    </script>
</body>
</html>