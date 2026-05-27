<?php

$notesDir = __DIR__ . '/notes';

// Garante que o diretório de notas exista
if (!is_dir($notesDir)) {
    @mkdir($notesDir, 0755, true);
}

// Sanitiza slug do título
function sanitizeSlug($title) {
    // Converte para minúsculas
    $slug = strtolower($title);
    // Substitui espaços por hífens
    $slug = preg_replace('/\s+/', '-', $slug);
    // Remove caracteres não alfanuméricos ou hífens
    $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
    // Remove hífens múltiplos consecutivos
    $slug = preg_replace('/-+/', '-', $slug);
    // Remove hífens no início e fim
    $slug = trim($slug, '-');
    // Limita a 80 caracteres
    $slug = substr($slug, 0, 80);

    return $slug ?: 'untitled';
}

// Conversor Markdown para HTML (simples)
function renderMarkdown($text) {
    // Escapa HTML primeiro, mas vamos reprocessar markdown
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // Blocos de código: ``` ... ```
    $text = preg_replace_callback(
        '/```([^`]*)```/s',
        function ($matches) {
            $code = htmlspecialchars(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            return '<pre><code>' . $code . '</code></pre>';
        },
        $text
    );

    // Código inline: `code`
    $text = preg_replace_callback(
        '/`([^`]+)`/',
        function ($matches) {
            return '<code>' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</code>';
        },
        $text
    );

    // Cabeçalhos: # h1, ## h2, etc.
    $text = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $text);
    $text = preg_replace('/^#### (.*?)$/m', '<h4>$1</h4>', $text);
    $text = preg_replace('/^##### (.*?)$/m', '<h5>$1</h5>', $text);
    $text = preg_replace('/^###### (.*?)$/m', '<h6>$1</h6>', $text);

    // Negrito: **texto**
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

    // Itálico: *texto* e _texto_
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/_(.+?)_/', '<em>$1</em>', $text);

    // Listas: - item
    $lines = explode("\n", $text);
    $inList = false;
    $result = [];

    foreach ($lines as $line) {
        if (preg_match('/^- (.*)/', $line, $matches)) {
            if (!$inList) {
                $result[] = '<ul>';
                $inList = true;
            }
            $result[] = '<li>' . $matches[1] . '</li>';
        } else {
            if ($inList) {
                $result[] = '</ul>';
                $inList = false;
            }
            // Parágrafos
            if (trim($line)) {
                if (!preg_match('/<(h[1-6]|pre|ul|ol)/', $line)) {
                    $result[] = '<p>' . $line . '</p>';
                } else {
                    $result[] = $line;
                }
            }
        }
    }

    if ($inList) {
        $result[] = '</ul>';
    }

    return '<div class="markdown-preview">' . implode("\n", $result) . '</div>';
}

// Trata requisição AJAX de pré-visualização
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'preview') {
    echo renderMarkdown($_POST['markdown'] ?? '');
    exit;
}

// Verifica se está editando uma nota existente
$editFile = null;
$title = '';
$content = '';

if (isset($_GET['file'])) {
    $filename = basename($_GET['file']);
    $filePath = $notesDir . '/' . $filename;

    // Segurança: evita traversal de caminho
    $realPath = realpath($filePath);
    if ($realPath && strpos($realPath, realpath($notesDir)) === 0 && file_exists($filePath)) {
        $editFile = $filename;
        $fileContent = file_get_contents($filePath);

        // Extrai título da primeira linha
        if (preg_match('/^#\s+(.+?)(?:\n|$)/', $fileContent, $matches)) {
            $title = trim($matches[1]);
            // Remove linha de título do conteúdo
            $content = preg_replace('/^#\s+.+?(?:\n|$)/m', '', $fileContent, 1);
        } else {
            $content = $fileContent;
        }
    }
}

// Trata submissão do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (!empty($title)) {
        $slug = sanitizeSlug($title);
        $filePath = $notesDir . '/' . $slug . '.md';

        // Formata conteúdo: título como primeira linha
        $fileContent = '# ' . $title . "\n" . $content;

        if (file_put_contents($filePath, $fileContent) !== false) {
            header('Location: index.php');
            exit;
        }
    }
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
