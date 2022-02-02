<?php

namespace App\Services\Chat;

use App\Exceptions\RepositoryException;
use App\Models\Attachment;
use App\Models\ChatMessage;
use App\Models\User;
use App\Repositories\MessageRepository;
use App\Services\FileService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

/**
 * Class ChatMessageService
 * @package App\Services\Chat
 */
class ChatMessageService
{
    /**
     * @var FileService $fileService
     */
    private FileService $fileService;

    /**
     * @var ChatConversationService $conversationService
     */
    private ChatConversationService $conversationService;

    /**
     * @var MessageRepository $messageRepository
     */
    private MessageRepository $messageRepository;

    /**
     * @var AttachmentService $attachmentService
     */
    private AttachmentService $attachmentService;

    /**
     * @param MessageRepository $messageRepository
     * @param ChatConversationService $conversationService
     * @param FileService $fileService
     * @param AttachmentService $attachmentService
     **/
    public function __construct(
        FileService $fileService,
        AttachmentService $attachmentService,
        MessageRepository $messageRepository,
        ChatConversationService $conversationService
    ) {
        $this->fileService = $fileService;
        $this->attachmentService = $attachmentService;
        $this->messageRepository = $messageRepository;
        $this->conversationService = $conversationService;
    }

    /**
     * @param Request $request
     * @return LengthAwarePaginator|mixed
     * @throws RepositoryException
     */
    public function messages(Request $request)
    {
        $conversation = $this->conversationService->find((int) $request->input('conversation_id'));
        $this->conversationService->isUserConversation($conversation, $request->user());

        return
            (bool) $request->input('is_pin')
                ?
                $this->messageRepository->get([
                    'hasConversation' => $conversation->id,
                    'HasUser' => auth()->id(),
                    'pin' => true
                ])
                :
                $this->messageRepository->messagePaginate(
                    ['hasConversation' => $conversation->id],
                    ['user.freelancerInfo', 'attachments', 'replyTo.user'],
                    [
                        'trashed' => true,
                        'orderType' => 'DESC',
                        "offset" => $request->input('offset'),
                        'perPage' => $request->input('per_page'),
                        'search' => $request->input('search')
                    ]
                );
    }

    /**
     * @param Request $request
     * @return Model|mixed
     * @throws RepositoryException
     */
    public function save(Request $request)
    {
        if (
            !$this->conversationService->existBy(['user_one_id' => auth()->id(), 'id' => $request->input('conversation_id')])
            &&
            !$this->conversationService->existBy(['user_two_id' => auth()->id(), 'id' => $request->input('conversation_id')])
        ) {
            throw new UnauthorizedException('Current user has no access to this conversation.');
        }
        $this->attachmentService->checkExtension($request->input('attachments', []));
        $message = $this->messageRepository->create(
            array_merge($request->only(['message', 'conversation_id', 'reply_to_id']), ['user_id' => $request->user()->id])
        );

        foreach ($request->input('attachments', []) as $file) {
            $this->attachFiles($file, ['chat_message_id' => $message->id]);
        }

        return $message->load('attachments');
    }

    /**
     * @param Request $request
     * @param ChatMessage $message
     * @return Model
     * @throws RepositoryException
     */
    public function update(Request $request, ChatMessage $message): Model
    {
        if ($message->user->id !== $request->user()->id) {
            throw new UnauthorizedException('You can not edit this message.');
        }

        if ($message->created_at->addMinutes(10) < now()) {
            throw new BadRequestException('You can not edit message after 10 min passed.');
        }

        return $this->messageRepository->update([
            'status' => ChatMessage::EDITED,
            'edited_message' => $request->input('message'),
            ], $message
        );
    }

    /**
     * @param ChatMessage $message
     * @return bool|null
     * @throws \Exception
     */
    public function delete(ChatMessage $message)
    {
        if (!$this->isUserMessage($message, request()->user())) {
            throw new BadRequestException('You can not delete this message.');
        }

        return $this->messageRepository->delete($message);
    }

    /**
     * @param ChatMessage $message
     * @return Model
     * @throws RepositoryException
     */
    public function markSeen(ChatMessage $message): Model
    {
        $message = $message->load('conversation');
        $this->conversationService->isUserConversation($message->conversation, request()->user());
        $this->hasAccess($message);

        return $this->messageRepository->update(['is_seen' => ChatMessage::SEEN, 'seen_at' => now()],$message);
    }

    /**
     * @param ChatMessage $message
     * @return Model
     * @throws RepositoryException
     */
    public function markUnseen(ChatMessage $message): Model
    {
        $message = $message->load('conversation');
        $this->conversationService->isUserConversation($message->conversation, request()->user());
        $this->hasAccess($message);

        return $this->messageRepository->update(['is_seen' => ChatMessage::NOT_SEEN, 'seen_at' => null],$message);
    }

    /**
     * @param ChatMessage $message
     * @param Request $request
     * @return Model
     * @throws RepositoryException
     */
    public function like(ChatMessage $message, Request $request): Model
    {
        $this->conversationService->isUserConversation($message->conversation, $request->user());
        if ($message->user->id === $request->user()->id) {
            $data['user_one_like'] = $request->input('like');
        } else {
            $data['user_two_like'] = $request->input('like');
        }

        return $this->messageRepository->update($data, $message);
    }

    /**
     * @param ChatMessage $message
     * @param Request $request
     * @return Model
     * @throws RepositoryException
     */
    public function pin(ChatMessage $message, Request $request)
    {
        $this->conversationService->isUserConversation($message->conversation, $request->user());

        if ($message->user->id === $request->user()->id) {
            $data['user_one_pin'] = $request->input('pin');
        } else {
            $data['user_two_pin'] = $request->input('pin');
        }

        return $this->messageRepository->update($data, $message);
    }

    /**
     * @param ChatMessage $message
     * @return ChatMessage
     */
    public function hasAccess(ChatMessage $message)
    {
        if ($this->isUserMessage($message, request()->user())) {
            throw new BadRequestException('You can not mark as seen or unseen your messages.');
        }

        return $message;
    }

    /**
     * @param array $target
     * @param array $args
     * @return Attachment
     * @throws RepositoryException
     */
    public function attachFiles(array $target, array $args = []): Attachment
    {
        $file = $this->fileService->upload(
            $this->fileService->base64ToUploadFile($target['file']),
            'attachments/chat_message_' . $args['chat_message_id'],
            'do_spaces',
            ''
        );

        return $this->attachmentService->create(
            array_merge($this->attachmentService->prepareFileData(array_merge($file, $target)), $args)
        );
    }

    /**
     * @param ChatMessage $message
     * @param User $user
     * @return bool
     */
    public function isUserMessage(ChatMessage $message, User $user): bool
    {
        return $message->user->id === $user->id;
    }

    /**
     * @param int|null $user_id
     * @param int $limit
     * @return Collection
     * @throws RepositoryException
     */
    public function getAdminChatNotifications(?int $user_id, int $limit) : Collection
    {
        return $this->messageRepository->getAdminChatNotifications($user_id, $limit);
    }
}
