<?php
// Local DB overrides for development (XAMPP). This file is intentionally optional and should NOT be committed with production credentials.
// To use, set your local values here or via environment variables.

// Example usage (uncomment and set values if needed):
// putenv('DB_HOST=127.0.0.1');
// putenv('DB_NAME=hr441');
// putenv('DB_USER=root');
// putenv('DB_PASS=');

// Lightweight fallback: if env vars not set, define them here for local dev.
if (getenv('DB_HOST') === false) putenv('DB_HOST=localhost');
if (getenv('DB_NAME') === false) putenv('DB_NAME=hr441');
if (getenv('DB_USER') === false) putenv('DB_USER=root');
if (getenv('DB_PASS') === false) putenv('DB_PASS=');

// Do NOT echo or log secrets here.
?>
