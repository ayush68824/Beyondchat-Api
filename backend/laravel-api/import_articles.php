<?php

/**
 * Script to import scraped articles from JSON file into Laravel database
 * 
 * Usage: php import_articles.php [path_to_json_file]
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Article;

$jsonFile = $argv[1] ?? __DIR__ . '/../scraper/scraped_articles.json';

if (!file_exists($jsonFile)) {
    echo "Error: JSON file not found at: $jsonFile\n";
    echo "Please run the scraper first or provide the path to the JSON file.\n";
    exit(1);
}

$articles = json_decode(file_get_contents($jsonFile), true);

if (!$articles || !is_array($articles)) {
    echo "Error: Invalid JSON file or no articles found.\n";
    exit(1);
}

echo "Importing " . count($articles) . " articles...\n\n";

$imported = 0;
$skipped = 0;

foreach ($articles as $index => $article) {
    try {
        // Check if article already exists (by title)
        $existing = Article::where('title', $article['title'])->first();
        
        if ($existing) {
            echo "Skipping: \"{$article['title']}\" (already exists)\n";
            $skipped++;
            continue;
        }
        
        Article::create([
            'title' => $article['title'] ?? 'Untitled',
            'content' => $article['content'] ?? '',
            'full_content' => $article['full_content'] ?? $article['content'] ?? '',
            'link' => $article['link'] ?? null,
            'date' => $article['date'] ? date('Y-m-d H:i:s', strtotime($article['date'])) : now(),
            'source_url' => $article['source_url'] ?? 'https://beyondchats.com/blogs/',
            'is_updated' => false,
        ]);
        
        echo "✓ Imported: \"{$article['title']}\"\n";
        $imported++;
    } catch (Exception $e) {
        echo "✗ Error importing \"{$article['title']}\": {$e->getMessage()}\n";
    }
}

echo "\n";
echo "Import complete!\n";
echo "Imported: $imported\n";
echo "Skipped: $skipped\n";
echo "Total: " . count($articles) . "\n";

