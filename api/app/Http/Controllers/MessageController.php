<?php

namespace App\Http\Controllers;

use App\Http\Requests\MessageRequest;
use App\Models\Chat;
use App\Models\User;
use App\Services\ChatService;
use App\Services\MessageService;
use App\Services\SwipeService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MessageController extends Controller
{
    public function __construct(private readonly MessageService $messageService)
    {

    }

    public function getMessages(Chat $chat)
    {
        $messages = $this->messageService->getMessages($chat);

        return response()->json(['messages' => $messages], Response::HTTP_OK);
    }

    public function sendMessage(MessageRequest $request, User $recipient): Response
    {

        $this->messageService->sendMessage($request->user(), $recipient, $request->validated('text'));

        return response()->json(['message' => 'Message has been sent.'], Response::HTTP_CREATED);
    }
}
