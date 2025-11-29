<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Models\Event;

class EventController extends Controller
{
    use Paginates;
    public function index()
    {
        $query = Event::orderBy('start_date', 'desc');
        $events = $this->paginateQuery($query);

        return response()->json($events);
    }

    public function show($id)
    {
        $event = Event::findOrFail($id);

        return response()->json($event);
    }

    public function upcoming()
    {
        $query = Event::where('start_date', '>', now())
            ->orderBy('start_date', 'asc');

        $events = $this->paginateQuery($query);

        return response()->json($events);
    }

    public function past()
    {
        $query = Event::where('end_date', '<', now())
            ->orderBy('start_date', 'desc');

        $events = $this->paginateQuery($query);

        return response()->json($events);
    }
}
