<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Message\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserResource;
use App\Models\Message;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends BaseApiController
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $me = $request->user()->getKey();

        $paginator = Message::query()
            ->where(fn ($q) => $q->where('sender_id', $me)->orWhere('recipient_id', $me))
            ->with(['sender', 'recipient'])
            ->whereNull('parent_id')
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json([
            'data' => MessageResource::collection($paginator),
            'meta' => [
                'pagination' => $this->paginationMeta($paginator),
                'conversation_map' => $this->conversationMap($request->user()),
            ],
            'errors' => null,
        ]);
    }

    public function store(SendMessageRequest $request): JsonResponse
    {
        $recipient = User::findOrFail($request->integer('recipient_id'));

        $message = Message::create([
            'sender_id' => $request->user()->getKey(),
            'recipient_id' => $recipient->getKey(),
            'parent_id' => $request->integer('parent_id') ?: null,
            'subject' => $request->string('subject')->toString() ?: null,
            'body' => $request->string('body')->toString(),
        ]);

        $this->audit->record('message.sent', $message, after: $message->toArray());

        return response()->json([
            'data' => new MessageResource($message->load(['sender', 'recipient'])),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function conversation(Request $request, User $user): JsonResponse
    {
        $me = $request->user()->getKey();

        if ($user->getKey() === $me) {
            abort(422, 'You cannot open a conversation with yourself.');
        }

        $messages = Message::query()
            ->where(fn ($q) => $q->where('sender_id', $me)->orWhere('recipient_id', $me))
            ->where(fn ($q) => $q->where('sender_id', $user->getKey())->orWhere('recipient_id', $user->getKey()))
            ->with(['sender', 'recipient'])
            ->orderByDesc('created_at')
            ->paginate(25);

        Message::query()
            ->where('sender_id', $user->getKey())
            ->where('recipient_id', $me)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'data' => MessageResource::collection($messages),
            'meta' => ['pagination' => $this->paginationMeta($messages)],
            'errors' => null,
        ]);
    }

    /**
     * Counterparts the current user has exchanged messages with, with the
     * newest message preview and unread count for each.
     */
    protected function conversationMap(User $user): array
    {
        $me = $user->getKey();

        $messages = Message::query()
            ->where(fn ($q) => $q->where('sender_id', $me)->orWhere('recipient_id', $me))
            ->with(['sender', 'recipient'])
            ->whereNull('parent_id')
            ->get()
            ->sortByDesc('created_at');

        $threads = collect();

        foreach ($messages as $message) {
            $key = $message->sender_id === $me ? $message->recipient_id : $message->sender_id;
            $threads->put($key, $message);
        }

        $result = [];

        foreach ($threads as $userId => $lastMessage) {
            $result[] = [
                'user' => $lastMessage->sender_id === $me
                    ? (new UserResource($lastMessage->recipient))
                    : (new UserResource($lastMessage->sender)),
                'last_message' => new MessageResource($lastMessage),
                'unread_count' => Message::query()
                    ->where('sender_id', $userId)
                    ->where('recipient_id', $me)
                    ->whereNull('read_at')
                    ->count(),
            ];
        }

        return $result;
    }
}
