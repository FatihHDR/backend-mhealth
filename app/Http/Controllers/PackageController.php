<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::orderBy('created_at', 'desc')->paginate(15);
        return response()->json($packages);
    }

    public function show($id)
    {
        $package = Package::findOrFail($id);
        return response()->json($package);
    }

    public function medical()
    {
        $packages = Package::where('is_medical', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return response()->json($packages);
    }

    public function entertainment()
    {
        $packages = Package::where('is_entertain', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return response()->json($packages);
    }
}
