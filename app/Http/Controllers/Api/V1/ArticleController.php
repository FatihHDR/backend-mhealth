<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::with('author')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($articles);
    }

    public function show($id)
    {
        $article = Article::with('author')->findOrFail($id);

        return response()->json($article);
    }

    public function byAuthor($userId)
    {
        $articles = Article::where('author_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($articles);
    }

    public function published()
    {
        $articles = Article::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('author')
            ->orderBy('published_at', 'desc')
            ->paginate(15);

        return response()->json($articles);
    }
}
