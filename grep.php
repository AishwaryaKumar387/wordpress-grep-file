<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the 'q' parameter exists and is not empty
if (empty($_GET['q'])) {
    die('No query found. Please provide a search query using ?q=your_query');
}

function searchForQuery($directory, $pattern) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    $results = [];

    foreach ($rii as $file) {
        if (!$file->isReadable() || $file->getSize() > 10485760) {
            continue; // Skip unreadable or large files (>10MB)
        }

        if (!$file->isDir() && preg_match('/\.(php|html|js)$/', $file->getFilename())) {
            $handle = fopen($file->getPathname(), "r");
            if ($handle) {
                $lineNumber = 0;
                while (($line = fgets($handle)) !== false) {
                    $lineNumber++;
                    if (preg_match($pattern, $line)) {
                        $results[] = [
                            'file' => $file->getPathname(),
                            'line_number' => $lineNumber,
                            'content' => htmlspecialchars(trim($line))
                        ];
                    }
                }
                fclose($handle);
            }
        }
    }

    return $results;
}

// Get search query from URL parameter
$query = $_GET['q'];
$pattern = '/' . preg_quote($query, '/') . '/';
$directory = __DIR__;

// Set memory limit
ini_set('memory_limit', '2G');

// Perform search
$results = searchForQuery($directory, $pattern);

if (empty($results)) {
    echo "No matches found for '<strong>" . htmlspecialchars($query) . "</strong>'.";
} else {
    echo "<h2>Matches Found for '<strong>" . htmlspecialchars($query) . "</strong>':</h2>";
    echo "<ul>";
    foreach ($results as $result) {
        echo "<li><strong>File:</strong> {$result['file']} | <strong>Line:</strong> {$result['line_number']}<br> <strong>Content:</strong> {$result['content']}</li>";
    }
    echo "</ul>";
}