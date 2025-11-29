<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Models\ErrorLog;

class ErrorLogController extends Controller
{
    use Paginates;
    public function index()
    {
        $query = ErrorLog::orderBy('created_at', 'desc');
        $errors = $this->paginateQuery($query);

        return response()->json($errors);
    }

    public function show($id)
    {
        $error = ErrorLog::findOrFail($id);

        return response()->json($error);
    }

    public function recent()
    {
        $query = ErrorLog::where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc');

        $errors = $this->paginateQuery($query);

        return response()->json($errors);
    }
}
