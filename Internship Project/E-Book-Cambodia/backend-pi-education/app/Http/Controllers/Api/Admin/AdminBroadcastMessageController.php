<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBroadcastMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $messages = BroadcastMessage::query()
            ->with('author:id,name')
            ->when($request->input('search'), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%");
                });
            })
            ->when($request->input('type'), fn ($query, string $type) => $query->where('type', $type))
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 20))
            ->withQueryString();

        return response()->json(['messages' => $messages]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedData($request, forUpdate: false);
        $validated['created_by'] = $request->user()->id;

        $message = BroadcastMessage::create($validated);

        return response()->json(['message' => $message], 201);
    }

    public function update(Request $request, BroadcastMessage $broadcastMessage): JsonResponse
    {
        $broadcastMessage->update($this->validatedData($request, forUpdate: true));

        return response()->json(['message' => $broadcastMessage->fresh()]);
    }

    public function destroy(BroadcastMessage $broadcastMessage): JsonResponse
    {
        $broadcastMessage->delete();

        return response()->json(['message' => 'Broadcast message deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, bool $forUpdate): array
    {
        $rules = [
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'type' => ['required', 'in:'.implode(',', [BroadcastMessage::TYPE_BANNER, BroadcastMessage::TYPE_POPUP])],
            'tone' => ['required', 'in:'.implode(',', [
                BroadcastMessage::TONE_INFO,
                BroadcastMessage::TONE_SUCCESS,
                BroadcastMessage::TONE_WARNING,
                BroadcastMessage::TONE_DANGER,
            ])],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'link_label' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];

        // Toggles (is_active) are partial updates, so the required fields only
        // apply when the field is actually present in the payload.
        if ($forUpdate) {
            foreach ($rules as $field => $fieldRules) {
                $rules[$field] = ['sometimes', ...$fieldRules];
            }
        }

        return $request->validate($rules);
    }
}
