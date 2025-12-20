<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Controllers\Concerns\Searchable;
use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthorController extends Controller
{
    use Paginates, Searchable;

    public function index(Request $request)
    {
        $query = Author::orderBy('created_at', 'desc');
        $query = $this->applySearch($query, $request);
        $paginator = $this->paginateQuery($query);

        return AuthorResource::collection($paginator);
    }

    public function store(StoreAuthorRequest $request)
    {
        $data = $request->validated();
        
        if (empty($data['slug'])) {
            $data['slug'] = SlugHelper::generate($data['name']);
        }

        $author = Author::create($data);

        return (new AuthorResource($author))->response()->setStatusCode(201);
    }

    public function show($id)
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            $author = Author::findOrFail($id);
        } else {
            $author = Author::where('slug', $id)->firstOrFail();
        }
        
        return new AuthorResource($author);
    }

    public function update(UpdateAuthorRequest $request, $id)
    {
        $author = Author::findOrFail($id);
        $data = $request->validated();

        if (isset($data['name']) && empty($data['slug'])) {
            $newSlug = SlugHelper::regenerateIfChanged($data['name'], $author->slug, $author->name);
            if ($newSlug) {
                $data['slug'] = $newSlug;
            }
        }

        $author->update($data);

        return new AuthorResource($author);
    }

    public function destroy($id)
    {
        $author = Author::findOrFail($id);
        $author->delete();

        return response()->json(['message' => 'Author deleted successfully']);
    }
}
