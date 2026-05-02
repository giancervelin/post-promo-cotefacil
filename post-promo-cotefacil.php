<?php
// 1. Captures data from the URL (GET)
$id_funcionario_raw = $_GET['id_funcionario'] ?? '';
$cargo = $_GET['cargo'] ?? '';

// 2. Diagnostic log
file_put_contents(__DIR__ . '/debug.log',
    date('Y-m-d H:i:s') . " - URL Recebida: ID: $id_funcionario_raw | Cargo: $cargo" . PHP_EOL,
    FILE_APPEND
);

// 3. Normalize user ID (extract only numbers)
preg_match('/\d+/', (string)$id_funcionario_raw, $m);
$funcionario_id = $m[0] ?? '';

if ($funcionario_id === '') {
    file_put_contents(__DIR__ . '/debug.log',
        date('Y-m-d H:i:s') . " - ERRO: não consegui extrair ID do funcionário de: {$id_funcionario_raw}" . PHP_EOL,
        FILE_APPEND
    );
    http_response_code(400);
    echo "Missing/invalid employee id";
    exit;
}

// 4. Retrieve employee name via Bitrix API.
$user_endpoint = 'KEY REMOVED FOR SECURITY';

$ch_user = curl_init();
curl_setopt_array($ch_user, [
    CURLOPT_URL => $user_endpoint,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['ID' => $funcionario_id]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
]);
$user_response = curl_exec($ch_user);
$curlErrUser = curl_error($ch_user);
curl_close($ch_user);

if ($curlErrUser) {
    file_put_contents(__DIR__ . '/debug.log',
        date('Y-m-d H:i:s') . " - ERRO user.get: {$curlErrUser}" . PHP_EOL,
        FILE_APPEND
    );
    $nome_funcionario = 'Funcionário';
} else {
    $user_info = json_decode($user_response, true);
    $nome = $user_info['result'][0]['NAME'] ?? '';
    $sobrenome = $user_info['result'][0]['LAST_NAME'] ?? '';
    $nome_funcionario = trim($nome . ' ' . $sobrenome);
    if ($nome_funcionario === '') $nome_funcionario = 'Funcionário';
}

// 5. Assemble the message (BBCode) - requested adjustments:
// - Employee's name larger
// - Text size +1 point (increased body SIZE)
// - Line breaks exactly as requested
$mensagem = "[CENTER][IMG]https://cotefacil.online/wp-content/uploads/2026/01/topo_anuncio_promocaoo.png[/IMG]\n\n";
$mensagem .= "[SIZE=37pt][I][B]{$nome_funcionario}[/B][/I][/SIZE]\n\n";

$mensagem .= "[SIZE=14pt]";
$mensagem .= "Por seu esforço, trabalho e dedicação passa a ocupar o cargo de\n";
$mensagem .= "[B]{$cargo}[/B] em nossa empresa. Reafirmamos\n";
$mensagem .= "a importância de tê-lo(a) em nosso elenco. Ninguém constrói nada\n";
$mensagem .= "sozinho, estamos juntos nessa jornada, crescendo profissional e\n";
$mensagem .= "pessoalmente. Agradeço pelo esforço, pela dedicação, e pelo\n";
$mensagem .= "trabalho que sempre desempenhou com tanta energia.";
$mensagem .= "[/SIZE]\n\n";

$mensagem .= "[IMG]https://cotefacil.online/wp-content/uploads/2026/01/rodape_anuncio_promocao.png[/IMG][/CENTER]";

// 6. Posted on the Bitrix Feed
$endpoint = 'KEY REMOVED FOR SECURITY';

$postFields = [
    'POST_TITLE'   => '', // untitled
    'POST_MESSAGE' => $mensagem,
    'DEST'         => ['UA', 'U' . $funcionario_id], // All + Specific User
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $endpoint,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($postFields),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
]);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
curl_close($ch);

file_put_contents(__DIR__ . '/debug.log',
    date('Y-m-d H:i:s') . " - Resposta Bitrix: " . ($curlErr ? "CURL_ERR={$curlErr}" : $response) . PHP_EOL,
    FILE_APPEND
);

echo "ok";