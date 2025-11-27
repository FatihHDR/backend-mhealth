<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AboutUsResource;

class AboutUsController extends Controller
{
    protected function readCsvAssoc(string $filename): array
    {
        $file = base_path('csv-files/'.$filename);
        if (!is_readable($file)) return [];
        if (($h = fopen($file, 'r')) === false) return [];
        $headers = fgetcsv($h) ?: [];
        $rows = [];
        while (($row = fgetcsv($h)) !== false) {
            $assoc = [];
            foreach ($headers as $i => $col) {
                $key = is_string($col) ? trim($col) : (string)$i;
                $assoc[$key] = $row[$i] ?? null;
            }
            $rows[] = $assoc;
        }
        fclose($h);
        return $rows;
    }

    public function index()
    {
        $rows = $this->readCsvAssoc('about_us.csv');
        return AboutUsResource::collection($rows);
    }
}
