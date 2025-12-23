import axios from 'axios';
import * as cheerio from 'cheerio';
import puppeteer from 'puppeteer';
import dotenv from 'dotenv';

dotenv.config();

const LARAVEL_API_URL = process.env.LARAVEL_API_URL || 'http://localhost:8000/api';
const LLM_API_KEY = process.env.LLM_API_KEY || process.env.OPENAI_API_KEY;
const LLM_API_URL = process.env.LLM_API_URL || 'https://api.openai.com/v1/chat/completions';
const LLM_MODEL = process.env.LLM_MODEL || 'gpt-3.5-turbo';

async function fetchLatestArticle() {
    try {
        const response = await axios.get(`${LARAVEL_API_URL}/articles/latest`);
        
        if (response.data.success && response.data.data) {
            return response.data.data;
        }
        
        if (response.data.data) {
            return response.data.data;
        }
        
        if (response.data.data && Array.isArray(response.data.data) && response.data.data.length > 0) {
            return response.data.data[0];
        }
        
        const allArticles = await axios.get(`${LARAVEL_API_URL}/articles?is_updated=false&per_page=1`);
        if (allArticles.data.data && allArticles.data.data.length > 0) {
            return allArticles.data.data[0];
        }
        if (allArticles.data && Array.isArray(allArticles.data) && allArticles.data.length > 0) {
            return allArticles.data[0];
        }
        
        throw new Error('No article found');
    } catch (error) {
        console.error('Error fetching latest article:', error.message);
        if (error.response) {
            console.error('API Response:', error.response.status, error.response.data);
        }
        throw error;
    }
}

async function scrapeBeyondChatsArticles(maxResults = 2) {
    try {
        console.log(`Scraping articles from: https://beyondchats.com/blogs/`);
        
        const response = await axios.get('https://beyondchats.com/blogs/', {
            timeout: 30000,
            headers: {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            }
        });
        
        const $ = cheerio.load(response.data);
        const foundLinks = [];
        
        const selectors = [
            'article a[href*="/blogs/"]',
            '.blog-post a',
            '.article a',
            'a[href*="/blogs/"]',
            'h2 a',
            'h3 a'
        ];
        
        for (const selector of selectors) {
            $(selector).each((i, elem) => {
                if (foundLinks.length >= maxResults) return false;
                
                const href = $(elem).attr('href');
                if (href && href.includes('/blogs/')) {
                    let fullUrl = href.startsWith('http') ? href : `https://beyondchats.com${href}`;
                    const title = $(elem).text().trim() || $(elem).find('h2, h3').text().trim();
                    
                    if (fullUrl && !foundLinks.some(link => link.url === fullUrl)) {
                        foundLinks.push({ url: fullUrl, title });
                    }
                }
            });
            
            if (foundLinks.length >= maxResults) break;
        }
        
        console.log(`Found ${foundLinks.length} articles from beyondchats.com`);
        return foundLinks.slice(0, maxResults);
    } catch (error) {
        console.error('Error scraping beyondchats.com:', error.message);
        return [];
    }
}

async function scrapeArticleContent(url) {
    try {
        console.log(`Scraping content from: ${url}`);
        
        const response = await axios.get(url, {
            timeout: 30000,
            headers: {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            }
        });
        
        const $ = cheerio.load(response.data);
        let content = '';
        
        const selectors = [
            'article',
            '.article-content',
            '.post-content',
            '.blog-content',
            'main article',
            '[class*="content"]'
        ];
        
        for (const selector of selectors) {
            const element = $(selector).first();
            if (element.length) {
                content = element.text().trim();
                if (content.length > 500) {
                    break;
                }
            }
        }
        
        if (content.length < 500) {
            content = $('p').map((i, el) => $(el).text()).get().join('\n\n').trim();
        }
        
        const title = $('h1').first().text().trim() || 
                     $('title').text().trim() || 
                     'Untitled';
        
        return {
            url,
            title,
            content: content.substring(0, 5000)
        };
    } catch (error) {
        console.error(`Error scraping ${url}:`, error.message);
        return {
            url,
            title: 'Error loading',
            content: ''
        };
    }
}

async function enhanceArticleWithLLM(originalArticle, referenceArticles) {
    try {
        console.log('Calling LLM API to enhance article...');
        
        const referenceTexts = referenceArticles
            .map(ref => `Title: ${ref.title}\nContent: ${ref.content.substring(0, 2000)}`)
            .join('\n\n---\n\n');
        
        const prompt = `You are an expert content writer. Your task is to update and enhance an article to match the formatting, style, and content quality of top-ranking articles on Google.

Original Article:
Title: ${originalArticle.title}
Content: ${originalArticle.full_content || originalArticle.content}

Reference Articles (top-ranking articles for similar topics):
${referenceTexts}

Please:
1. Update the article's formatting to match the style of the reference articles
2. Improve the content quality, structure, and readability
3. Maintain the core message and information from the original article
4. Use similar heading structures, paragraph lengths, and writing style as the reference articles
5. Make it more engaging and SEO-friendly
6. Ensure the content flows naturally

Return the enhanced article with proper formatting. Include headings, subheadings, and well-structured paragraphs.`;

        const isOllama = LLM_API_URL.includes('localhost:11434') || LLM_API_URL.includes('ollama');
        const isGemini = LLM_API_URL.includes('generativelanguage.googleapis.com') || LLM_API_URL.includes('gemini');
        
        let enhancedContent;
        
        if (isGemini) {
            const modelName = LLM_MODEL || 'gemini-2.0-flash';
            const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/${modelName}:generateContent?key=${LLM_API_KEY}`;
            
            let response;
            try {
                response = await axios.post(
                    apiUrl,
                    {
                        contents: [{
                            parts: [{
                                text: `You are an expert content writer specializing in creating high-quality, well-formatted articles that rank well on search engines.\n\n${prompt}`
                            }]
                        }],
                        generationConfig: {
                            temperature: 0.7,
                            maxOutputTokens: 4000
                        }
                    },
                    {
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    }
                );
            } catch (error) {
                if (error.response && error.response.status === 404) {
                    const apiUrlV1 = `https://generativelanguage.googleapis.com/v1/models/${modelName}:generateContent?key=${LLM_API_KEY}`;
                    response = await axios.post(
                        apiUrlV1,
                        {
                            contents: [{
                                parts: [{
                                    text: `You are an expert content writer specializing in creating high-quality, well-formatted articles that rank well on search engines.\n\n${prompt}`
                                }]
                            }],
                            generationConfig: {
                                temperature: 0.7,
                                maxOutputTokens: 4000
                            }
                        },
                        {
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        }
                    );
                } else {
                    throw error;
                }
            }
            
            enhancedContent = response.data.candidates[0].content.parts[0].text;
        } else if (isOllama) {
            const response = await axios.post(
                LLM_API_URL,
                {
                    model: LLM_MODEL,
                    prompt: `You are an expert content writer specializing in creating high-quality, well-formatted articles that rank well on search engines.\n\n${prompt}`,
                    stream: false
                },
                {
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }
            );
            
            enhancedContent = response.data.response;
        } else {
            const response = await axios.post(
                LLM_API_URL,
                {
                    model: LLM_MODEL,
                    messages: [
                        {
                            role: 'system',
                            content: 'You are an expert content writer specializing in creating high-quality, well-formatted articles that rank well on search engines.'
                        },
                        {
                            role: 'user',
                            content: prompt
                        }
                    ],
                    max_tokens: 4000,
                    temperature: 0.7
                },
                {
                    headers: {
                        'Authorization': `Bearer ${LLM_API_KEY}`,
                        'Content-Type': 'application/json'
                    }
                }
            );
            
            enhancedContent = response.data.choices[0].message.content;
        }
        return enhancedContent;
    } catch (error) {
        console.error('Error calling LLM API:', error.message);
        if (error.response) {
            console.error('Response:', error.response.data);
        }
        throw error;
    }
}

async function publishEnhancedArticle(originalArticle, enhancedContent, referenceArticles) {
    try {
        console.log('Publishing enhanced article...');
        
        const citations = referenceArticles.map(ref => ({
            title: ref.title,
            url: ref.url
        }));
        
        const citationsText = '\n\n---\n\n## References\n\n' +
            citations.map((ref, idx) => 
                `${idx + 1}. [${ref.title}](${ref.url})`
            ).join('\n');
        
        const finalContent = enhancedContent + citationsText;
        
        const articleData = {
            title: originalArticle.title + ' (Enhanced)',
            content: enhancedContent.substring(0, 500),
            full_content: finalContent,
            link: originalArticle.link,
            date: new Date().toISOString(),
            source_url: originalArticle.source_url,
            is_updated: true,
            original_article_id: originalArticle.id,
            reference_articles: citations
        };
        
        const response = await axios.post(
            `${LARAVEL_API_URL}/articles`,
            articleData
        );
        
        if (response.data.success) {
            console.log('Enhanced article published successfully!');
            console.log(`Article ID: ${response.data.data.id}`);
            return response.data.data;
        }
        
        throw new Error('Failed to publish article');
    } catch (error) {
        console.error('Error publishing article:', error.message);
        if (error.response) {
            console.error('Response:', error.response.data);
        }
        throw error;
    }
}

async function main() {
    try {
        console.log('=== Article Enhancement Process Started ===\n');
        
        console.log('Step 1: Fetching latest article from Laravel API...');
        const latestArticle = await fetchLatestArticle();
        console.log(`Found article: "${latestArticle.title}"\n`);
        
        console.log('Step 2: Scraping articles from beyondchats.com/blogs/...');
        const searchLinks = await scrapeBeyondChatsArticles(2);
        
        if (searchLinks.length === 0) {
            console.log('No relevant articles found on beyondchats.com. Exiting...');
            return;
        }
        
        console.log(`Found ${searchLinks.length} reference articles\n`);
        
        console.log('Step 3: Scraping content from reference articles...');
        const referenceArticles = [];
        for (const link of searchLinks) {
            const scraped = await scrapeArticleContent(link.url);
            if (scraped.content) {
                referenceArticles.push(scraped);
            }
        }
        
        if (referenceArticles.length === 0) {
            console.log('No content scraped from reference articles. Exiting...');
            return;
        }
        
        console.log(`Successfully scraped ${referenceArticles.length} reference articles\n`);
        
        console.log('Step 4: Enhancing article using LLM...');
        const enhancedContent = await enhanceArticleWithLLM(latestArticle, referenceArticles);
        console.log('Article enhanced successfully!\n');
        
        console.log('Step 5: Publishing enhanced article...');
        const publishedArticle = await publishEnhancedArticle(latestArticle, enhancedContent, referenceArticles);
        
        console.log('\n=== Article Enhancement Process Completed ===');
        console.log(`Enhanced article ID: ${publishedArticle.id}`);
        console.log(`View at: ${LARAVEL_API_URL.replace('/api', '')}/api/articles/${publishedArticle.id}`);
    } catch (error) {
        console.error('\n=== Error ===');
        console.error(error.message);
        process.exit(1);
    }
}

main();
