<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Controllers\Concerns\Searchable;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    use Paginates, Searchable;

    /**
     * Display a listing of articles.
     * 
     * GET /api/v1/articles
     * GET /api/v1/articles?search=keyword (search by title)
     */
    public function index(Request $request)
    {
        $query = Article::with('author')->orderBy('created_at', 'desc');
        $query = $this->applySearch($query, $request);
        $paginator = $this->paginateQuery($query);

        return ArticleResource::collection($paginator);
    }

    public function show($id)
    {
        $article = Article::with('author')->findOrFail($id);
        return new ArticleResource($article);
    }

    /**
     * Display an article by slug.
     * 
     * GET /api/v1/articles/slug/{slug}
     */
    public function showBySlug($slug)
    {
        $article = Article::with('author')->where('slug', $slug)->firstOrFail();
        return new ArticleResource($article);
    }

    /**
     * Create a new Article.
     * 
     * POST /api/v1/articles
     * 
     * Payload:
     * {
     *   "title": "Article Title",                              // Required - will be used for both en_title and id_title
     *   "en_title": "English Title",                           // Optional - override English title
     *   "id_title": "Judul Indonesia",                         // Optional - override Indonesian title
     *   "content": "Article content here...",                  // Required - will be used for both en_content and id_content
     *   "en_content": "English content...",                    // Optional - override English content
     *   "id_content": "Konten Indonesia...",                   // Optional - override Indonesian content
     *   "author": "uuid-of-author",                            // Optional - Author UUID
     *   "category": ["health", "wellness"],                    // Optional - Array of categories
     *   "highlight_image": "https://supabase.../image.jpg",    // Optional - Supabase bucket URL
     *   "status": "draft"                                      // Optional - draft/published
     * }
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required_without_all:en_title,id_title|string|max:255',
            'en_title' => 'nullable|string|max:255',
            'id_title' => 'nullable|string|max:255',
            'content' => 'required_without_all:en_content,id_content|string',
            'en_content' => 'nullable|string',
            'id_content' => 'nullable|string',
            'author' => 'nullable|uuid',
            'category' => 'nullable|array',
            'category.*' => 'string',
            'highlight_image' => 'nullable|url',
            'status' => 'nullable|string|in:draft,published',
        ]);

        $payload = [
            'en_title' => $data['en_title'] ?? $data['title'] ?? '',
            'id_title' => $data['id_title'] ?? $data['title'] ?? '',
            'slug' => SlugHelper::generate($data['en_title'] ?? $data['title'] ?? Str::random(10)),
            'en_content' => $data['en_content'] ?? $data['content'] ?? '',
            'id_content' => $data['id_content'] ?? $data['content'] ?? '',
            'author' => $data['author'] ?? null,
            'category' => $data['category'] ?? [],
            'status' => $data['status'] ?? 'draft',
        ];

        $article = Article::create($payload);

        return (new ArticleResource($article->load('author')))->response()->setStatusCode(201);
    }

    /**
     * Update an Article.
     * 
     * PUT/PATCH /api/v1/articles/{id}
     * 
     * Payload: Same as store, all fields optional
     */
    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'en_title' => 'nullable|string|max:255',
            'id_title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'en_content' => 'nullable|string',
            'id_content' => 'nullable|string',
            'author' => 'nullable|uuid',
            'category' => 'nullable|array',
            'category.*' => 'string',
            'highlight_image' => 'nullable|url',
            'status' => 'nullable|string|in:draft,published',
        ]);

        $payload = [];

        if (isset($data['title'])) {
            $newTitle = $data['en_title'] ?? $data['title'];
            $payload['en_title'] = $newTitle;
            $payload['id_title'] = $data['id_title'] ?? $data['title'];
            // Regenerate slug only if title changed
            $newSlug = SlugHelper::regenerateIfChanged($newTitle, $article->slug, $article->en_title);
            if ($newSlug) {
                $payload['slug'] = $newSlug;
            }
        } else {
            if (isset($data['en_title'])) {
                $payload['en_title'] = $data['en_title'];
                // Regenerate slug only if en_title changed
                $newSlug = SlugHelper::regenerateIfChanged($data['en_title'], $article->slug, $article->en_title);
                if ($newSlug) {
                    $payload['slug'] = $newSlug;
                }
            }
            if (isset($data['id_title'])) {
                $payload['id_title'] = $data['id_title'];
            }
        }

        if (isset($data['content'])) {
            $payload['en_content'] = $data['en_content'] ?? $data['content'];
            $payload['id_content'] = $data['id_content'] ?? $data['content'];
        } else {
            if (isset($data['en_content'])) $payload['en_content'] = $data['en_content'];
            if (isset($data['id_content'])) $payload['id_content'] = $data['id_content'];
        }

        foreach (['author', 'category', 'status'] as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        $article->update($payload);

        return new ArticleResource($article->fresh()->load('author'));
    }

    /**
     * Delete an Article.
     * 
     * DELETE /api/v1/articles/{id}
     */
    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        $article->delete();

        return response()->json(['message' => 'Article deleted successfully']);
    }

    public function byAuthor($userId)
    {
        $query = Article::where('author', $userId)
            ->with('author')
            ->orderBy('created_at', 'desc');

        $paginator = $this->paginateQuery($query);

        return ArticleResource::collection($paginator);
    }

    public function published()
    {
        $query = Article::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('author')
            ->orderBy('published_at', 'desc');

        $paginator = $this->paginateQuery($query);

        return ArticleResource::collection($paginator);
    }
}
