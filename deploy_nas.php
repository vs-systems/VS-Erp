<?php
/**
 * VS System ERP - NAS Auto-Deploy Webhook
 * This script allows your NAS to automatically pull changes from GitHub.
 */

// 1. Configuration
$repo_dir = '/volume1/Web/vsys_erp'; // Update this to your absolute path on the NAS
$branch = 'main';

// 2. Execution logic
echo "<h2>NAS Deploy System</h2>";

// Check if we are in a git repo
if (!is_dir("$repo_dir/.git")) {
    die("Error: The directory is not a git repository. Please clone it first via SSH.");
}

// Execute git pull
$output = shell_exec("cd $repo_dir && git pull origin $branch 2>&1");

echo "<pre>$output</pre>";

if (strpos($output, 'Updating') !== false || strpos($output, 'Already up to date') !== false) {
    echo "<p style='color: green;'>âœ… Sync complete!</p>";
} else {
    echo "<p style='color: red;'>âŒ Sync failed. Check if Git is installed on your NAS via App Central.</p>";
}
?>





