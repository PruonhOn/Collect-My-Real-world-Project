<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BroadcastMessage;
use Illuminate\Http\JsonResponse;

class BroadcastMessageController extends Controller
{
    /**
     * Messages the web page should currently show.
     *
     * Deliberately public: the announcement targets every visitor, including
     * guests who have not signed in yet.
     */
    public function index(): JsonResponse
    {
        $messages = BroadcastMessage::query()
            ->currentlyVisible()
            ->limit((int) request()->input('limit', 10))
            ->get(['id', 'title', 'body', 'type', 'tone', 'link_url', 'link_label']);

        return response()->json(['messages' => $messages]);
    }
}
