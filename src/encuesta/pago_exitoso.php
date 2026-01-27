<?php
session_start();

// Aquí validamos que el pago fue real.
// En producción, aquí verificarías un token de Stripe/MercadoPago.

// 1. Crear la sesión segura
$_SESSION['acceso_formulario'] = true;
$_SESSION['tiempo_pago'] = time();

// 2. Redirigir al formulario inmediatamente
header("Location: index.php");
exit();
?>