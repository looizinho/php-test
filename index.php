<?php
$notesDir = __DIR__ . '/notes';

// Garante que o diretório de notas exista
if (!is_dir($notesDir)) {
    @mkdir($notesDir, 0755, true);
}

// Recupera todos os arquivos markdown
$files = glob($notesDir . '/*.md');

// Ordena pelos tempos de modificação (mais recentes primeiro)
usort($files, function ($a, $b) {
    return filemtime($b) - filemtime($a);
});

// Extrai o título da primeira linha do arquivo markdown
function extractTitle($filePath) {
    $content = file_get_contents($filePath);
    if (preg_match('/^#\s+(.+?)$/m', $content, $matches)) {
        return trim($matches[1]);
    }
    // Fallback para nome do arquivo sem extensão
    return basename($filePath, '.md');
}

// Formata diferença de tempo
function formatTime($timestamp) {
    $now = time();
    $diff = $now - $timestamp;

    if ($diff < 60) return 'agora mesmo';
    if ($diff < 3600) return intval($diff / 60) . ' minutos atrás';
    if ($diff < 86400) return intval($diff / 3600) . ' horas atrás';
    if ($diff < 604800) return intval($diff / 86400) . ' dias atrás';

    return date('d/m/Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloco de Notas Markdown</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header>
        <h1>📝 Notas</h1>
    </header>

    <main>
        <?php if (empty($files)): ?>
            <div class="empty-state">
                <h2>Nenhuma nota ainda</h2>
                <p>Clique no botão + abaixo para criar sua primeira nota.</p>
            </div>
        <?php else: ?>
            <div class="notes-grid">
                <?php foreach ($files as $file): ?>
                    <?php
                        $title = extractTitle($file);
                        $filename = basename($file);
                        $mtime = filemtime($file);
                        $timeStr = formatTime($mtime);
                    ?>
                    <div class="note-card">
                        <a href="note.php?file=<?php echo urlencode($filename); ?>" style="text-decoration: none; color: inherit; flex-grow: 1;">
                            <h3><?php echo htmlspecialchars($title); ?></h3>
                            <div class="note-card-meta">
                                <span><?php echo htmlspecialchars($timeStr); ?></span>
                            </div>
                        </a>
                        <form method="POST" action="delete.php" style="display: inline;">
                            <input type="hidden" name="file" value="<?php echo htmlspecialchars($filename); ?>">
                            <button type="submit" class="delete-btn" onclick="return confirm('Excluir esta nota?');" title="Excluir nota">🗑️</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <a href="note.php" class="fab">+</a>
</body>
</html>
