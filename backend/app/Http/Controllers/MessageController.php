<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\UserTyping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
            'username' => 'required|string|max:100',
        ]);

        event(new MessageSent(
            message: $validated['message'],
            username: $validated['username'],
        ));

        return response()->json(['status' => 'Message sent!']);
    }

    public function typing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:100',
        ]);

        event(new UserTyping(
            username: $validated['username'],
        ));

        return response()->json(['status' => 'Typing event sent!']);
    }
}
