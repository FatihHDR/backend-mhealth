<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::orderBy('start_date', 'desc')->paginate(15);
        return response()->json($events);
    }

    public function show($id)
    {
        $event = Event::findOrFail($id);
        return response()->json($event);
    }

    public function upcoming()
    {
        $events = Event::where('start_date', '>', now())
            ->orderBy('start_date', 'asc')
            ->paginate(15);
        return response()->json($events);
    }

    public function past()
    {
        $events = Event::where('end_date', '<', now())
            ->orderBy('start_date', 'desc')
            ->paginate(15);
        return response()->json($events);
    }
}
