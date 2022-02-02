<?php

namespace App\Repositories;

use App\FreelancerTeam;
use App\Models\ChatMessage;
use App\Models\Job;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class MessageRepository
 * @package App\Repositories
 */
class MessageRepository extends Repository implements MessageRepositoryInterface
{
    /**
     * @var array|string[] $fillable
     */
    protected array $fillable = [
        'user_id',
        'conversation_id',
        'message',
        'is_seen',
        'reply_to_id',
        'status',
        'seen_at',
        'edited_message',
        'user_one_pin',
        'user_two_pin',
        'user_one_like',
        'user_two_like',
    ];

    /**
     * @var bool $isSales
     */
    protected bool $isSales = false;

    /**
     * @return string
     */
    protected function model(): string
    {
        return ChatMessage::class;
    }

    /**
     * @param array $filters
     * @param array $relations
     * @param array $meta
     * @return Collection
     */
    public function messagePaginate(array $filters = [], array $relations = [], array $meta = []): Collection
    {
        return $this->setMeta($meta)
            ->setFilters($filters)
            ->setTrashed($meta['trashed'] ?? false)
            ->setSearch(getVal($meta, 'search'))
            ->with(array_values($relations))
            ->orderBy($this->meta['orderBy'], $this->meta['orderType'])
            ->skip($this->meta['offset'])
            ->take($this->meta['perPage'])
            ->get($this->meta['columns']);
    }

    /**
     * @param bool $isSales
     */
    public function setIsSales(bool $isSales) : void
    {
        $this->isSales = $isSales;
    }

    /**
     * @param int|null $user_id
     * @param bool|null $withJob
     * @param bool|null $isSeen
     * @return Builder
     * @throws \App\Exceptions\RepositoryException
     */
    protected function getAdminChatNotificationsQuery(?int $user_id = null, ?bool $withJob = null, ?bool $isSeen = null) : Builder
    {
        return $this->query()
            ->when(isset($isSeen), function ($query) use ($isSeen) {
                $query->where('is_seen', $isSeen);
            })
            ->when($this->isSales, function ($query) use ($user_id) {
                $query->whereHas('conversation', function ($query) use ($user_id) {
                        $query->whereNotIn('user_two_id', $this->getSalesTeamFreelancersIds($user_id, false));
                    });
            })
            ->when($withJob !== false && $user_id && $this->isSales === false, function ($query) use ($user_id) {
                $query->whereHas('conversation.job.hireManagers', function ($query) use ($user_id) {
                        $query
                            ->whereIn('status', [Job::STATUS_ACTIVE, Job::STATUS_PAUSED_ACTIVE])
                            ->where('admin_id', $user_id);
                    });
            })
            ->when($withJob === false, function ($query) {
                $query->doesntHave('conversation.job.hireManagers');
            })
            ->when($withJob === true, function ($query) {
                $query->has('conversation.job.hireManagers');
            })
            ->when($isSeen === false, function ($query){
                 $query->has('conversation.unreadClientMessages');
            })
            ->when($isSeen === true, function ($query){
                 $query->has('conversation.readClientMessages');
            });
    }

    /**
     * Get the freelancers who (exist or dont exist) in sale's user team.
     *
     * @param int $user_id sales user ID
     * @param bool $exists
     * @return array
     */
    public function getSalesTeamFreelancersIds(int $user_id, bool $exists = true) : array
    {
        $freelancerIds = FreelancerTeam::query()
            ->with('salesTeams')
            ->whereHas('salesTeams', function ($query) use ($user_id, $exists) {
                if ($exists) {
                    $query->where('user_id', $user_id);
                } else {
                    $query->where('user_id', '<>', $user_id);
                }
            })
            ->get()
            ->pluck('freelancer.id')
            ->toArray() ?: [];

        return $freelancerIds;
    }

    /**
     * @param int|null $user_id
     * @param bool $grouped
     * @param bool|null $withJob
     * @return int
     * @throws \App\Exceptions\RepositoryException
     */
    public function getAdminChatNotificationsCount(?int $user_id, bool $grouped = true, ?bool $withJob = null) : int
    {
        $query = $this->getAdminChatNotificationsQuery($user_id, $withJob, ChatMessage::NOT_SEEN)->withCount('conversation');

        if ($grouped === true) {
            $query->groupBy('user_id');
        }

        return $query->get('conversation_count')->sum('conversation_count');
    }

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
    public function getAdminChatNotifications(?int $user_id, int $limit, bool $grouped = true, ?bool $withJob = null, ?bool $isSeen = ChatMessage::NOT_SEEN, bool $paginated = false, ?\Closure $closure = null)
    {
        $query = $closure
            ? $closure($this->getAdminChatNotificationsQuery($user_id, $withJob, $isSeen, $grouped))
            : $this->getAdminChatNotificationsQuery($user_id, $withJob, $isSeen, $grouped);


        $query = DB::table(DB::raw("({$query->toSql()}) as subna"))
            ->mergeBindings($query->getQuery())
            ->when($grouped, function ($query){
                $query->groupBy('conversation_id');
                $query->orderByDesc('conversation_id');
            })
            ->orderByDesc('updated_at');


        if ($paginated === true)
            $items = $query->paginate();
        elseif (!$closure)
            $items = $query->limit($limit)->get();
        else
            $items = $query->get();

        if ($grouped === true) {
            $newItems = $items instanceof \Illuminate\Pagination\LengthAwarePaginator
                ? $items->getCollection() : $items;


            $data = [];
            foreach ($newItems as $message) {
                try {
                    $message = ChatMessage::with(['user', 'conversation.job.hireManagers', 'conversation.unreadClientMessages'])->find($message->id);

                    if (empty($message->message)) continue; // TODO continue if message is empty, Should be fixed on message inserting event.

                    $job_id = $message->conversation->jobWithDeleted->id;

                    $data['job-' . $job_id] = $message;
                }
                catch (\Exception $e){
                    $data['user-' . $message->conversation->user_one_id.'-'.$message->conversation->user_two_id] = $message;
                }
            }


            if ($items instanceof \Illuminate\Pagination\LengthAwarePaginator)
                $items->setCollection(collect($data));
            else
                $items = $data;
        }

        return $items;
    }
}
