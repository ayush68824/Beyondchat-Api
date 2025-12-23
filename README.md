# BeyondChat Articles Management System

A full-stack application for scraping, enhancing, and displaying articles from BeyondChats. This project consists of three main components working together to create an intelligent article management system.

## 📋 Table of Contents

- [Overview](#overview)
- [Project Structure](#project-structure)
- [Architecture & Data Flow](#architecture--data-flow)
- [Prerequisites](#prerequisites)
- [Local Setup Instructions](#local-setup-instructions)
  - [Phase 1: Python Scraper](#phase-1-python-scraper)
  - [Phase 1: Laravel API](#phase-1-laravel-api)
  - [Phase 2: NodeJS Enhancement Script](#phase-2-nodejs-enhancement-script)
  - [Phase 3: React Frontend](#phase-3-react-frontend)
- [Complete Workflow](#complete-workflow)
- [API Endpoints](#api-endpoints)
- [Live Demo](#live-demo)
- [Technology Stack](#technology-stack)
- [Troubleshooting](#troubleshooting)
- [Notes](#notes)

## Overview

This project helps you collect articles from BeyondChats, store them in a database, improve them using AI, then show both the original and improved versions on a website.

I've split the work into three parts:
1. **Scraping & Storage**: A Python script grabs articles from the website, then a Laravel API saves them to a database
2. **Enhancement**: A NodeJS script takes those articles, searches Google for related content, then uses an AI model to make them better
3. **Display**: A React website where you can browse all articles, filter them, and see the differences between original and enhanced versions

## Project Structure

```
Beyondchat/
├── backend/
│   ├── scraper/              # Phase 1: Python web scraper
│   │   ├── scrape_articles.py
│   │   ├── requirements.txt
│   │   └── scraped_articles.json
│   ├── laravel-api/          # Phase 1: Laravel REST API
│   │   ├── app/
│   │   ├── database/
│   │   ├── routes/
│   │   └── ...
│   └── nodejs-script/        # Phase 2: Article enhancement script
│       ├── index.js
│       ├── package.json
│       └── env-template.txt
├── frontend/
│   └── react-app/            # Phase 3: React frontend
│       ├── src/
│       ├── public/
│       └── package.json
├── netlify.toml              # Frontend deployment config
├── render.yaml               # Backend deployment config
└── README.md
```

## Architecture & Data Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                         BEYONDCHATS.COM                          │
│                    (Source Website - Blogs)                      │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             │ HTTP Request
                             ▼
                    ┌────────────────────┐
                    │  Python Scraper    │
                    │  (BeautifulSoup)   │
                    └─────────┬──────────┘
                              │
                              │ JSON Output
                              ▼
                    ┌────────────────────┐
                    │ scraped_articles   │
                    │      .json         │
                    └─────────┬──────────┘
                              │
                              │ Import via API
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    LARAVEL API (Backend)                         │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Database: SQLite/MySQL                                   │  │
│  │  - Articles Table                                          │  │
│  │  - Stores: title, content, full_content, link, date, etc. │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                  │
│  REST API Endpoints:                                            │
│  - GET    /api/articles                                         │
│  - GET    /api/articles/latest                                  │
│  - GET    /api/articles/{id}                                    │
│  - POST   /api/articles                                         │
│  - PUT    /api/articles/{id}                                    │
│  - DELETE /api/articles/{id}                                    │
└─────────────┬──────────────────────────────┬────────────────────┘
              │                              │
              │                              │
              │ HTTP API                     │ HTTP API
              │                              │
              ▼                              ▼
┌──────────────────────┐      ┌──────────────────────────────┐
│  NodeJS Enhancer     │      │   React Frontend              │
│  (Phase 2)           │      │   (Phase 3)                   │
│                      │      │                               │
│  1. Fetch latest     │      │  - Article List View          │
│     article          │      │  - Article Detail View        │
│  2. Google Search    │      │  - Filter (All/Original/      │
│  3. Scrape top 2     │      │     Enhanced)                 │
│     results          │      │  - Related Articles           │
│  4. LLM Enhancement  │      │  - Reference Citations        │
│  5. Save enhanced    │      │                               │
│     version          │      │                               │
└──────────────────────┘      └──────────────────────────────┘
              │                              │
              │                              │
              └──────────┬───────────────────┘
                         │
                         │ Enhanced Article
                         │ (with citations)
                         ▼
              ┌──────────────────────┐
              │  Laravel API         │
              │  (Stores enhanced)   │
              └──────────────────────┘
```

**Data Flow Summary:**
1. Python scraper extracts articles from BeyondChats and saves to JSON
2. Articles are imported into Laravel database via API
3. NodeJS script fetches latest article, searches Google, scrapes top results
4. LLM enhances the article with additional context and citations
5. Enhanced article is saved back to Laravel API with reference links
6. React frontend displays both original and enhanced articles
7. Users can filter, view details, and navigate between related articles

## Prerequisites

You'll need these installed on your computer before you can run this project:

- **Python 3.8 or newer** - Get it from [python.org](https://www.python.org/downloads/)
- **Node.js 18 or newer** - Download from [nodejs.org](https://nodejs.org/)
- **PHP 8.1 or newer** - Available at [php.net](https://www.php.net/downloads.php)
- **Composer** - PHP dependency manager from [getcomposer.org](https://getcomposer.org/download/)
- **Chrome or Chromium browser** - The NodeJS script needs this for web scraping
- **OpenAI API Key** - You'll need this for the article enhancement feature. Sign up at [platform.openai.com](https://platform.openai.com)
- **Git** - For getting the code from the repository

## Local Setup Instructions

### Phase 1: Python Scraper

First, we need to get articles from the BeyondChats website. The Python scraper does this.

1. **Go to the scraper folder:**
   ```bash
   cd backend/scraper
   ```

2. **Install the required Python packages:**
   ```bash
   pip install -r requirements.txt
   ```
   
   This will install:
   - `requests` - Makes HTTP requests to websites
   - `beautifulsoup4` - Parses HTML so we can extract data
   - `lxml` - Helps with parsing

3. **Run the scraper:**
   ```bash
   python scrape_articles.py
   ```

   What happens:
   - It finds the last page of blog posts
   - Gets the 5 oldest articles from that page
   - Pulls out the title, content, link, and date for each
   - Downloads the full article text
   - Saves everything to `scraped_articles.json`

4. **Check if it worked:**
   Open `scraped_articles.json` and make sure it has article data in it.

### Phase 1: Laravel API

Now we need to set up the API that stores and serves the articles. Laravel handles this.

1. **Go to the Laravel folder:**
   ```bash
   cd backend/laravel-api
   ```

2. **Install PHP packages:**
   ```bash
   composer install
   ```
   This might take a minute the first time.

3. **Set up the environment file:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure the database in `.env`:**
   
   I recommend SQLite for simplicity (no database server needed):
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=/absolute/path/to/database.sqlite
   ```
   
   Or use MySQL if you prefer:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=beyondchat_articles
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Create the database file (SQLite only):**
   ```bash
   touch database/database.sqlite
   ```
   
   Then in `.env`, put the full path:
   ```env
   DB_DATABASE=/absolute/path/to/backend/laravel-api/database/database.sqlite
   ```

6. **Create the database tables:**
   ```bash
   php artisan migrate
   ```
   
   This makes an `articles` table with columns for:
   - id, title, content, full_content
   - link, date, source_url
   - is_updated, original_article_id
   - reference_articles (stores JSON)
   - created_at, updated_at

7. **Import the articles you scraped:**
   
   Easy way - use the import script:
   ```bash
   php import_articles.php
   ```
   
   Or manually with tinker:
   ```bash
   php artisan tinker
   ```
   Then paste this:
   ```php
   $articles = json_decode(file_get_contents('../scraper/scraped_articles.json'), true);
   foreach ($articles as $article) {
       \App\Models\Article::create([
           'title' => $article['title'],
           'content' => $article['content'] ?? '',
           'full_content' => $article['full_content'] ?? $article['content'] ?? '',
           'link' => $article['link'] ?? '',
           'date' => $article['date'] ?? now(),
           'source_url' => $article['source_url'] ?? '',
           'is_updated' => false
       ]);
   }
   ```

8. **Start the API server:**
   ```bash
   php artisan serve
   ```
   
   You should see: "Server started on http://localhost:8000"
   Try opening `http://localhost:8000/api/articles` in your browser to test it.

9. **Fix CORS if needed:**
   
   If the React app can't connect, edit `config/cors.php`:
   ```php
   'allowed_origins' => ['http://localhost:3000'],
   ```

### Phase 2: NodeJS Enhancement Script

This is the cool part - the script that makes articles better using Google search and AI.

1. **Go to the NodeJS script folder:**
   ```bash
   cd backend/nodejs-script
   ```

2. **Install the packages:**
   ```bash
   npm install
   ```
   
   This gets you:
   - `axios` - For making API calls
   - `puppeteer` - Controls a browser to search Google
   - `cheerio` - Parses HTML on the server
   - `dotenv` - Loads environment variables

3. **Make the `.env` file:**
   ```bash
   cp env-template.txt .env
   ```

4. **Add your settings to `.env`:**
   ```env
   LARAVEL_API_URL=http://localhost:8000/api
   LLM_API_KEY=sk-your-openai-api-key-here
   LLM_API_URL=https://api.openai.com/v1/chat/completions
   LLM_MODEL=gpt-4
   ```
   
   **To get an OpenAI API key:**
   - Go to https://platform.openai.com/api-keys
   - Sign up or log in
   - Click "Create new secret key"
   - Copy it and paste into the `.env` file

5. **Make sure the Laravel API is running:**
   ```bash
   curl http://localhost:8000/api/health
   ```
   
   You should see: `{"status":"ok"}`

6. **Run the enhancement:**
   ```bash
   npm start
   ```
   
   Here's what it does:
   1. Gets the newest article from your Laravel API
   2. Searches Google for that article's title
   3. Scrapes the top 2 search results
   4. Sends everything to the AI to improve the article
   5. Saves the improved version back to the API with citations
   6. Links it to the original article

7. **See the results:**
   Go to `http://localhost:8000/api/articles?is_updated=true` to view enhanced articles.

**Heads up:** The first time you run this, Puppeteer will download Chromium (about 170MB). It only happens once.

### Phase 3: React Frontend

Finally, the website where you can actually see everything. The React app shows all your articles in a nice interface.

1. **Go to the React app folder:**
   ```bash
   cd frontend/react-app
   ```

2. **Install the packages:**
   ```bash
   npm install
   ```
   
   This installs React and everything it needs:
   - `react` & `react-dom` - The React framework
   - `react-router-dom` - For page navigation
   - `axios` - To call the API
   - `react-scripts` - Build tools

3. **Set up the API URL (optional):**
   ```bash
   echo REACT_APP_API_URL=http://localhost:8000/api > .env
   ```
   
   If you skip this, it defaults to `http://localhost:8000/api` anyway.

4. **Start the development server:**
   ```bash
   npm start
   ```
   
   Your browser should open to `http://localhost:3000` automatically.

5. **Build for production:**
   ```bash
   npm run build
   ```
   
   This creates an optimized version in the `build/` folder, ready to deploy.

**What you can do in the frontend:**
- **Browse articles**: See all articles in a grid layout with badges showing if they're original or enhanced
- **Filter**: Click buttons to show all articles, only originals, or only enhanced ones
- **Read details**: Click any article to see the full content
- **Related articles**: When viewing an article, see links to its original or enhanced version
- **Citations**: Enhanced articles show reference links at the bottom
- **Mobile friendly**: Works great on phones and tablets too

## Complete Workflow

Here's how to run everything from scratch:

1. **Get articles from the website:**
   ```bash
   cd backend/scraper
   python scrape_articles.py
   ```

2. **Start the API server:**
   ```bash
   cd backend/laravel-api
   php artisan serve
   ```
   Keep this terminal window open - the server needs to keep running.

3. **Put the scraped articles into the database:**
   ```bash
   php import_articles.php
   ```

4. **Make articles better with AI:**
   ```bash
   cd backend/nodejs-script
   npm start
   ```
   
   You can run this as many times as you want. Each time it enhances the latest article that hasn't been enhanced yet.

5. **Open the website:**
   ```bash
   cd frontend/react-app
   npm start
   ```
   
   Your browser should open to `http://localhost:3000` automatically. If not, just go there manually.

## API Endpoints

### Base URL
- Local: `http://localhost:8000/api`
- Production: `https://beyondchat-api-1.onrender.com/api`

### Endpoints

**Get All Articles**
```
GET /api/articles
Query Parameters:
  - is_updated: boolean (filter by updated status)
  - original_article_id: integer (filter by original article)
  - per_page: integer (default: 15)
```

**Get Latest Article**
```
GET /api/articles/latest
```

**Get Article by ID**
```
GET /api/articles/{id}
```

**Create Article**
```
POST /api/articles
Content-Type: application/json

{
  "title": "Article Title",
  "content": "Short excerpt",
  "full_content": "Full article content",
  "link": "https://beyondchats.com/article-url",
  "date": "2024-01-01",
  "source_url": "https://beyondchats.com/blogs/",
  "is_updated": false,
  "original_article_id": null,
  "reference_articles": []
}
```

**Update Article**
```
PUT /api/articles/{id}
PATCH /api/articles/{id}
```

**Delete Article**
```
DELETE /api/articles/{id}
```

**Health Check**
```
GET /api/health
```

## Live Demo

**Frontend Application:**
🔗 [View Live Website](https://your-netlify-app.netlify.app)

You can see both original and enhanced articles here. The site is connected to the production API, so all the data is real.

**Backend API:**
🔗 [API Endpoint](https://beyondchat-api-1.onrender.com/api)

The API is live and running. You can test these endpoints:
- Health Check: https://beyondchat-api-1.onrender.com/api/health
- All Articles: https://beyondchat-api-1.onrender.com/api/articles
- Latest Article: https://beyondchat-api-1.onrender.com/api/articles/latest

**Note:** Replace the frontend link above with your actual Netlify URL once deployment is done. The frontend automatically uses the production API, so no changes needed there.

## Technology Stack

### Phase 1: Scraping & API
- **Python 3.8+**: Scraping logic
  - BeautifulSoup4: HTML parsing
  - Requests: HTTP client
- **Laravel 10**: REST API framework
  - PHP 8.1+
  - SQLite/MySQL: Database
  - Eloquent ORM: Database abstraction

### Phase 2: Enhancement
- **Node.js 18+**: Runtime
  - Puppeteer: Browser automation for Google search
  - Cheerio: HTML parsing
  - Axios: HTTP client
- **OpenAI GPT-4**: LLM for content enhancement

### Phase 3: Frontend
- **React 18**: UI framework
  - React Router: Navigation
  - Axios: API communication
  - CSS3: Styling

### Deployment
- **Netlify**: Frontend hosting
- **Render**: Backend API hosting

## Troubleshooting

Here are some common issues and how to fix them:

### Python Scraper Issues

**Problem:** `ModuleNotFoundError: No module named 'requests'`
- **Fix:** You need to install the packages first. Run `pip install -r requirements.txt` in the scraper directory.

**Problem:** Scraper returns no articles
- **Fix:** The website might have changed its structure. Open `scrape_articles.py` and check if the HTML selectors need updating. You can inspect the BeyondChats website to see the current structure.

**Problem:** Connection timeout
- **Fix:** Check your internet connection. The script has built-in delays to be nice to the server, so it might take a while.

### Laravel API Issues

**Problem:** `SQLSTATE[HY000] [14] unable to open database file`
- **Fix:** The SQLite file doesn't exist or the path is wrong. Make sure the path in `.env` is the full absolute path. Create the file:
  ```bash
  touch database/database.sqlite
  ```

**Problem:** `Class 'PDO' not found`
- **Fix:** PHP needs the PDO extension. Install it:
  ```bash
  # Ubuntu/Debian
  sudo apt-get install php-sqlite3 php-mysql
  
  # Windows
  # Open php.ini and uncomment: extension=sqlite3
  ```

**Problem:** CORS errors from frontend
- **Fix:** Laravel is blocking requests from your React app. Edit `config/cors.php`:
  ```php
  'allowed_origins' => ['http://localhost:3000', 'https://your-netlify-app.netlify.app'],
  ```

**Problem:** `php artisan migrate` fails
- **Fix:** Double-check your database settings in `.env`. Make sure the database file exists (SQLite) or the MySQL credentials are correct.

### NodeJS Script Issues

**Problem:** `Error: Failed to launch the browser process`
- **Fix:** Puppeteer can't find Chrome. It usually downloads Chromium automatically on first run, but if that fails:
  ```bash
  # Linux
  sudo apt-get install chromium-browser
  
  # macOS
  brew install chromium
  ```

**Problem:** `401 Unauthorized` from OpenAI API
- **Fix:** Your API key is wrong or expired. Check the `.env` file - the key should start with `sk-`. Also make sure you have credits in your OpenAI account.

**Problem:** Script hangs on Google search
- **Fix:** Google might be blocking the automated browser. You could try:
  - Adding longer wait times in the code
  - Using a different approach
  - Running it less frequently

**Problem:** `ECONNREFUSED` when connecting to Laravel API
- **Fix:** The Laravel server isn't running. Start it with `php artisan serve` in the laravel-api folder. Test it:
  ```bash
  curl http://localhost:8000/api/health
  ```

### React Frontend Issues

**Problem:** `Cannot GET /api/articles`
- **Fix:** The API isn't running. Make sure Laravel is started and check that `REACT_APP_API_URL` in `.env` points to the right place.

**Problem:** Blank page or build errors
- **Fix:** Sometimes node_modules gets messed up. Clean it:
  ```bash
  rm -rf node_modules package-lock.json
  npm install
  npm start
  ```

**Problem:** Articles not loading
- **Fix:** Open the browser console (F12) and look for errors. Check if the API URL is correct and CORS is configured properly.

**Problem:** Build fails on Netlify
- **Fix:** 
  - Make sure `package.json` has `"build": "react-scripts build"`
  - Set the Node version in `.nvmrc` or `package.json` engines field
  - Add `REACT_APP_API_URL` to Netlify's environment variables in the dashboard

## Notes

A few things to keep in mind:

- **Database:** I used SQLite because it's simple - no database server to set up. For a real production app, you'd want MySQL or PostgreSQL.

- **API Keys:** Don't commit `.env` files to Git. They have your API keys in them. I've added them to `.gitignore` already.

- **Scraping:** The scraper waits between requests so we don't overload the website. Please don't remove those delays.

- **AI Costs:** GPT-4 costs money per request. Keep an eye on your OpenAI usage. For testing, you could switch to GPT-3.5-turbo in the `.env` file - it's cheaper.

- **Article Linking:** When an article gets enhanced, it's linked back to the original using `original_article_id`. That's how the frontend knows to show "related articles."

- **References:** Enhanced articles save their source URLs in the `reference_articles` field as JSON. The frontend shows these as clickable citations.

- **Deployment:** 
  - Frontend: Deployed on Netlify, builds automatically when you push to Git
  - Backend: Running on Render
  - Make sure to set `REACT_APP_API_URL` in Netlify's environment variables

- **Windows Users:** I included some PowerShell scripts (`setup.ps1`, `start-laravel.ps1`, etc.) to make setup easier on Windows.

---

**Project Status:** ✅ Everything works and is ready to use

**Last Updated:** December 2024

**License:** This is for assignment purposes only.
