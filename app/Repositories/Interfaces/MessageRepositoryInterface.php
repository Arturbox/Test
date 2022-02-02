<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface MessageRepositoryInterface
{
    /**
     * @param int|null $user_id
     * @param bool $grouped
     * @param bool|null $withJob
     * @return int
     */
    public function getAdminChatNotificationsCount(?int $user_id, bool $grouped = true, ?bool $withJob = false) : int;

    /**
     * @param int|null $user_id
     * @param int $limit
     * @param bool $grouped
     * @param bool|null $withJob
     * @param bool|null $isSeen
     * @param bool $paginated
     * @param \Closure|null $closure
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|Builder[]|Collection
     * @throws \App\Exceptions\RepositoryException
     */
    public function getAdminChatNotifications(?int $user_id, int $limit, bool $grouped = true, ?bool $withJob = null, ?bool $isSeen = ChatMessage::NOT_SEEN, bool $paginated = false, ?\Closure $closure = null);
}
