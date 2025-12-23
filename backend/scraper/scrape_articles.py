import requests
from bs4 import BeautifulSoup
import json
import time

def get_last_page_number(base_url):
    try:
        response = requests.get(base_url, timeout=10)
        response.raise_for_status()
        soup = BeautifulSoup(response.content, 'html.parser')
        
        pagination = soup.find('nav', class_='pagination') or soup.find('div', class_='pagination')
        if pagination:
            page_links = pagination.find_all('a')
            max_page = 1
            for link in page_links:
                try:
                    page_num = int(link.text.strip())
                    max_page = max(max_page, page_num)
                except ValueError:
                    pass
            return max_page
        
        return 1
    except Exception as e:
        print(f"Error finding last page: {e}")
        return 1

def scrape_articles_from_page(url):
    try:
        response = requests.get(url, timeout=10)
        response.raise_for_status()
        soup = BeautifulSoup(response.content, 'html.parser')
        
        articles = []
        
        article_selectors = [
            'article',
            '.blog-post',
            '.article',
            '.post',
            '[class*="blog"]',
            '[class*="article"]'
        ]
        
        article_elements = []
        for selector in article_selectors:
            elements = soup.select(selector)
            if elements:
                article_elements = elements
                break
        
        if not article_elements:
            article_elements = soup.find_all('div', class_=lambda x: x and ('blog' in x.lower() or 'article' in x.lower() or 'post' in x.lower()))
        
        for element in article_elements:
            try:
                title_elem = element.find('h1') or element.find('h2') or element.find('h3') or element.find('a', class_=lambda x: x and 'title' in x.lower())
                title = title_elem.get_text(strip=True) if title_elem else "No Title"
                
                link_elem = element.find('a', href=True)
                link = link_elem['href'] if link_elem else ""
                if link and not link.startswith('http'):
                    link = f"https://beyondchats.com{link}"
                
                content_elem = element.find('p') or element.find('div', class_=lambda x: x and ('content' in x.lower() or 'excerpt' in x.lower() or 'summary' in x.lower()))
                content = content_elem.get_text(strip=True) if content_elem else ""
                
                date_elem = element.find('time') or element.find('span', class_=lambda x: x and 'date' in x.lower())
                date = date_elem.get('datetime') or (date_elem.get_text(strip=True) if date_elem else "")
                
                if title and title != "No Title":
                    articles.append({
                        'title': title,
                        'link': link,
                        'content': content,
                        'date': date,
                        'source_url': url
                    })
            except Exception as e:
                print(f"Error parsing article element: {e}")
                continue
        
        return articles
    except Exception as e:
        print(f"Error scraping page {url}: {e}")
        return []

def scrape_full_article_content(article_url):
    try:
        response = requests.get(article_url, timeout=10)
        response.raise_for_status()
        soup = BeautifulSoup(response.content, 'html.parser')
        
        content_selectors = [
            'article',
            '.article-content',
            '.post-content',
            '.blog-content',
            'main',
            '[class*="content"]'
        ]
        
        content = ""
        for selector in content_selectors:
            elem = soup.select_one(selector)
            if elem:
                for script in elem(["script", "style"]):
                    script.decompose()
                content = elem.get_text(separator='\n', strip=True)
                if len(content) > 100:
                    break
        
        return content
    except Exception as e:
        print(f"Error scraping full content from {article_url}: {e}")
        return ""

def main():
    base_url = "https://beyondchats.com/blogs/"
    
    print("Finding last page...")
    last_page = get_last_page_number(base_url)
    print(f"Last page number: {last_page}")
    
    if last_page > 1:
        last_page_url = f"{base_url}?page={last_page}" if '?' not in base_url else f"{base_url}&page={last_page}"
    else:
        last_page_url = base_url
    
    print(f"Scraping articles from: {last_page_url}")
    articles = scrape_articles_from_page(last_page_url)
    
    if len(articles) < 5:
        for page in range(last_page, max(1, last_page - 2), -1):
            page_url = f"{base_url}?page={page}" if '?' not in base_url else f"{base_url}&page={page}"
            page_articles = scrape_articles_from_page(page_url)
            articles.extend(page_articles)
            if len(articles) >= 5:
                break
    
    print(f"Found {len(articles)} articles. Fetching full content...")
    for article in articles[:5]:
        if article['link']:
            print(f"Fetching content for: {article['title']}")
            full_content = scrape_full_article_content(article['link'])
            if full_content:
                article['full_content'] = full_content
            time.sleep(1)
    
    oldest_articles = articles[:5]
    
    output_file = 'scraped_articles.json'
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(oldest_articles, f, indent=2, ensure_ascii=False)
    
    print(f"\nScraped {len(oldest_articles)} articles. Saved to {output_file}")
    for i, article in enumerate(oldest_articles, 1):
        print(f"{i}. {article['title']}")
    
    return oldest_articles

if __name__ == "__main__":
    main()
