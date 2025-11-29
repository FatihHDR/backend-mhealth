<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Models\User;

class UserController extends Controller
{
    use Paginates;
    public function index()
    {
        $query = User::orderBy('created_at', 'desc');
        $users = $this->paginateQuery($query);

        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json($user);
    }
}
