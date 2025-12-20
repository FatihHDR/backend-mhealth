<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\SlugHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Http\Controllers\Concerns\Searchable;
use App\Http\Resources\EventResource;
use App\Http\Resources\EventCollection;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    use Paginates, Searchable;

    /**
     * Display a listing of events.
     * 
     * GET /api/v1/events
     * GET /api/v1/events?search=keyword (search by title)
     */
    public function index(Request $request)
    {
        $query = Event::orderBy('start_date', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $query = $this->applySearch($query, $request);
        $events = $this->paginateQuery($query);

        return new EventCollection($events);
    }

    /**
     * Display an event.
     * 
     * GET /api/v1/events/{id}      - by UUID
     * GET /api/v1/events/{slug}    - by slug
     */
    public function show($id)
    {
        // Auto-detect: UUID format or slug
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id) ||
            preg_match('/^[0-9a-f]{32}$/i', $id) ||
            preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', str_replace('-', '', $id))) {
            $event = Event::findOrFail($id);
        } else {
            $event = Event::where('slug', $id)->firstOrFail();
        }
        return new EventResource($event);
    }

    /**
     * Display an event by slug.
     * 
     * GET /api/v1/events/slug/{slug}
     */
    public function showBySlug($slug)
    {
        $event = Event::where('slug', $slug)->firstOrFail();
        return new EventResource($event);
    }

    /**
     * Create a new Event.
     * 
     * POST /api/v1/events
     * 
     * Payload:
     * {
     *   "title": "Event Title",                                    // Required - will be used for both en_title and id_title
     *   "en_title": "English Title",                               // Optional - override English title
     *   "id_title": "Judul Indonesia",                             // Optional - override Indonesian title
     *   "description": "Event description...",                     // Optional - will be used for both en/id description
     *   "en_description": "English description...",                // Optional
     *   "id_description": "Deskripsi Indonesia...",                // Optional
     *   "highlight_image": "https://supabase.../event.jpg",        // Optional - Supabase bucket URL
     *   "reference_image": "https://supabase.../ref.jpg",          // Optional - Supabase bucket URL
     *   "organized_image": "https://supabase.../org.jpg",          // Optional - Organizer logo URL
     *   "organized_by": "Company Name",                            // Optional
     *   "start_date": "2025-01-15T09:00:00",                       // Required - ISO datetime
     *   "end_date": "2025-01-15T17:00:00",                         // Required - ISO datetime
     *   "location_name": "Jakarta Convention Center",              // Optional
     *   "location_map": "https://maps.google.com/...",             // Optional - Google Maps URL
     *   "status": "upcoming"                                       // Optional - upcoming/ongoing/past
     * }
     */
    public function store(StoreEventRequest $request)
    {
        $data = $request->validated();

        $payload = [
            'en_title' => $data['en_title'] ?? $data['title'] ?? '',
            'id_title' => $data['id_title'] ?? $data['title'] ?? '',
            'slug' => SlugHelper::generate($data['en_title'] ?? $data['title'] ?? Str::random(10)),
            'en_description' => $data['en_description'] ?? $data['description'] ?? null,
            'id_description' => $data['id_description'] ?? $data['description'] ?? null,
            'highlight_image' => $data['highlight_image'] ?? null,
            'reference_image' => isset($data['reference_image']) ? (is_array($data['reference_image']) ? $data['reference_image'] : [$data['reference_image']]) : null,
            'organized_image' => $data['organized_image'] ?? null,
            'organized_by' => $data['organized_by'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'location_name' => $data['location_name'] ?? null,
            'location_map' => $data['location_map'] ?? null,
            'location_map' => $data['location_map'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'registration_url' => $data['registration_url'] ?? null,
        ];

        $event = Event::create($payload);

        return (new EventResource($event))
            ->additional(['message' => 'Event created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an Event.
     * 
     * PUT/PATCH /api/v1/events/{id}
     * 
     * Payload: Same as store, all fields optional
     */
    public function update(UpdateEventRequest $request, $id)
    {
        $event = Event::findOrFail($id);

        $data = $request->validated();

        $payload = [];

        if (isset($data['title'])) {
            $newTitle = $data['en_title'] ?? $data['title'];
            $payload['en_title'] = $newTitle;
            $payload['id_title'] = $data['id_title'] ?? $data['title'];
            $newSlug = SlugHelper::regenerateIfChanged($newTitle, $event->slug, $event->en_title);
            if ($newSlug) {
                $payload['slug'] = $newSlug;
            }
        } else {
            if (isset($data['en_title'])) {
                $payload['en_title'] = $data['en_title'];
                $newSlug = SlugHelper::regenerateIfChanged($data['en_title'], $event->slug, $event->en_title);
                if ($newSlug) {
                    $payload['slug'] = $newSlug;
                }
            }
            if (isset($data['id_title'])) $payload['id_title'] = $data['id_title'];
        }

        if (isset($data['description'])) {
            $payload['en_description'] = $data['en_description'] ?? $data['description'];
            $payload['id_description'] = $data['id_description'] ?? $data['description'];
        } else {
            if (isset($data['en_description'])) $payload['en_description'] = $data['en_description'];
            if (isset($data['id_description'])) $payload['id_description'] = $data['id_description'];
        }

        $directFields = ['highlight_image', 'reference_image', 'organized_image', 'organized_by', 
                         'start_date', 'end_date', 'location_name', 'location_map', 'status', 'registration_url'];

        // Normalize reference_image to array if provided as single string
        if (array_key_exists('reference_image', $data)) {
            $payload['reference_image'] = is_array($data['reference_image']) ? $data['reference_image'] : ($data['reference_image'] === null ? null : [$data['reference_image']]);
        }

        foreach ($directFields as $key) {
            if ($key === 'reference_image') continue; // already handled
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        $event->update($payload);

        return (new EventResource($event->fresh()))
            ->additional(['message' => 'Event updated successfully']);
    }

    /**
     * Delete an Event.
     * 
     * DELETE /api/v1/events/{id}
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }

    public function upcoming()
    {
        $query = Event::where('start_date', '>', now())
            ->orderBy('start_date', 'asc');

        $events = $this->paginateQuery($query);

        return new EventCollection($events);
    }

    public function past()
    {
        $query = Event::where('end_date', '<', now())
            ->orderBy('start_date', 'desc');

        $events = $this->paginateQuery($query);

        return new EventCollection($events);
    }
}
