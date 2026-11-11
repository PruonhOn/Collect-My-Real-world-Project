<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth('sanctum')->user();

        $contact = ContactMessage::create([
            'user_id' => $user?->id,
            ...$request->only(['name', 'email', 'subject', 'message']),
        ]);

        return $this->success($contact, 'Message sent successfully', 201);
    }

    public function myMessages(Request $request)
    {
        $user = $request->user();

        $messages = ContactMessage::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhere('email', $user->email);
        })
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->success($messages);
    }
}
