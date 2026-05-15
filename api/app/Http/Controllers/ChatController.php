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
    public function __construct(private readonly ChatService $chatService, private readonly MatchService $matchService)
    {

    }

    public function getChats(Request $request): JsonResponse
    {
        $chats = $this->chatService->getUserChats($request->user());

        return response()->json(['chats' => $chats], Response::HTTP_OK);
    }

    public function firstOrCreate(Request $request, User $recipient): JsonResponse
    {
        if ($request->user()->is($recipient)) {
            return response()->json(['message' => 'You cannot create chat with yourself.'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->matchService->haveMatch($request->user()->id, $recipient->id)) {
            return response()->json(['message' => 'You cannot create chat without matching.'], Response::HTTP_FORBIDDEN);
        }

        $chat = $this->chatService->firstOrCreate($request->user(), $recipient);

        return response()->json(['chat' => $chat['chat']],
            $chat['isExisted'] ? Response::HTTP_OK : Response::HTTP_CREATED
        );
    }
}
