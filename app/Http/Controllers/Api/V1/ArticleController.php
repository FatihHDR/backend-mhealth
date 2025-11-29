<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;

class ArticleController extends Controller
{
    public function index()
    {
        // allow clients to control per_page with safe defaults/limits
        $perPage = (int) request()->query('per_page', 15);
        if ($perPage < 1) $perPage = 15;
        $perPage = min($perPage, 200);

        $articles = Article::with('author')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());

        // return a Resource collection so the `data` entries are formatted consistently
        return ArticleResource::collection($articles);
    }

    public function show($id)
    {
        $article = Article::with('author')->findOrFail($id);
        return new ArticleResource($article);
    }

    public function byAuthor($userId)
    {
        $perPage = (int) request()->query('per_page', 15);
        if ($perPage < 1) $perPage = 15;
        $perPage = min($perPage, 200);

        // note: Article uses `author` as the foreign key column
        $articles = Article::where('author', $userId)
            ->with('author')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());

        return ArticleResource::collection($articles);
    }

    public function published()
    {
        $perPage = (int) request()->query('per_page', 15);
        if ($perPage < 1) $perPage = 15;
        $perPage = min($perPage, 200);

        $articles = Article::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('author')
            ->orderBy('published_at', 'desc')
            ->paginate($perPage)
            ->appends(request()->query());

        return ArticleResource::collection($articles);
    }
}
