<?php
/**
 * Prueba de concurrencia (CP-09): N solicitudes simultáneas del mismo usuario a la misma votación.
 * Resultado esperado: 1 voto registrado y N-1 rechazados.
 *
 * Uso:  php docs/entrega_final/prueba_concurrencia.php <id_votacion> <correo_nuevo@festival.test> [N=10] [URL=http://localhost:8080]
 */
if ($argc < 3) {
    fwrite(STDERR, "Uso: php prueba_concurrencia.php <id_votacion> <correo> [N] [URL]\n");
    exit(1);
}
[$votacion, $email] = [(int) $argv[1], $argv[2]];
$n = (int) ($argv[3] ?? 10);
$base = rtrim($argv[4] ?? 'http://localhost:8080', '/');
$jar = tempnam(sys_get_temp_dir(), 'cj');

function curl($url, $jar, $post = null)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    return $ch;
}

function ejecutar($ch)
{
    $cuerpo = curl_exec($ch);

    return [$cuerpo, curl_getinfo($ch, CURLINFO_EFFECTIVE_URL)];
}

function token($html)
{
    preg_match('/name="_token" value="([^"]+)"/', $html, $m);

    return $m[1] ?? '';
}

// 1) registro de un usuario nuevo (datos ficticios)
[$html] = ejecutar(curl("$base/registro", $jar));
// El registro exige documento (una cuenta por persona): se genera uno aleatorio para la prueba.
$documento = '9'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
ejecutar(curl("$base/registro", $jar, ['_token' => token($html), 'name' => 'Prueba Concurrencia', 'email' => $email,
    'tipo_documento' => 'CC', 'documento' => $documento,
    'password' => 'ClaveDePrueba123', 'password_confirmation' => 'ClaveDePrueba123', 'acepta_datos' => '1']));

// 2) token CSRF y primera opción de la votación
[$html] = ejecutar(curl("$base/votaciones/$votacion", $jar));
preg_match('/name="opcion_id" id="o\d+" value="(\d+)"/', $html, $m);
$csrf = token($html);
$opcion = $m[1] ?? null;
if (! $opcion) {
    fwrite(STDERR, "No se encontró una opción (¿votación abierta y usuario sin voto previo en la categoría?).\n");
    exit(1);
}

// 3) N solicitudes simultáneas
$multi = curl_multi_init();
$handles = [];
for ($i = 0; $i < $n; $i++) {
    $h = curl("$base/votaciones/$votacion/votar", $jar, ['_token' => $csrf, 'opcion_id' => $opcion]);
    curl_multi_add_handle($multi, $h);
    $handles[] = $h;
}
do {
    curl_multi_exec($multi, $activo);
    curl_multi_select($multi);
} while ($activo > 0);

$registrados = 0;
foreach ($handles as $h) {
    curl_multi_getcontent($h);
    if (str_contains(curl_getinfo($h, CURLINFO_EFFECTIVE_URL), '/gracias')) {
        $registrados++;
    }
    curl_multi_remove_handle($multi, $h);
}
echo "Solicitudes simultáneas enviadas: $n\n";
echo "  VOTO REGISTRADO: $registrados\n";
echo '  RECHAZADO (ya votaste en la categoría): '.($n - $registrados)."\n";
echo $registrados === 1 ? "OK: la regla un voto por categoría se cumplió.\n" : "ATENCIÓN: se esperaba exactamente 1 voto registrado.\n";
