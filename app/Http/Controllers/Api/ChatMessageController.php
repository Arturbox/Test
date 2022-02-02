<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\RepositoryException;
use App\Http\Requests\Api\Chat\LikeMessageRequest;
use App\Http\Requests\Api\Chat\PinMessageRequest;
use App\Http\Requests\Api\Chat\UpdateMessageRequest;
use App\Http\Requests\Api\Chat\CreateMessageRequest;
use App\Http\Requests\Api\Chat\ListMessageRequest;
use App\Http\Resources\Chat\MessageCollection;
use App\Http\Resources\Chat\MessageResource;
use App\Http\Resources\Chat\UnreadTotalResource;
use App\Models\ChatMessage;
use App\Services\Chat\CastService;
use App\Services\Chat\ChatConversationService;
use App\Services\Chat\ChatMessageService;
use App\Services\NotificationService;
use App\Tools\APIResponse;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Swagger\Annotations as SWG;

/**
 * Class ChatMessageController
 * @package App\Http\Controllers\Api
 */
class ChatMessageController extends Controller
{
    /**
     * @var ChatMessageService $messageService
     */
    private ChatMessageService $messageService;

    /**
     * @var ChatConversationService $conversationService
     */
    private ChatConversationService $conversationService;

    /**
     * @var CastService $castService
     */
    private CastService $castService;

    /**
     * @var NotificationService $notificationService
     */
    private NotificationService $notificationService;

    /**
     * @param ChatMessageService $chatMessageService
     * @param ChatConversationService $chatConversationService
     * @param NotificationService $notificationService
     * @param CastService $castService
     */
    public function __construct(
        ChatMessageService $chatMessageService,
        ChatConversationService $chatConversationService,
        NotificationService $notificationService,
        CastService $castService
    ) {
        $this->castService = $castService;
        $this->messageService = $chatMessageService;
        $this->conversationService = $chatConversationService;
        $this->notificationService = $notificationService;
    }

    /**
     * @SWG\Post(
     *      path="/chat/messages",
     *      operationId="createChatMessage",
     *      tags={"Chat Messages"},
     *      summary="Create new chat message",
     *      description="Create new chat message.",
     *      security={{"Bearer":{}}},
     *      @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="Create chat message params",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="message", type="string"),
     *              @SWG\Property(property="attachments", type="ObjectArray"),
     *              @SWG\Property(property="conversation_id", type="integer"),
     *              @SWG\Property(property="reply_to_id", type="integer"),
     *          )
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="Succesful operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "success",
     *                  "data": {
     *                      "id": 123,
     *                      "message": "message",
     *                      "date": "2021-05-11T07:53:56.000000Z",
     *                      "conversation_id": 22,
     *                      "is_seen": "false",
     *                      "pin": "false",
     *                      "status": "original",
     *                      "edited_message": "null",
     *                      "reply_to": "null",
     *                      "deleted_at": "null",
     *                      "user": {"id": 1233, "name": "name", "avatar": "url"},
     *                      "attachments": {},
     *                  }
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=400,
     *          description="Failed operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "fail",
     *                  "data": {
     *                      "message": {"The message field is required."},
     *                      "conversation_id": {"The conversation id field is required."}
     *                  }
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=401,
     *          description="Unauthorised operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Unauthorised."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=403,
     *          description="Access forbidden operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Current user has no access to provided conversation."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=404,
     *          description="Not found operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Chat conversation with id=1 not found."
     *              }
     *          }
     *      )
     * )
     *
     * @param CreateMessageRequest $request
     * @return JsonResponse
     * @throws RepositoryException|GuzzleException
     */
    public function create(CreateMessageRequest  $request): JsonResponse
    {
        $conversation = $this->conversationService->hasAccess($request);
        $message = $this->messageService->save($request);
        $this->castService->broadcast($message, $conversation);
        $this->notificationService->sendClientUnreadMessageNotification($message, $conversation);
        $this->notificationService->sendMessageNotification($message, $conversation);

        return APIResponse::successResponse(new MessageResource($message));
    }

    /**
     * @SWG\Get(
     *      path="/chat/messages",
     *      operationId="listChatMessages",
     *      tags={"Chat Messages"},
     *      summary="Get chat messages list by conversation",
     *      description="Return chat messages list by conversation for current user",
     *      security={{"Bearer":{}}},
     *     @SWG\Parameter(
     *          name="conversation_id",
     *          in="query",
     *          description="conversation id",
     *          required=true,
     *          type="integer"
     *      ),
     *      @SWG\Parameter(
     *          name="page",
     *          in="query",
     *          description="Pagination page",
     *          required=false,
     *          type="integer"
     *      ),
     *     @SWG\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="Items per page",
     *          required=false,
     *          type="integer"
     *      ),
     *     @SWG\Parameter(
     *          name="search",
     *          in="query",
     *          description="search",
     *          required=false,
     *          type="string"
     *      ),
     *     @SWG\Parameter(
     *          name="is_pin",
     *          in="query",
     *          description="get pin messages only",
     *          required=false,
     *          type="integer"
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="Succesful operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "success",
     *                  "data": {{
     *                      "id": 1,
     *                      "message": "Hello!",
     *                      "date": "2019-11-02T10:22:12+00:00",
     *                      "user": {
     *                          "id": 4,
     *                          "full_name": "Freelancer Name"
     *                      },
     *                      "conversation_id": 1,
     *                      "is_seen": 0
     *                  }},
     *                  "pagination": {
     *                      "total": 2,
     *                      "count": 3,
     *                      "per_page": 1,
     *                      "current_page": 1,
     *                      "total_pages": 2,
     *                  },
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=401,
     *          description="Unauthorised operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Unauthorised."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=403,
     *          description="Access forbidden operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Current user has no access to provided conversation."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=404,
     *          description="Not found operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Chat conversation with id=1 not found."
     *              }
     *          }
     *      )
     * )
     *
     * @param ListMessageRequest $request
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function list(ListMessageRequest $request): JsonResponse
    {
        return APIResponse::collectionResponse(new MessageCollection($this->messageService->messages($request)));
    }

    /**
     * @SWG\Put(
     *      path="/chat/messages/seen/{message_id}",
     *      operationId="markSeenChatMessage",
     *      tags={"Chat Messages"},
     *      summary="Mark seen existing message",
     *      description="Mark seen existing chat message and return empty success response",
     *      security={{"Bearer":{}}},
     *     @SWG\Parameter(
     *          name="message_id",
     *          in="path",
     *          description="Create chat message params",
     *          required=true,
     *          type="string"
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="Succesful operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "success",
     *                  "data": null
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=400,
     *          description="Failed operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "fail",
     *                  "data": {
     *                      "message_id": {"The message id field is required."}
     *                  }
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=401,
     *          description="Unauthorised operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Unauthorised."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=403,
     *          description="Access forbidden operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Access denied. You have no access to message conversation."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=404,
     *          description="Not found operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Chat message with id=1 not found."
     *              }
     *          }
     *      )
     * )
     *
     * @param ChatMessage $message
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function seen(ChatMessage $message): JsonResponse
    {
        $message = $this->messageService->markSeen($message);
        $this->castService->broadcastSeen($message);

        return APIResponse::successResponse();
    }

    /**
     * @SWG\Put(
     *      path="/chat/messages/unseen/{message_id}",
     *      operationId="markUnseenChatMessage",
     *      tags={"Chat Messages"},
     *      summary="Mark seen existing message",
     *      description="Mark unseen existing chat message and return empty success response",
     *      security={{"Bearer":{}}},
     *     @SWG\Parameter(
     *          name="message_id",
     *          in="path",
     *          description="Create chat message params",
     *          required=true,
     *          type="string"
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="Succesful operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "success",
     *                  "data": null
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=400,
     *          description="Failed operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "fail",
     *                  "data": {
     *                      "message_id": {"The message id field is required."}
     *                  }
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=401,
     *          description="Unauthorised operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Unauthorised."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=403,
     *          description="Access forbidden operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Access denied. You have no access to message conversation."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=404,
     *          description="Not found operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Chat message with id=1 not found."
     *              }
     *          }
     *      )
     * )
     *
     * @param ChatMessage $message
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function unseen(ChatMessage $message): JsonResponse
    {
        $message = $this->messageService->markUnseen($message);
        $this->castService->broadcastUnseen($message);

        return APIResponse::successResponse();
    }

    /**
     * @SWG\Put(
     *      path="/chat/messages/like/{message_id}",
     *      operationId="likeChatMessage",
     *      tags={"Chat Messages"},
     *      summary="Like existing message",
     *      description="like existing chat message and return empty success response",
     *      security={{"Bearer":{}}},
     *     @SWG\Parameter(
     *       name="message_id",
     *       description="message id",
     *       required=true,
     *       type="string",
     *       in="path"
     *     ),
     *     @SWG\Parameter(
     *       name="like",
     *       description="like",
     *       required=true,
     *       type="string",
     *       in="query"
     *     ),
     *      @SWG\Response(
     *          response=200,
     *          description="Succesful operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "success",
     *                  "data": null
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=400,
     *          description="Failed operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "fail",
     *                  "data": {
     *                      "message_id": {"The message id field is required."}
     *                  }
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=401,
     *          description="Unauthorised operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Unauthorised."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=403,
     *          description="Access forbidden operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Access denied. You have no access to message conversation."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=404,
     *          description="Not found operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Chat message with id=1 not found."
     *              }
     *          }
     *      )
     * )
     *
     * @param LikeMessageRequest $request
     * @param ChatMessage $message
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function like(ChatMessage $message, LikeMessageRequest $request): JsonResponse
    {;
        $this->castService->broadcastLike($this->messageService->like($message, $request));

        return APIResponse::successResponse();
    }

    /**
     * @SWG\Put(
     *      path="/chat/messages/pin/{message_id}",
     *      operationId="PinChatMessage",
     *      tags={"Chat Messages"},
     *      summary="Pint message",
     *      description="Mark message as pined",
     *      security={{"Bearer":{}}},
     *      @SWG\Parameter(
     *       name="message_id",
     *       description="Message id",
     *       required=true,
     *       type="string",
     *       in="path"
     *     ),
     *     @SWG\Parameter(
     *       name="pin",
     *       description="pin",
     *       required=true,
     *       type="integer",
     *       in="query"
     *     ),
     *      @SWG\Response(
     *          response=200,
     *          description="Succesful operation.",
     *          examples={
     *              "application/json": {
     *                  "message": "Successful request.",
     *                  "status": "success",
     *                  "data": {
     *                      "id": 1,
     *                      "title": "New conversation",
     *                      "pin": "false",
     *                      "unreadCount": "2",
     *                      "withUser": {
     *                          "id": "3",
     *                          "name": "Some Name",
     *                          "countryCode": "AM",
     *                          "image": "url",
     *                          "freelancer_type": "dev"
     *                      },
     *                      "lastMessage": {
     *                          "id": "2!",
     *                          "message": "Hello!",
     *                          "date": "2019-11-02T10:26:55+00:00",
     *                          "from": "You"
     *                      }
     *                  }
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=401,
     *          description="Unauthorised operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Unauthorised."
     *              }
     *          }
     *      )
     * )
     *
     * @param ChatMessage $message
     * @param PinMessageRequest $request
     * @return JsonResponse
     * @throws RepositoryException
     */
    public function pin(ChatMessage $message, PinMessageRequest $request): JsonResponse
    {
        return APIResponse::successResponse(new MessageResource($this->messageService->pin($message, $request)));
    }

    /**
     * @SWG\Put(
     *      path="/chat/messages/update/{message_id}",
     *      operationId="updateChatMessage",
     *      tags={"Chat Messages"},
     *      summary="Update new chat message",
     *      description="Update chat message.",
     *      security={{"Bearer":{}}},
     *     @SWG\Parameter(
     *       name="message_id",
     *       description="Message id",
     *       required=true,
     *       type="string",
     *       in="path"
     *     ),
     *      @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="Create chat message params",
     *          required=false,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="message", type="string"),
     *              @SWG\Property(property="attachments", type="ObjectArray"),
     *              @SWG\Property(property="conversation_id", type="integer"),
     *              @SWG\Property(property="reply_to_id", type="integer"),
     *          )
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="Succesful operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "success",
     *                  "data": {
     *                      "id": 123,
     *                      "message": "message",
     *                      "date": "2021-05-11T07:53:56.000000Z",
     *                      "conversation_id": 22,
     *                      "is_seen": "false",
     *                      "pin": "false",
     *                      "status": "original",
     *                      "edited_message": "null",
     *                      "reply_to": "null",
     *                      "deleted_at": "null",
     *                      "user": {"id": 1233, "name": "name", "avatar": "url"},
     *                      "attachments": {},
     *                  }
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=400,
     *          description="Failed operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "fail",
     *                  "data": {
     *                      "message": {"The message field is required."},
     *                      "conversation_id": {"The conversation id field is required."}
     *                  }
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=401,
     *          description="Unauthorised operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Unauthorised."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=403,
     *          description="Access forbidden operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Current user has no access to provided conversation."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=404,
     *          description="Not found operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Chat conversation with id=1 not found."
     *              }
     *          }
     *      )
     * )
     *
     * @param UpdateMessageRequest $request
     * @param ChatMessage $message
     * @return JsonResponse
     * @throws RepositoryException
     * @throws GuzzleException
     */
    public function update(UpdateMessageRequest $request, ChatMessage $message): JsonResponse
    {
        $message = $this->messageService->update($request, $message);
        $this->castService->broadcast($message, $message->conversation);
        $this->notificationService->sendClientUnreadMessageNotification($message, $message->conversation);
        $this->notificationService->sendMessageNotification($message, $message->conversation);

        return APIResponse::successResponse(new MessageResource($message));
    }

    /**
     * @SWG\Delete(
     *      path="/chat/messages/delete/{message_id}",
     *      operationId="deleteChatMessage",
     *      tags={"Chat Messages"},
     *      summary="Delete new chat message",
     *      description="Delete chat message.",
     *      security={{"Bearer":{}}},
     *     @SWG\Parameter(
     *       name="message_id",
     *       description="Message id",
     *       required=true,
     *       type="string",
     *       in="path"
     *     ),
     *      @SWG\Response(
     *          response=200,
     *          description="Succesful operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "success",
     *                  "data": {}
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=400,
     *          description="Failed operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "fail",
     *                  "data": {
     *                      "message": {"The message field is required."},
     *                      "conversation_id": {"The conversation id field is required."}
     *                  }
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=401,
     *          description="Unauthorised operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Unauthorised."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=403,
     *          description="Access forbidden operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Current user has no access to provided conversation."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=404,
     *          description="Not found operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Chat conversation with id=1 not found."
     *              }
     *          }
     *      )
     * )
     *
     *
     * @param ChatMessage $message
     * @return JsonResponse
     * @throws Exception
     */
    public function delete(ChatMessage $message): JsonResponse
    {
        $this->messageService->delete($message);
        $this->castService->broadcastDelete(['message_id' => $message->id, 'conversation_id' => $message->conversation_id]);

        return APIResponse::successResponse();
    }

    /**
     * @SWG\Get(
     *      path="/chat/messages/unread-total",
     *      operationId="CountUnreadMessages",
     *      tags={"Chat Messages"},
     *      summary="count unread messages",
     *      security={{"Bearer":{}}},
     *      @SWG\Response(
     *          response=200,
     *          description="Succesful operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "success",
     *                  "data": {{
     *                      "total": 1,
     *                  }},
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=401,
     *          description="Unauthorised operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Unauthorised."
     *              }
     *          }
     *      ),
     *      @SWG\Response(
     *          response=403,
     *          description="Access forbidden operation.",
     *          examples={
     *              "application/json": {
     *                  "status": "error",
     *                  "message": "Current user has no access to provided conversation."
     *              }
     *          }
     *      )
     * )
     *
     * @return JsonResponse
     */
    public function unreadTotal(): JsonResponse
    {
        return APIResponse::successResponse(new UnreadTotalResource($this->conversationService->unreadTotal()));
    }
}
