<?php

$notesDir = __DIR__ . '/notes';

// Ensure notes directory exists
if (!is_dir($notesDir)) {
    @mkdir($notesDir, 0755, true);
}

// Get all markdown files
$files = glob($notesDir . '/*.md');

// Sort by modification time (newest first)
usort($files, function ($a, $b) {
    return filemtime($b) - filemtime($a);
});

// Extract title from first line of markdown file
function extractTitle($filePath) {
    $content = file_get_contents($filePath);
    if (preg_match('/^#\s+(.+?)$/m', $content, $matches)) {
        return trim($matches[1]);
    }
    // Fallback to filename without extension
    return basename($filePath, '.md');
}

// Format time difference
function formatTime($timestamp) {
    $now = time();
    $diff = $now - $timestamp;

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return intval($diff / 60) . ' minutes ago';
    if ($diff < 86400) return intval($diff / 3600) . ' hours ago';
    if ($diff < 604800) return intval($diff / 86400) . ' days ago';

    return date('M d, Y', $timestamp);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Markdown Notepad</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header>
        <h1>📝 Notes</h1>
    </header>

    <main>
        <?php if (empty($files)): ?>
            <div class="empty-state">
                <h2>No notes yet</h2>
                <p>Click the + button below to create your first note.</p>
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
                            <button type="submit" class="delete-btn" onclick="return confirm('Delete this note?');" title="Delete note">🗑️</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <a href="note.php" class="fab">+</a>
</body>
</html>
