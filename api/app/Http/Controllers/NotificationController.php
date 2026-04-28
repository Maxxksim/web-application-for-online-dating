<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    public function getUnreadNotifications(Request $request): JsonResponse
    {
        return response()->json(['notifications' => $request->user()->unreadNotifications], Response::HTTP_OK);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
