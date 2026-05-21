<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ChatService;
use App\Services\MatchService;
use App\Services\SwipeService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chatService)
    {

    }

    public function getChats(Request $request): JsonResponse
    {
        $chats = $this->chatService->getUserChats($request->user());

        return response()->json(['chats' => $chats], Response::HTTP_OK);
    }
}
