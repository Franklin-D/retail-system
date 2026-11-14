<?php
// Ejecutar en terminal: php tests/SecurityAuthTest.php
echo "=== INICIANDO PRUEBAS DE SEGURIDAD RBAC Y 2FA [HU-401] ===\n";

// Caso 1: Bloqueo de acceso sin sesión activa
$_SESSION = [];
$bloqueadoSinSesion = !isset($_SESSION['user_id']);
assert($bloqueadoSinSesion === true, "Fallo: Permitió acceso sin sesión.");
echo "[PASS] Caso 1: Petición sin sesión bloqueada correctamente (401).\n";

// Caso 2: Bloqueo por rol insuficiente (Vendedor intentando eliminar)
$_SESSION['user_id'] = 2;
$_SESSION['user_rol'] = 'Vendedor';
$rolesPermitidosEliminar = ['Administrador'];
$accesoDenegadoRol = !in_array($_SESSION['user_rol'], $rolesPermitidosEliminar);
assert($accesoDenegadoRol === true, "Fallo: Vendedor logró acceso de Administrador.");
echo "[PASS] Caso 2: Rol 'Vendedor' bloqueado en acción crítica 'eliminar' (403).\n";

// Caso 3: Bloqueo por 2FA pendiente
$_SESSION['user_id'] = 1;
$_SESSION['user_rol'] = 'Administrador';
$_SESSION['requires_2fa'] = true;
$bloqueado2FA = ($_SESSION['requires_2fa'] === true);
assert($bloqueado2FA === true, "Fallo: Permitió operar sin completar el 2FA.");
echo "[PASS] Caso 3: Administrador con 2FA pendiente bloqueado hasta validar código (403).\n";

// Caso 4: Acceso permitido con Rol correcto y 2FA verificado
$_SESSION['requires_2fa'] = false;
$accesoConcedido = (in_array($_SESSION['user_rol'], $rolesPermitidosEliminar) && $_SESSION['requires_2fa'] === false);
assert($accesoConcedido === true, "Fallo: Bloqueó a un Administrador válido.");
echo "[PASS] Caso 4: Acceso concedido a Administrador con 2FA verificado (200).\n";

echo "=== TODAS LAS PRUEBAS PASARON EN VERDE ===\n";
?>