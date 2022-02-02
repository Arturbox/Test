<?php

namespace App\Models;

use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nicolaslopezj\Searchable\SearchableTrait;

class ChatMessage extends Model
{
    use SoftDeletes, SearchableTrait;

    public const SEEN = 1;
    public const NOT_SEEN = 0;
    public const PIN = 1;
    public const UNPIN = 0;
    public const LIKE = 1;
    public const UNLIKE = 0;
    public const ORIGINAL = 'original';
    public const EDITED = 'edited';

    protected $table = 'chat_messages';

    /**
     * Searchable rules.
     *
     * @var array
     */
    protected $searchable = [
        'columns' => [
            'chat_messages.edited_message' => 10,
            'chat_messages.message' => 5,
        ],
    ];

    /**
     * Fields that are mass assignable
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'conversation_id',
        'message',
        'is_seen',
        'reply_to_id',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_seen' => 'boolean',
    ];

    /**
     * @return bool
     */
    public function getPinAttribute(): bool
    {

    }

    /**
     * @return array
     */
    public function getLikeAttribute(): array
    {

    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return BelongsTo
     */
    public function userWithTrashed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    /**
     * @return BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }

    /**
     * @return BelongsTo
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    /**
     * The attachments that belong to the chat message.
     *
     * @return HasMany
     */
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'chat_message_id');
    }

    /**
     * @param Builder $query
     * @param int $value
     * @return BuildsQueries|Builder|mixed
     */
    public function scopeHasConversation(Builder $query, int $value)
    {
        return $query->where('conversation_id', $value);
    }

    /**
     * @param Builder $query
     * @param int $value
     * @return Builder
     */
    public function scopeHasUser(Builder $query, int $value)
    {
        return $query->where('user_id', $value);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopePin(Builder $query)
    {
        return $query->where( 'user_id', auth()->id())->where(function ($q) {
            $q->where('user_one_pin', 1)->orWhere('user_two_pin', 1);
        });
    }
}
