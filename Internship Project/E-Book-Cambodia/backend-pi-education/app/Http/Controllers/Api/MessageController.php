<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewChatMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * Send a message. A member sends to the support team; an admin may also
     * send. The sender is always taken from the authenticated user and the
     * receiver is never trusted from the request.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
            'topic' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $sender = $request->user();

        if (array_key_exists('conversation_id', $validated) && $validated['conversation_id'] !== null) {
            $conversation = Conversation::findOrFail($validated['conversation_id']);
            abort_unless($conversation->involves($sender) || $sender->isAdmin(), 403, 'You are not part of this conversation.');
        } else {
            $conversation = $this->findOrCreateConversation($sender);
        }

        $receiver = $conversation->otherParticipant($sender);
        $subject = $validated['subject'] ?? $this->conversationSubject($conversation) ?? 'Support Request';
        $isAdmin = $sender->isAdmin();

        $message = Message::create([
            'user_id' => $conversation->user_id,
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver?->id,
            'subject' => $subject,
            'topic' => $validated['topic'] ?? null ? $this->contactSubjectLabel($validated['topic']) : null,
            'message' => $validated['message'],
            'from_admin' => $isAdmin,
            'admin_read_at' => $isAdmin ? now() : null,
        ]);

        $conversation->touch();

        $message->load('sender:id,name,email,avatar', 'receiver:id,name,email,avatar', 'conversation');

        $this->announceToAdmin($message, $conversation->admin);

        if ($isAdmin) {
            $this->announceToMember($message);
        }

        return response()->json([
            'message' => 'Message sent.',
            'user_message' => $message,
            'conversation' => $this->conversationPayload($conversation, $sender),
        ], 201);
    }

    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Conversation::with([
            'user:id,name,email,avatar',
            'admin:id,name,email,avatar',
            'messages',
        ]);

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $conversations = $query
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Conversation $conversation) => $this->conversationPayload($conversation, $user));

        return response()->json(['conversations' => $conversations]);
    }

    public function conversationMessages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->involves($user) || $user->isAdmin(), 403, 'You are not part of this conversation.');

        if ($user->isAdmin()) {
            $conversation->messages()
                ->whereNotNull('sender_id')
                ->where('sender_id', '!=', $user->id)
                ->whereNull('admin_read_at')
                ->update(['admin_read_at' => now()]);
        } else {
            $conversation->messages()
                ->where('receiver_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        // Opening the thread answers the bell for whoever is reading it: the
        // admin's "new message" and the member's "support replied" are the same
        // notification type, so one query clears both.
        $user->notifications()
            ->where('type', NewChatMessage::class)
            ->whereNull('read_at')
            ->whereJsonContains('data->conversation_id', $conversation->id)
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with('sender:id,name,email,avatar')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Message $message) => $this->messagePayload($message, $user));

        return response()->json([
            'messages' => $messages,
            'conversation' => $this->conversationPayload($conversation->fresh(), $user),
        ]);
    }

    public function readMessage(Request $request, Message $message): JsonResponse
    {
        $user = $request->user();

        abort_unless($message->receiver_id === $user->id, 403, 'You are not the receiver of this message.');

        $message->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Message marked as read.',
            'user_message' => $message->fresh(),
        ]);
    }

    private function conversationPayload(Conversation $conversation, User $viewer): array
    {
        $other = $conversation->otherParticipant($viewer) ?? $conversation->admin ?? $conversation->user;

        $messages = $conversation->messages;
        $unread = $messages
            ->filter(fn (Message $message) => $viewer->isAdmin()
                ? $message->sender_id !== $viewer->id && $message->admin_read_at === null
                : $message->receiver_id === $viewer->id && $message->read_at === null)
            ->count();

        $last = $messages->sortByDesc('id')->first();

        return [
            'id' => $conversation->id,
            'other_participant' => $other ? [
                'id' => $other->id,
                'name' => $other->name,
                'email' => $other->email,
                'avatar' => $other->avatar,
            ] : null,
            'last_message' => $last ? [
                'id' => $last->id,
                'message' => $last->message,
                'sender_id' => $last->sender_id,
                'created_at' => $last->created_at,
                'from_admin' => $last->from_admin,
            ] : null,
            'unread_count' => $unread,
            'updated_at' => $conversation->updated_at,
        ];
    }

    private function messagePayload(Message $message, User $viewer): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'subject' => $message->subject,
            'topic' => $message->topic,
            'message' => $message->message,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'sender' => $message->sender ? [
                'id' => $message->sender->id,
                'name' => $message->sender->name,
                'avatar' => $message->sender->avatar,
            ] : null,
            'from_admin' => $message->from_admin,
            'is_mine' => $message->sender_id === $viewer->id,
            'read_at' => $message->read_at,
            'admin_read_at' => $message->admin_read_at,
            'created_at' => $message->created_at,
        ];
    }

    private function findOrCreateConversation(User $user): Conversation
    {
        $conversation = Conversation::query()
            ->where('user_id', $user->id)
            ->first();

        if ($conversation === null) {
            $conversation = Conversation::query()->create([
                'user_id' => $user->id,
                'admin_id' => $user->isAdmin() ? $user->id : $this->defaultAdminId(),
            ]);
        }

        if ($conversation->admin_id === null) {
            $conversation->update(['admin_id' => $this->defaultAdminId()]);
        }

        return $conversation;
    }

    private function defaultAdminId(): ?int
    {
        return User::defaultAdmin()?->id;
    }

    /**
     * Announce an inbound message to the admin, so every message the admin
     * still owes an answer surfaces as a notification instead of waiting in an
     * inbox to be noticed.
     *
     * `admin_read_at` is the discriminator: the admin's own traffic (a reply,
     * an outbound send, a record they created) is marked read the moment it is
     * written, so it never announces itself. A member chat, a guest contact
     * form and a partnership enquiry all arrive unread and all announce.
     */
    private function announceToAdmin(Message $message, ?User $admin = null): void
    {
        if ($message->admin_read_at !== null) {
            return;
        }

        ($admin ?? User::defaultAdmin())?->notify(new NewChatMessage($message));
    }

    /**
     * The return leg of announceToAdmin(): an admin's reply is the only message
     * a member ever waits on, so it lights up their bell too. Guests have no
     * account to notify and are skipped.
     */
    private function announceToMember(Message $message): void
    {
        if ($message->user_id === null) {
            return;
        }

        $message->user?->notify(new NewChatMessage($message));
    }

    private function conversationSubject(Conversation $conversation): ?string
    {
        return $conversation->messages()->orderByDesc('id')->value('subject');
    }

    public function contact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = Message::create([
            'user_id' => $request->user()?->id,
            'sender_name' => $validated['name'],
            'sender_email' => $validated['email'],
            'subject' => $this->contactSubjectLabel($validated['subject']),
            'message' => $validated['message'],
        ]);

        $this->announceToAdmin($message);

        return response()->json([
            'message' => 'Message sent.',
            'user_message' => $message->load('user:id,name'),
        ], 201);
    }

    private function contactSubjectLabel(string $key): string
    {
        $labels = [
            'subjectGeneral' => 'General Inquiry',
            'subjectBook' => 'Book Support',
            'subjectAccount' => 'Account Support',
            'subjectPayment' => 'Payment Support',
            'subjectVip' => 'VIP Support',
            'subjectTechnical' => 'Technical Support',
            'subjectPartnership' => 'Partnership / Business',
        ];

        return $labels[$key] ?? $key;
    }

    public function myMessages(Request $request): JsonResponse
    {
        $messages = $request->user()
            ->messages()
            ->latest()
            ->paginate((int) $request->input('per_page', 15))
            ->withQueryString();

        return response()->json([
            'messages' => $messages,
            'unread_count' => $request->user()->unreadMessagesCount(),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $request->user()->unreadMessagesCount(),
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->messages()
            ->whereNull('read_at')
            ->where(function ($query) use ($user) {
                $query->where(function ($query) {
                    $query->whereNotNull('reply')->orWhere('from_admin', true);
                })->orWhere(function ($query) use ($user) {
                    $query->whereNotNull('receiver_id')->where('receiver_id', $user->id);
                });
            })
            ->update(['read_at' => now()]);

        return response()->json([
            'unread_count' => 0,
        ]);
    }

    public function adminStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'program' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $message = Message::create([
            'user_id' => null,
            'sender_name' => $validated['name'],
            'sender_email' => $validated['email'] ?? null,
            'photo' => $request->file('photo')?->store('collaborators', 'public'),
            'subject' => 'Partnership / Business',
            'message' => $this->collaboratorMessage($validated['program'] ?? null, $validated['message'] ?? null),
            'admin_read_at' => now(),
        ]);

        return response()->json([
            'message' => 'Collaborator added.',
            'user_message' => $message,
        ], 201);
    }

    public function adminUpdate(Request $request, Message $message): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'program' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $message->sender_name = $validated['name'];
        $message->sender_email = $validated['email'] ?? null;
        $message->message = $this->collaboratorMessage($validated['program'] ?? null, $validated['message'] ?? null);

        if ($request->hasFile('photo')) {
            $this->deleteCollaboratorPhoto($message);
            $message->photo = $request->file('photo')->store('collaborators', 'public');
        } elseif ($request->boolean('remove_photo')) {
            $this->deleteCollaboratorPhoto($message);
            $message->photo = null;
        }

        $message->save();

        return response()->json([
            'message' => 'Collaborator updated.',
            'user_message' => $message->fresh(),
        ]);
    }

    private function collaboratorMessage(?string $program, ?string $message): string
    {
        $text = trim(($program ? "[{$program}] " : '').trim((string) $message));

        return $text === '' ? $program ?? '' : $text;
    }

    private function deleteCollaboratorPhoto(Message $message): void
    {
        if ($message->photo) {
            Storage::disk('public')->delete($message->photo);
        }
    }

    public function adminUnreadCount(): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->adminInboundMessages()->whereNull('admin_read_at')->count(),
        ]);
    }

    public function adminMarkRead(): JsonResponse
    {
        $this->adminInboundMessages()->whereNull('admin_read_at')->update(['admin_read_at' => now()]);

        $admins = User::query()->where('role', User::ROLE_ADMIN)->pluck('id');

        if ($admins->isNotEmpty()) {
            DB::table('notifications')
                ->whereIn('notifiable_id', $admins)
                ->where('type', NewChatMessage::class)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return response()->json([
            'unread_count' => 0,
        ]);
    }

    private function adminInboundMessages(): Builder
    {
        return Message::query()
            ->where('from_admin', false)
            ->where(function ($query) {
                $query->whereNotNull('sender_name')
                    ->orWhereNotNull('conversation_id')
                    ->orWhereNotNull('user_id');
            });
    }

    public function adminSend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $admin = $request->user();

        $conversation = Conversation::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['admin_id' => $admin->id],
        );

        if ($conversation->admin_id === null) {
            $conversation->update(['admin_id' => $admin->id]);
        }

        $message = Message::create([
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'receiver_id' => $user->id,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'from_admin' => true,
            'admin_read_at' => now(),
        ]);

        $conversation->touch();

        $this->announceToMember($message);

        return response()->json([
            'message' => 'Message sent to user.',
            'user_message' => $message->load('sender:id,name,email,avatar', 'conversation'),
            'conversation' => $this->conversationPayload($conversation->load('messages', 'user:id,name,email,avatar', 'admin:id,name,email,avatar'), $admin),
        ], 201);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $messages = Message::with('user:id,name,email,avatar')
            ->when($request->boolean('collaborators'), function ($query) {
                $query->where('subject', 'Partnership / Business');
            })
            ->when($request->boolean('guests'), function ($query) {
                $query->whereNull('conversation_id')
                    ->whereNull('user_id')
                    ->whereNotNull('sender_name')
                    ->where('subject', '!=', 'Partnership / Business');
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = $request->input('status');
                $query->where(fn ($query) => $status === 'replied'
                    ? $query->whereNotNull('reply')
                    : $query->whereNull('reply')->where('from_admin', false));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($query) use ($search) {
                    $query->where('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhere('sender_name', 'like', "%{$search}%")
                        ->orWhere('sender_email', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate((int) $request->input('per_page', 15))
            ->withQueryString();

        return response()->json($messages);
    }

    public function adminReply(Request $request, Message $message): JsonResponse
    {
        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:5000'],
        ]);

        $message->update([
            'reply' => $validated['reply'],
            'replied_at' => now(),
            'admin_read_at' => now(),
        ]);

        $this->announceToMember($message->fresh()->load('user:id,name'));

        return response()->json([
            'message' => 'Reply sent.',
            'user_message' => $message->fresh()->load('user:id,name'),
        ]);
    }

    public function adminDestroy(Request $request, Message $message): JsonResponse
    {
        $message->delete();

        return response()->json(['message' => 'Message deleted.']);
    }
}
