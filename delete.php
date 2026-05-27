<?php

// Apenas permite requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido');
}

$notesDir = __DIR__ . '/notes';

if (!isset($_POST['file'])) {
    http_response_code(400);
    die('Parâmetro de arquivo ausente');
}

$filename = basename($_POST['file']);
$filePath = $notesDir . '/' . $filename;

// Segurança: impede traversal de caminho
$realPath = realpath($filePath);
$notesRealPath = realpath($notesDir);

if (!$realPath || !$notesRealPath || strpos($realPath, $notesRealPath) !== 0) {
    http_response_code(400);
    die('Caminho de arquivo inválido');
}

// Verifica se o arquivo existe
if (!file_exists($filePath) || !is_file($filePath)) {
    http_response_code(404);
    die('Arquivo não encontrado');
}

// Valida nome do arquivo (apenas letras minúsculas, números e hífens)
if (!preg_match('/^[a-z0-9-]+\.md$/', $filename)) {
    http_response_code(400);
    die('Nome de arquivo inválido');
}

// Exclui o arquivo
if (unlink($filePath)) {
    header('Location: index.php');
    exit;
} else {
    http_response_code(500);
    die('Falha ao excluir o arquivo');
}
?>
