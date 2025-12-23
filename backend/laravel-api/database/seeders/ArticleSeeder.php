<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Article;
use Illuminate\Support\Facades\File;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Try multiple possible paths for the JSON file
        $possiblePaths = [
            __DIR__ . '/scraped_articles.json', // In seeders directory
            __DIR__ . '/../../scraper/scraped_articles.json',
            __DIR__ . '/../../../scraper/scraped_articles.json',
            base_path('../scraper/scraped_articles.json'),
            storage_path('app/scraped_articles.json'),
        ];

        $jsonFile = null;
        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                $jsonFile = $path;
                break;
            }
        }

        if (!$jsonFile) {
            $this->command->warn('No scraped_articles.json file found. Creating sample articles instead...');
            $this->createSampleArticles();
            return;
        }

        $this->command->info("Found JSON file at: {$jsonFile}");
        $articles = json_decode(File::get($jsonFile), true);

        if (!$articles || !is_array($articles)) {
            $this->command->error('Invalid JSON file or no articles found.');
            $this->createSampleArticles();
            return;
        }

        $this->command->info("Importing " . count($articles) . " articles...");

        $imported = 0;
        $skipped = 0;

        foreach ($articles as $article) {
            try {
                // Check if article already exists (by title)
                $existing = Article::where('title', $article['title'] ?? '')->first();
                
                if ($existing) {
                    $this->command->line("Skipping: \"{$article['title']}\" (already exists)");
                    $skipped++;
                    continue;
                }
                
                Article::create([
                    'title' => $article['title'] ?? 'Untitled',
                    'content' => $article['content'] ?? '',
                    'full_content' => $article['full_content'] ?? $article['content'] ?? '',
                    'link' => $article['link'] ?? null,
                    'date' => isset($article['date']) ? date('Y-m-d H:i:s', strtotime($article['date'])) : now(),
                    'source_url' => $article['source_url'] ?? 'https://beyondchats.com/blogs/',
                    'is_updated' => false,
                ]);
                
                $this->command->info("✓ Imported: \"{$article['title']}\"");
                $imported++;
            } catch (\Exception $e) {
                $this->command->error("✗ Error importing \"{$article['title']}\": {$e->getMessage()}");
            }
        }

        $this->command->info("\nImport complete!");
        $this->command->info("Imported: {$imported}");
        $this->command->info("Skipped: {$skipped}");
        $this->command->info("Total: " . count($articles));

        // Create at least one updated article
        $this->createUpdatedArticle();
    }

    /**
     * Create an updated/enhanced article based on an original article
     */
    private function createUpdatedArticle(): void
    {
        // Find the first original article (not updated) to create an enhanced version
        $originalArticle = Article::where('is_updated', false)->orderBy('id')->first();

        if (!$originalArticle) {
            $this->command->warn('No original articles found to create updated version.');
            return;
        }

        // Check if updated version already exists
        $existingUpdated = Article::where('original_article_id', $originalArticle->id)
                                  ->where('is_updated', true)
                                  ->first();

        if ($existingUpdated) {
            $this->command->info("Updated article already exists for: \"{$originalArticle->title}\"");
            return;
        }

        // Create enhanced content with additional insights
        $enhancedContent = $this->generateEnhancedContent($originalArticle);

        // Create reference articles
        $referenceArticles = [
            [
                'title' => 'AI in Healthcare: A Comprehensive Guide',
                'url' => 'https://beyondchats.com/blogs/should-you-trust-ai-in-healthcare/'
            ],
            [
                'title' => 'BeyondChats: Digital Receptionist for Clinics',
                'url' => 'https://beyondchats.com/'
            ],
            [
                'title' => 'Healthcare AI Implementation Best Practices',
                'url' => 'https://beyondchats.com/blogs/'
            ]
        ];

        try {
            $updatedArticle = Article::create([
                'title' => $originalArticle->title . ' ✨ Enhanced',
                'content' => substr($enhancedContent, 0, 300) . '...',
                'full_content' => $enhancedContent,
                'link' => $originalArticle->link,
                'date' => now(),
                'source_url' => $originalArticle->source_url,
                'is_updated' => true,
                'original_article_id' => $originalArticle->id,
                'reference_articles' => $referenceArticles,
            ]);

            $this->command->info("✓ Created updated article: \"{$updatedArticle->title}\"");
            $this->command->info("  → Based on original: \"{$originalArticle->title}\"");
        } catch (\Exception $e) {
            $this->command->error("✗ Error creating updated article: {$e->getMessage()}");
        }
    }

    /**
     * Generate enhanced content for an article
     */
    private function generateEnhancedContent(Article $originalArticle): string
    {
        $originalContent = $originalArticle->full_content ?? $originalArticle->content ?? '';
        
        $enhancement = "\n\n---\n\n## Enhanced Analysis\n\n";
        $enhancement .= "This article has been enhanced with additional insights and comprehensive analysis. ";
        $enhancement .= "The enhanced version provides deeper context, practical applications, and expert perspectives ";
        $enhancement .= "to help readers make more informed decisions.\n\n";
        
        $enhancement .= "### Key Enhancements:\n\n";
        $enhancement .= "1. **Expanded Context**: Additional background information and industry trends\n";
        $enhancement .= "2. **Practical Applications**: Real-world examples and use cases\n";
        $enhancement .= "3. **Expert Insights**: Analysis from healthcare professionals and industry experts\n";
        $enhancement .= "4. **Actionable Takeaways**: Clear next steps and recommendations\n\n";
        
        $enhancement .= "### Why This Matters\n\n";
        $enhancement .= "In today's rapidly evolving healthcare landscape, staying informed about the latest ";
        $enhancement .= "developments in AI and digital health solutions is crucial. This enhanced version ";
        $enhancement .= "provides a comprehensive view that goes beyond the original content, offering ";
        $enhancement .= "readers a more complete understanding of the topic.\n\n";
        
        $enhancement .= "### References\n\n";
        $enhancement .= "This enhanced article draws from multiple authoritative sources and industry research ";
        $enhancement .= "to provide a well-rounded perspective. See the references section below for more information.\n";

        return $originalContent . $enhancement;
    }

    /**
     * Create sample articles if JSON file is not found
     */
    private function createSampleArticles(): void
    {
        $sampleArticles = [
            [
                'title' => 'Welcome to BeyondChat Articles',
                'content' => 'This is a sample article to get you started.',
                'full_content' => 'This is a sample article to get you started. You can import your scraped articles using the import script or by running the scraper.',
                'link' => 'https://beyondchats.com/blogs/',
                'date' => now(),
                'source_url' => 'https://beyondchats.com/blogs/',
                'is_updated' => false,
            ],
            [
                'title' => 'Understanding AI Chatbots in Healthcare',
                'content' => 'AI chatbots are transforming how healthcare providers interact with patients...',
                'full_content' => 'AI chatbots are transforming how healthcare providers interact with patients. This comprehensive guide explores the benefits, challenges, and best practices for implementing AI chatbots in healthcare settings.',
                'link' => 'https://beyondchats.com/blogs/',
                'date' => now()->subDays(1),
                'source_url' => 'https://beyondchats.com/blogs/',
                'is_updated' => false,
            ],
            [
                'title' => 'Digital Transformation in Modern Clinics',
                'content' => 'Modern clinics are embracing digital transformation to improve patient care...',
                'full_content' => 'Modern clinics are embracing digital transformation to improve patient care and operational efficiency. Learn about the latest trends and technologies shaping the future of healthcare.',
                'link' => 'https://beyondchats.com/blogs/',
                'date' => now()->subDays(2),
                'source_url' => 'https://beyondchats.com/blogs/',
                'is_updated' => false,
            ],
            [
                'title' => 'Patient Engagement Strategies for Healthcare Providers',
                'content' => 'Effective patient engagement is crucial for improving health outcomes...',
                'full_content' => 'Effective patient engagement is crucial for improving health outcomes. Discover proven strategies and tools that healthcare providers can use to better connect with their patients.',
                'link' => 'https://beyondchats.com/blogs/',
                'date' => now()->subDays(3),
                'source_url' => 'https://beyondchats.com/blogs/',
                'is_updated' => false,
            ],
            [
                'title' => 'The Future of Telemedicine and Remote Care',
                'content' => 'Telemedicine has revolutionized healthcare delivery, especially in recent years...',
                'full_content' => 'Telemedicine has revolutionized healthcare delivery, especially in recent years. Explore how remote care is shaping the future of healthcare and what it means for patients and providers.',
                'link' => 'https://beyondchats.com/blogs/',
                'date' => now()->subDays(4),
                'source_url' => 'https://beyondchats.com/blogs/',
                'is_updated' => false,
            ],
        ];

        foreach ($sampleArticles as $article) {
            Article::firstOrCreate(
                ['title' => $article['title']],
                $article
            );
        }

        $this->command->info('Created 5 sample articles.');
        
        // Create an updated article
        $this->createUpdatedArticle();
    }
}

