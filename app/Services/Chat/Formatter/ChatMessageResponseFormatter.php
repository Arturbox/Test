<?php

declare(strict_types=1);

namespace App\Services\Chat\Formatter;

use App\DTO\ChatMessageResponseDTO;
use App\Models\Attachment;
use App\Models\ChatMessage;
use App\Models\User;
use App\Tools\Collection;

class ChatMessageResponseFormatter
{
    /**
     * @param ChatMessageResponseDTO[] $list
     *
     * @return array
     */
    public function formatList(array $list): array
    {
        $data = [];
        foreach ($list as $messageResponseDTO) {
            $data[] = $this->format($messageResponseDTO);
        }

        return $data;
    }

    /**
     * @param ChatMessageResponseDTO $dto
     *
     * @return array
     */
    public function format(ChatMessageResponseDTO $dto): array
    {
        return [
            'id'              => $dto->id,
            'message'         => $dto->message,
            'edited_message'  => $dto->edited_message,
            'date'            => $dto->date->toAtomString(),
            'user'            => $this->prepareUser($dto->user),
            'conversation_id' => $dto->conversationId,
            'is_seen'         => $dto->isSeen,
            'reply_to'        => $dto->replyTo ? $this->prepareReplyTo($dto->replyTo) : null,
            'attachments'     => $dto->attachments ? $this->prepareAttachment($dto->attachments) : null
        ];
    }

    /**
     * @param User $user
     *
     * @return array
     */
    private function prepareUser(User $user): array
    {
        return [
            'id'     => $user->id,
            'name'   => $user->full_name,
            'avatar' => $user->freelancerInfo ? $user->freelancerInfo->image : null,
        ];
    }

    /**
     * @param ChatMessage $chatMessage
     *
     * @return array
     */
    private function prepareReplyTo(ChatMessage $chatMessage): array
    {
        return [
            'id'        => $chatMessage->id,
            'message'   => $chatMessage->message,
            'user_name' => $chatMessage->user->full_name,
            'date'      => $chatMessage->created_at->toAtomString(),
        ];
    }


    /**
     * @param $attachments
     * @return Collection|\Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    private function prepareAttachment($attachments)
    {
        $files = collect([]);

        foreach ($attachments as $file) {
            $files->push([
                'id' => $file->id,
                'name' => $file->filename,
                'alias' => $file->alias,
                'url' => $file->url,
                'size' => $file->size,
                'job_id' => $file->job_id,
                'chat_message_id' => $file->chat_message_id,
                'task_comment_id' => $file->task_comment_id,
                'created_at' => $file->created_at,
                'updated_at' => $file->updated_at,
                'deleted_at' => $file->deleted_at,
            ]);
        }

        return $files;
    }
}
