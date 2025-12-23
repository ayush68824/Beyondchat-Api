# Laravel Articles API

Laravel API for managing articles scraped from BeyondChats and their updated versions.

## Setup

1. Install dependencies:
```bash
composer install
```

2. Copy `.env.example` to `.env` and configure your database:
```bash
cp .env.example .env
php artisan key:generate
```

3. Update `.env` with your database credentials:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beyondchat_articles
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

4. Run migrations:
```bash
php artisan migrate
```

5. Start the server:
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## API Endpoints

### Get all articles
```
GET /api/articles
Query parameters:
  - is_updated: boolean (filter by updated status)
  - original_article_id: integer (filter by original article)
  - with_versions: boolean (include updated versions)
  - per_page: integer (pagination, default: 15)
```

### Get latest article
```
GET /api/articles/latest
```

### Get article by ID
```
GET /api/articles/{id}
```

### Create article
```
POST /api/articles
Body:
{
  "title": "Article Title",
  "content": "Short content/excerpt",
  "full_content": "Full article content",
  "link": "https://beyondchats.com/article-url",
  "date": "2024-01-01",
  "source_url": "https://beyondchats.com/blogs/",
  "is_updated": false,
  "original_article_id": null,
  "reference_articles": []
}
```

### Update article
```
PUT /api/articles/{id}
PATCH /api/articles/{id}
```

### Delete article
```
DELETE /api/articles/{id}
```

## Importing Scraped Articles

After running the Python scraper, you can import articles using:

```bash
php artisan tinker
```

Then:
```php
$articles = json_decode(file_get_contents('../scraper/scraped_articles.json'), true);
foreach ($articles as $article) {
    \App\Models\Article::create([
        'title' => $article['title'],
        'content' => $article['content'] ?? '',
        'full_content' => $article['full_content'] ?? '',
        'link' => $article['link'] ?? '',
        'date' => $article['date'] ?? now(),
        'source_url' => $article['source_url'] ?? '',
        'is_updated' => false
    ]);
}
```

