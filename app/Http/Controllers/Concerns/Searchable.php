<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Searchable
{
    /**
     * Apply search filter to query.
     * 
     * Supports:
     * - Case-insensitive search
     * - Handles spaces (with or without %20)
     * - Searches in both en_title and id_title by default
     * 
     * @param Builder $query
     * @param Request $request
     * @param array $searchColumns Columns to search in (default: ['en_title', 'id_title'])
     * @return Builder
     */
    protected function applySearch(Builder $query, Request $request, array $searchColumns = ['en_title', 'id_title']): Builder
    {
        $search = $request->query('search');
        
        if (empty($search)) {
            return $query;
        }

        // Decode URL encoded spaces and trim
        $search = urldecode($search);
        $search = trim($search);
        
        if (empty($search)) {
            return $query;
        }

        // Apply case-insensitive search across all specified columns
        $query->where(function (Builder $q) use ($search, $searchColumns) {
            foreach ($searchColumns as $index => $column) {
                // Use ILIKE for PostgreSQL (case-insensitive)
                // For MySQL, LIKE is case-insensitive by default with utf8_general_ci collation
                if ($index === 0) {
                    $q->whereRaw("LOWER({$column}) LIKE ?", ['%' . strtolower($search) . '%']);
                } else {
                    $q->orWhereRaw("LOWER({$column}) LIKE ?", ['%' . strtolower($search) . '%']);
                }
            }
        });

        return $query;
    }
}
