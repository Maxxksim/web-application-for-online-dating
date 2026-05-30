<?php

namespace App\Http\Controllers;

use App\Http\Requests\MessageRequest;
use App\Models\Chat;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MessageController extends Controller
{
    public function __construct(private readonly MessageService $messageService)
    {

    }

    public function getMessages(Chat $chat): JsonResponse
    {
        $messages = $this->messageService->getMessages($chat);

        return response()->json(['messages' => $messages->toResourceCollection()], Response::HTTP_OK);
    }

    public function sendMessage(MessageRequest $request, User $recipient): JsonResponse
    {

        $this->messageService->sendMessage($request->user(), $recipient, $request->validated('text'));

        return response()->json(['message' => 'Message has been sent.'], Response::HTTP_CREATED);
    }

    public function markAsRead(Request $request, Chat $chat): Response
    {
        $this->messageService->markAsRead($chat, $request->user()->id);

        return response()->noContent();
    }
}
