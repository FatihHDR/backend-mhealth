<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ErrorLogController extends Controller
{
    public function index()
    {
        $errors = ErrorLog::orderBy('created_at', 'desc')->paginate(15);
        return response()->json($errors);
    }

    public function show($id)
    {
        $error = ErrorLog::findOrFail($id);
        return response()->json($error);
    }

    public function recent()
    {
        $errors = ErrorLog::where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return response()->json($errors);
    }
}
