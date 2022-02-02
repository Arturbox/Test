<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class MessageResource
 * @package App\Http\Resources\Chat
 */
class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return is_null($this->deleted_at) ? [
            'id' => $this->id,
            'message' => $this->message,
            'date' => $this->created_at,
            'conversation_id' => $this->conversation_id,
            'is_seen' => $this->is_seen,
            'seen_at' => $this->seen_at,
            'status' => $this->status,
            'pin' => $this->pin,
            'like' => $this->like,
            'edited_message' => $this->edited_message,
            'deleted_at' => $this->deleted_at,
            'reply_to' => new ReplyResource($this->replyTo),
            'user' => new UserResource($this->user),
            'attachments' => AttachmentResource::collection($this->attachments),
            ] : [
                'id' => $this->id,
                'conversation_id' => $this->conversation_id,
                'is_seen' => $this->is_seen,
                'seen_at' => $this->seen_at,
                'date' => $this->created_at,
                'deleted_at' => $this->deleted_at,
                'user' => new UserResource($this->user),
                'reply_to' => new ReplyResource($this->replyTo),
            ];

    }
}
