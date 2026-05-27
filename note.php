<?php

$notesDir = __DIR__ . '/notes';

// Ensure notes directory exists
if (!is_dir($notesDir)) {
    @mkdir($notesDir, 0755, true);
}

// Sanitize filename to safe slug
function sanitizeSlug($title) {
    // Convert to lowercase
    $slug = strtolower($title);
    // Replace spaces with hyphens
    $slug = preg_replace('/\s+/', '-', $slug);
    // Remove any character that's not alphanumeric or hyphen
    $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
    // Remove multiple consecutive hyphens
    $slug = preg_replace('/-+/', '-', $slug);
    // Trim hyphens from start and end
    $slug = trim($slug, '-');
    // Limit to 80 characters
    $slug = substr($slug, 0, 80);

    return $slug ?: 'untitled';
}

// Markdown to HTML parser
function renderMarkdown($text) {
    // Escape HTML first, but we'll re-process markdown
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // Code blocks: ``` ... ```
    $text = preg_replace_callback(
        '/```([^`]*)```/s',
        function ($matches) {
            $code = htmlspecialchars(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            return '<pre><code>' . $code . '</code></pre>';
        },
        $text
    );

    // Inline code: `code`
    $text = preg_replace_callback(
        '/`([^`]+)`/',
        function ($matches) {
            return '<code>' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</code>';
        },
        $text
    );

    // Headings: # h1, ## h2, etc.
    $text = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $text);
    $text = preg_replace('/^#### (.*?)$/m', '<h4>$1</h4>', $text);
    $text = preg_replace('/^##### (.*?)$/m', '<h5>$1</h5>', $text);
    $text = preg_replace('/^###### (.*?)$/m', '<h6>$1</h6>', $text);

    // Bold: **text**
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

    // Italic: *text* and _text_
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/_(.+?)_/', '<em>$1</em>', $text);

    // Lists: - item
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
            // Paragraphs
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

// Check if editing an existing note
$editFile = null;
$title = '';
$content = '';

if (isset($_GET['file'])) {
    $filename = basename($_GET['file']);
    $filePath = $notesDir . '/' . $filename;

    // Security: prevent path traversal
    $realPath = realpath($filePath);
    if ($realPath && strpos($realPath, realpath($notesDir)) === 0 && file_exists($filePath)) {
        $editFile = $filename;
        $fileContent = file_get_contents($filePath);

        // Extract title from first line
        if (preg_match('/^#\s+(.+?)(?:\n|$)/', $fileContent, $matches)) {
            $title = trim($matches[1]);
            // Remove title line from content
            $content = preg_replace('/^#\s+.+?(?:\n|$)/m', '', $fileContent, 1);
        } else {
            $content = $fileContent;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (!empty($title)) {
        $slug = sanitizeSlug($title);
        $filePath = $notesDir . '/' . $slug . '.md';

        // Format content: title as first line
        $fileContent = '# ' . $title . "\n" . $content;

        if (file_put_contents($filePath, $fileContent) !== false) {
            header('Location: index.php');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editFile ? 'Edit' : 'New'; ?> Note</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header>
        <h1><?php echo $editFile ? '✏️ Edit Note' : '✍️ New Note'; ?></h1>
    </header>

    <main>
        <div class="note-form">
            <button class="toggle-button" onclick="toggleMode()">📖 Preview</button>

            <form method="POST">
                <div class="editor-section">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" placeholder="Note title..." required>
                    </div>

                    <div class="form-group">
                        <label for="content">Content (Markdown)</label>
                        <textarea id="content" name="content" placeholder="Write your note in Markdown..."><?php echo htmlspecialchars($content); ?></textarea>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">💾 Save</button>
                        <a href="index.php" class="btn btn-secondary">❌ Cancel</a>
                    </div>
                </div>
            </form>

            <div class="preview-section" id="preview">
                <h2 style="margin-top: 0; color: #c8f060;">Preview</h2>
                <div id="preview-content"></div>
                <div class="form-buttons">
                    <button class="btn btn-secondary" onclick="toggleMode()">← Back to Edit</button>
                </div>
            </div>
        </div>
    </main>

    <script>
        const titleInput = document.getElementById('title');
        const contentInput = document.getElementById('content');
        const previewSection = document.getElementById('preview');
        const previewContent = document.getElementById('preview-content');
        const editorSection = document.querySelector('.editor-section');
        const toggleBtn = document.querySelector('.toggle-button');

        function updatePreview() {
            const title = titleInput.value;
            const content = contentInput.value;
            const markdown = (title ? '# ' + title : '') + '\n' + content;

            // Send to server for rendering
            fetch('note.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=preview&markdown=' + encodeURIComponent(markdown)
            })
            .then(response => response.text())
            .then(html => {
                previewContent.innerHTML = html;
            })
            .catch(err => {
                console.error('Preview error:', err);
            });
        }

        function toggleMode() {
            editorSection.classList.toggle('hidden');
            previewSection.classList.toggle('active');

            if (previewSection.classList.contains('active')) {
                updatePreview();
                toggleBtn.textContent = '✏️ Edit';
            } else {
                toggleBtn.textContent = '📖 Preview';
            }
        }

        // Update preview when typing
        titleInput.addEventListener('input', updatePreview);
        contentInput.addEventListener('input', updatePreview);
    </script>
</body>
</html>

<?php
// Handle AJAX preview request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'preview') {
    echo renderMarkdown($_POST['markdown'] ?? '');
    exit;
}
?>
