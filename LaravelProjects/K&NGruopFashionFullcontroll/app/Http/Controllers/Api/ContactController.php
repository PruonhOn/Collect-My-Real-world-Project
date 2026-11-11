<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string',
        ]);
        return ContactMessage::create($data);
    }

    public function index()
    {
        return ContactMessage::orderBy('created_at', 'desc')->paginate(20);
    }

    public function show(ContactMessage $contactMessage)
    {
        $contactMessage->update(['read' => true]);
        return $contactMessage;
    }

    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();
        return response()->json(['message' => 'Message deleted']);
    }
}
