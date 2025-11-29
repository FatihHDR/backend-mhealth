<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait Paginates
{
    /**
     * Read `per_page` from the request and normalize it.
     * Accepts numeric values or the string 'all'.
     */
    protected function resolvePerPage()
    {
        $raw = request()->query('per_page', null);
        if ($raw === null) return null;
        if (is_string($raw) && strtolower($raw) === 'all') return 'all';
        if (is_numeric($raw)) {
            $n = (int)$raw;
            if ($n <= 0) return null;

            // Cap per-page to a reasonable maximum to avoid huge responses.
            // You can change this value if you need larger pages.
            $maxPerPage = 100;
            return $n > $maxPerPage ? $maxPerPage : $n;
        }
        return null;
    }

    /**
     * Apply pagination to a query builder or collection.
     * If `per_page=all` is requested, returns a LengthAwarePaginator containing all items.
     */
    protected function paginateQuery($query)
    {
        $perPage = $this->resolvePerPage();

        $default = 15;
        $page = (int) request()->query('page', 1);
        if ($page < 1) $page = 1;

        if ($query instanceof Collection) {
            if ($perPage === 'all') {
                $total = $query->count();
                $perPageVal = $total > 0 ? $total : 1;
                $items = $query->forPage($page, $perPageVal);

                return new LengthAwarePaginator($items->values(), $total, $perPageVal, $page, [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]);
            }

            $perPageVal = $perPage ?: $default;
            $total = $query->count();
            $items = $query->forPage($page, $perPageVal);

            return new LengthAwarePaginator($items->values(), $total, $perPageVal, $page, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        }

        if ($perPage === 'all') {
            $all = $query->get();
            $total = $all->count();
            $perPageVal = $total > 0 ? $total : 1;
            $items = $all->forPage($page, $perPageVal);

            return new LengthAwarePaginator($items->values(), $total, $perPageVal, $page, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        }

        $perPageVal = $perPage ?: $default;

        return $query->paginate($perPageVal)->appends(request()->query());
    }
}
