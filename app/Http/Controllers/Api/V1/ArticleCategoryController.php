<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Requests\StoreArticleCategoryRequest;
use App\Http\Requests\UpdateArticleCategoryRequest;
use App\Http\Resources\ArticleCategoryResource;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleCategoryController extends Controller
{
    use Paginates;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ArticleCategory::query()->orderBy('created_at', 'desc');
        $paginator = $this->paginateQuery($query);

        return ArticleCategoryResource::collection($paginator);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreArticleCategoryRequest $request)
    {
        $data = $request->validated();
        // Generate a short 8-character ID from a UUID to match user's example
        $data['id'] = substr((string) Str::uuid(), 0, 8);
        $category = ArticleCategory::create($data);

        return (new ArticleCategoryResource($category))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $category = ArticleCategory::findOrFail($id);
        return new ArticleCategoryResource($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateArticleCategoryRequest $request, $id)
    {
        $category = ArticleCategory::findOrFail($id);
        $category->update($request->validated());

        return new ArticleCategoryResource($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $category = ArticleCategory::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Article category deleted successfully']);
    }
}
