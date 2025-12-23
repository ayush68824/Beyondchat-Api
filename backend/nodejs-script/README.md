# Article Enhancement Script

NodeJS script that enhances articles by:
1. Fetching the latest article from Laravel API
2. Searching Google for similar articles
3. Scraping content from top-ranking articles
4. Using LLM to enhance the original article
5. Publishing the enhanced article back to Laravel API

## Setup

1. Install dependencies:
```bash
npm install
```

2. Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

3. Configure your `.env` file:
   - Set `LARAVEL_API_URL` to your Laravel API endpoint
   - Set `LLM_API_KEY` to your OpenAI API key (or other LLM provider)
   - Adjust `LLM_API_URL` and `LLM_MODEL` if using a different LLM provider

## Usage

Run the script:
```bash
npm start
```

Or:
```bash
node index.js
```

## Requirements

- Node.js 18+ 
- Laravel API running and accessible
- OpenAI API key (or other LLM provider API key)
- Internet connection for Google search and web scraping

## How it Works

1. **Fetch Latest Article**: Retrieves the most recent article from the Laravel API
2. **Google Search**: Uses Puppeteer to search Google for the article title
3. **Content Scraping**: Extracts content from the top 2 blog/article results
4. **LLM Enhancement**: Sends the original article and reference articles to an LLM to create an enhanced version
5. **Publish**: Posts the enhanced article back to Laravel API with citations

## Notes

- The script uses Puppeteer for Google search, which requires Chrome/Chromium
- Be respectful with web scraping - the script includes delays between requests
- Make sure your LLM API has sufficient credits/quota
- The enhanced articles are marked with `is_updated: true` and linked to the original via `original_article_id`

