# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Common Development Commands

- **Run the application locally**:  
  `php -S localhost:8000` (starts the built‑in PHP dev server; then open http://localhost:8000 in a browser).

- **Syntax check**:  
  `php -l *.php`

- **Format / lint** (optional, if `phpcs` or `phpcbf` is installed):  
  `phpcs --standard=PSR12 *.php`

- **Create a new note**:  
  Visit `http://localhost:8000/note.php` (or click the “+” button on the index page). The form creates a Markdown file under `notes/` with a slug derived from the title.

- **Edit an existing note**:  
  Click a note on the index page or open `http://localhost:8000/note.php?file=<slug>.md`.

- **Delete a note**:  
  Press the trash‑can button on a note card; the request is sent to `delete.php` via POST.

- **Preview Markdown**:  
  While editing, the UI sends a POST request with `action=preview` to `note.php`; the server returns the rendered HTML.

## High‑Level Architecture

- **Entry points**
  - `index.php` – Lists notes, sorts by modification time, renders titles and timestamps, and provides delete forms.
  - `note.php` – Handles creating, editing, loading, and previewing a single Markdown note.
  - `delete.php` – Performs a POST‑only file deletion with strict path‑traversal checks.

- **Data storage**
  - All notes are plain Markdown files (`*.md`) stored in a sibling `notes/` directory created on first use (`mkdir -p notes`). The filename is a slug generated from the note title (`sanitizeSlug()`).

- **Core helpers**
  - `sanitizeSlug()` – Normalizes titles to safe, URL‑friendly filenames (lower‑case, hyphens, alphanumerics, max 80 chars). Guarantees no directory traversal.
  - `renderMarkdown()` – Very lightweight Markdown‑to‑HTML conversion (headings, lists, inline code, code blocks, bold/italic, etc.) with HTML escaping for safety.
  - `extractTitle()` (in `index.php`) – Reads the first `# ` heading of a Markdown file to use as the note title; falls back to the filename.

- **Security considerations**
  - All file paths are sanitized with `basename()` and `realpath()` checks to ensure they remain inside the `notes/` directory.
  - `delete.php` validates the filename against a RegExp (`^[a-z0-9-]+\.md$`) before deletion.
  - POST actions (create/edit, preview, delete) are the only mutable endpoints; GET requests only read files.

- **Front‑end**
  - `assets/style.css` provides a dark theme, responsive grid layout, and a floating “add” button.
  - JavaScript in `note.php` toggles between edit and preview modes, sends an AJAX POST for live preview, and updates the preview in real time.

## Extensibility Points

- **Adding persistent storage** – Replace the filesystem‑based `notes/` folder with a database; keep the same slug logic and markdown rendering pipeline.
- **Improving Markdown** – Swap `renderMarkdown()` with a full parser (e.g. `Parsedown`) without changing the request/response contract.
- **Authentication** – Insert a session check at the top of each PHP file if the app needs user accounts.
