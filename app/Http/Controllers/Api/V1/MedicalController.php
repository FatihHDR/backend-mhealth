<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Resources\MedicalResource;
use App\Models\Medical;

class MedicalController extends Controller
{
    use Paginates;
    public function index()
    {
        $query = Medical::orderBy('created_at', 'desc');
        $rows = $this->paginateQuery($query);
        return MedicalResource::collection($rows);
    }

    public function show($id)
    {
        $row = Medical::findOrFail($id);
        return new MedicalResource($row);
    }
}
