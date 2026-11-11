<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Mail\ContactReplyMail;
use App\Models\ContactMessage;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminContactController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = ContactMessage::query();

        if ($request->has('status')) {
            if ($request->status === 'pending') {
                $query->whereNull('replied_at');
            } elseif ($request->status === 'replied') {
                $query->whereNotNull('replied_at');
            }
        }

        $messages = $query->latest()->paginate($request->per_page ?? 15);

        return $this->success($messages);
    }

    public function show(int $id)
    {
        $message = ContactMessage::find($id);

        if (! $message) {
            return $this->error('Message not found', 404);
        }

        return $this->success($message);
    }

    public function markReplied(Request $request, int $id)
    {
        $message = ContactMessage::find($id);

        if (! $message) {
            return $this->error('Message not found', 404);
        }

        $message->update(['replied_at' => now()]);

        return $this->success($message, 'Message marked as replied');
    }

    public function reply(Request $request, int $id)
    {
        $message = ContactMessage::find($id);

        if (! $message) {
            return $this->error('Message not found', 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'admin_reply' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $message->update([
            'admin_reply' => $request->admin_reply,
            'replied_at' => now(),
        ]);

        $user = $message->user_id
            ? User::find($message->user_id)
            : User::where('email', $message->email)->first();

        if ($user && ! $message->user_id) {
            $message->update(['user_id' => $user->id]);
        }

        try {
            Mail::to($message->email)->send(new ContactReplyMail(
                userName: $message->name,
                subjectText: $message->subject ?: 'Your message',
                originalMessage: $message->message,
                reply: $request->admin_reply,
            ));
        } catch (\Throwable $e) {
            // email failure shouldn't block the reply from being saved
        }

        if ($user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'contact_reply',
                'title' => 'Reply to your message',
                'message' => $request->admin_reply,
            ]);
        }

        return $this->success($message, 'Reply sent successfully');
    }

    public function destroy(Request $request, int $id)
    {
        $message = ContactMessage::find($id);

        if (! $message) {
            return $this->error('Message not found', 404);
        }

        $message->delete();

        return $this->success(null, 'Message deleted successfully');
    }
}