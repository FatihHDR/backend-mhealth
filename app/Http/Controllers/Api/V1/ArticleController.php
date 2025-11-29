<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Resources\ArticleResource;
use App\Models\Article;

class ArticleController extends Controller
{
    use Paginates;
    public function index()
    {
        $query = Article::with('author')->orderBy('created_at', 'desc');

        $paginator = $this->paginateQuery($query);

        return ArticleResource::collection($paginator);
    }

    public function show($id)
    {
        $article = Article::with('author')->findOrFail($id);
        return new ArticleResource($article);
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
