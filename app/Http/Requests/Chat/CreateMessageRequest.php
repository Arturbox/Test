<?php

namespace App\Http\Requests\Api\Chat;

use Illuminate\Foundation\Http\FormRequest;

class CreateMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'message' => 'sometimes|string',
            'conversation_id' => 'required|integer|exists:chat_conversations,id',
            'reply_to_id' => 'sometimes|integer|exists:chat_messages,id',
            'attachments.*'   => 'sometimes|array|max:12',
            'attachments.*.file'   => 'required|string',
            'attachments.*.name'   => 'required|string|max:255',
        ];
    }
}
