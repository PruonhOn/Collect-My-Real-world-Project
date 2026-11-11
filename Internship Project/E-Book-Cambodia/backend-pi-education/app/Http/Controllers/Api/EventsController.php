<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventsController extends Controller
{
    /**
     * Public feed of published events, featured events first.
     */
    public function index(): JsonResponse
    {
        $events = Event::query()
            ->withCount('registrations')
            ->published()
            ->orderByDesc('is_featured')
            ->orderBy('start_date')
            ->get();

        $events->each(function (Event $event) {
            $event->offsetSet('spots_left', $event->spotsLeft());
        });

        return response()->json(['events' => $events]);
    }

    /**
     * Public event detail. Only published events are visible.
     */
    public function show(Request $request, Event $event): JsonResponse
    {
        if ($event->status !== Event::STATUS_PUBLISHED) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        $event->loadCount('registrations');
        $event->offsetSet('spots_left', $event->spotsLeft());
        $user = $request->user() ?? Auth::guard('sanctum')->user();
        $event->offsetSet('is_registered', $user
            ? $event->registrations()->where('user_id', $user->id)->exists()
            : false);

        return response()->json(['event' => $event]);
    }

    /**
     * Register the authenticated member for an event.
     */
    public function register(Request $request, Event $event): JsonResponse
    {
        if ($event->status !== Event::STATUS_PUBLISHED) {
            return response()->json(['message' => 'This event is not open for registration.'], 422);
        }

        $alreadyRegistered = $event->registrations()->where('user_id', $request->user()->id)->exists();

        if ($alreadyRegistered) {
            return response()->json(['message' => 'You have already registered for this event.'], 409);
        }

        if ($event->isFull()) {
            return response()->json(['message' => 'This event is full. Registration is closed.'], 422);
        }

        $registration = EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $request->user()->id,
            'status' => EventRegistration::STATUS_REGISTERED,
        ]);

        return response()->json(['registration' => $registration], 201);
    }
}
