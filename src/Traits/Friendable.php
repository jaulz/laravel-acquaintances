<?php


namespace Jaulz\Acquaintances\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Jaulz\Acquaintances\Interaction;
use Jaulz\Acquaintances\Models\FriendFriendshipGroups;
use Jaulz\Acquaintances\Models\Friendship;
use Jaulz\Acquaintances\Status;

/**
 * Class Friendable
 * @package Jaulz\Acquaintances\Traits
 */
trait Friendable
{
    /**
     * @param  Model  $recipient
     *
     * @return \Jaulz\Acquaintances\Models\Friendship|false
     */
    public function befriend(Model $recipient)
    {

        if ( ! $this->canBefriend($recipient)) {
            return false;
        }

        $friendshipModelName = Interaction::getFriendshipModelName();
        $friendship = (new $friendshipModelName)->fillRecipient($recipient)->fill([
            'status' => Status::PENDING,
        ]);

        $this->friends()->save($friendship);

        Event::dispatch('acq.friendships.sent', [$this, $recipient]);

        return $friendship;

    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function unfriend(Model $recipient)
    {
        Event::dispatch('acq.friendships.cancelled', [$this, $recipient]);

        return $this->findFriendship($recipient)->delete();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function hasFriendRequestFrom(Model $recipient)
    {
        return $this->findFriendship($recipient)->whereSender($recipient)->whereStatus(Status::PENDING)->exists();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function hasSentFriendRequestTo(Model $recipient)
    {
        $friendshipModelName = Interaction::getFriendshipModelName();
        return $friendshipModelName::whereRecipient($recipient)->whereSender($this)->whereStatus(Status::PENDING)->exists();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function isFriendWith(Model $recipient)
    {
        return $this->findFriendship($recipient)->where('status', Status::ACCEPTED)->exists();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool|int
     */
    public function acceptFriendRequest(Model $recipient)
    {
        Event::dispatch('acq.friendships.accepted', [$this, $recipient]);

        return $this->findFriendship($recipient)->whereRecipient($this)->update([
            'status' => Status::ACCEPTED,
        ]);
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool|int
     */
    public function denyFriendRequest(Model $recipient)
    {
        Event::dispatch('acq.friendships.denied', [$this, $recipient]);

        return $this->findFriendship($recipient)->whereRecipient($this)->update([
            'status' => Status::DENIED,
        ]);
    }


    /**
     * @param  Model  $friend
     * @param       $groupSlug
     *
     * @return bool
     */
    public function groupFriend(Model $friend, $groupSlug)
    {

        $friendship = $this->findFriendship($friend)->whereStatus(Status::ACCEPTED)->first();
        $groupsAvailable = config('acquaintances.friendships_groups', []);

        if ( ! isset($groupsAvailable[$groupSlug]) || empty($friendship)) {
            return false;
        }

        $group = $friendship->groups()->firstOrCreate([
            'friendship_id' => $friendship->id,
            'group_id' => $groupsAvailable[$groupSlug],
            'friend_id' => $friend->getKey(),
            'friend_type' => $friend->getMorphClass(),
        ]);

        return $group->wasRecentlyCreated;

    }

    /**
     * @param  Model  $friend
     * @param       $groupSlug
     *
     * @return bool
     */
    public function ungroupFriend(Model $friend, $groupSlug = '')
    {

        $friendship = $this->findFriendship($friend)->first();
        $groupsAvailable = config('acquaintances.friendships_groups', []);

        if (empty($friendship)) {
            return false;
        }

        $where = [
            'friendship_id' => $friendship->id,
            'friend_id' => $friend->getKey(),
            'friend_type' => $friend->getMorphClass(),
        ];

        if ('' !== $groupSlug && isset($groupsAvailable[$groupSlug])) {
            $where['group_id'] = $groupsAvailable[$groupSlug];
        }

        $result = $friendship->groups()->where($where)->delete();

        return $result;

    }

    /**
     * @param  Model  $recipient
     *
     * @return \Jaulz\Acquaintances\Models\Friendship
     */
    public function blockFriend(Model $recipient)
    {
        // if there is a friendship between the two users and the sender is not blocked
        // by the recipient user then delete the friendship
        if ( ! $this->isBlockedBy($recipient)) {
            $this->findFriendship($recipient)->delete();
        }

        $friendshipModelName = Interaction::getFriendshipModelName();
        $friendship = (new $friendshipModelName)->fillRecipient($recipient)->fill([
            'status' => Status::BLOCKED,
        ]);

        Event::dispatch('acq.friendships.blocked', [$this, $recipient]);

        return $this->friends()->save($friendship);
    }

    /**
     * @param  Model  $recipient
     *
     * @return mixed
     */
    public function unblockFriend(Model $recipient)
    {
        Event::dispatch('acq.friendships.unblocked', [$this, $recipient]);

        return $this->findFriendship($recipient)->whereSender($this)->delete();
    }

    /**
     * @param  Model  $recipient
     *
     * @return \Jaulz\Acquaintances\Models\Friendship
     */
    public function getFriendship(Model $recipient)
    {
        return $this->findFriendship($recipient)->first();
    }

    /**
     * @param  string  $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     *
     */
    public function getAllFriendships($groupSlug = '')
    {
        return $this->findFriendships(null, $groupSlug)->get();
    }

    /**
     * @param  string  $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     *
     */
    public function getPendingFriendships($groupSlug = '')
    {
        return $this->findFriendships(Status::PENDING, $groupSlug)->get();
    }

    /**
     * @param  string  $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     *
     */
    public function getAcceptedFriendships($groupSlug = '')
    {
        return $this->findFriendships(Status::ACCEPTED, $groupSlug)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     *
     */
    public function getDeniedFriendships()
    {
        return $this->findFriendships(Status::DENIED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     *
     */
    public function getBlockedFriendships()
    {
        return $this->findFriendships(Status::BLOCKED)->get();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function hasBlocked(Model $recipient)
    {
        return $this->friends()->whereRecipient($recipient)->whereStatus(Status::BLOCKED)->exists();
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function isBlockedBy(Model $recipient)
    {
        return $recipient->hasBlocked($this);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Friendship[]
     */
    public function getFriendRequests()
    {
        $friendshipModelName = Interaction::getFriendshipModelName();
        return $friendshipModelName::whereRecipient($this)->whereStatus(Status::PENDING)->get();
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param  int  $perPage  Number
     * @param  string  $groupSlug
     *
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriends($perPage = 0, $groupSlug = '', array $fields = ['*'])
    {
        return $this->getOrPaginate($this->getFriendsQueryBuilder($groupSlug), $perPage, $fields);
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param  Model  $other
     * @param  int  $perPage  Number
     *
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMutualFriends(Model $other, $perPage = 0, array $fields = ['*'])
    {
        return $this->getOrPaginate($this->getMutualFriendsQueryBuilder($other), $perPage, $fields);
    }

    /**
     * Get the number of friends
     *
     * @return integer
     */
    public function getMutualFriendsCount($other)
    {
        return $this->getMutualFriendsQueryBuilder($other)->count();
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param  int  $perPage  Number
     *
     * @param  array  $fields
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFriendsOfFriends($perPage = 0, array $fields = ['*'])
    {
        return $this->getOrPaginate($this->friendsOfFriendsQueryBuilder(), $perPage, $fields);
    }


    /**
     * Get the number of friends
     *
     * @param  string  $groupSlug
     *
     * @return integer
     */
    public function getFriendsCount($groupSlug = '')
    {
        $friendsCount = $this->findFriendships(Status::ACCEPTED, $groupSlug)->count();

        return $friendsCount;
    }

    /**
     * @param  Model  $recipient
     *
     * @return bool
     */
    public function canBefriend($recipient)
    {
        // if user has Blocked the recipient and changed his mind
        // he can send a friend request after unblocking
        if ($this->hasBlocked($recipient)) {
            $this->unblockFriend($recipient);

            return true;
        }

        // if sender has a friendship with the recipient return false
        if ($friendship = $this->getFriendship($recipient)) {
            // if previous friendship was Denied then let the user send fr
            if ($friendship->status != Status::DENIED) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Model  $recipient
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function findFriendship(Model $recipient)
    {
        $friendshipModelName = Interaction::getFriendshipModelName();
        return $friendshipModelName::betweenModels($this, $recipient);
    }

    /**
     * @param        $status
     * @param  string  $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findFriendships($status = null, $groupSlug = '')
    {
        $friendshipModelName = Interaction::getFriendshipModelName();
        $query = $friendshipModelName::where(function ($query) {
            $query->where(function ($q) {
                $q->whereSender($this);
            })->orWhere(function ($q) {
                $q->whereRecipient($this);
            });
        })->whereGroup($this, $groupSlug);

        //if $status is passed, add where clause
        if ( ! is_null($status)) {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Get the query builder of the 'friend' model
     *
     * @param  string  $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getFriendsQueryBuilder($groupSlug = '')
    {

        $friendships = $this->findFriendships(Status::ACCEPTED, $groupSlug)->get(['sender_id', 'recipient_id']);
        $recipients = $friendships->pluck('recipient_id')->all();
        $senders = $friendships->pluck('sender_id')->all();

        return $this->where('id', '!=', $this->getKey())->whereIn('id', array_merge($recipients, $senders));
    }

    /**
     * Get the query builder of the 'friend' model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getMutualFriendsQueryBuilder(Model $other)
    {
        $user1['friendships'] = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $user1['recipients'] = $user1['friendships']->pluck('recipient_id')->all();
        $user1['senders'] = $user1['friendships']->pluck('sender_id')->all();

        $user2['friendships'] = $other->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $user2['recipients'] = $user2['friendships']->pluck('recipient_id')->all();
        $user2['senders'] = $user2['friendships']->pluck('sender_id')->all();

        $mutualFriendships = array_unique(
            array_intersect(
                array_merge($user1['recipients'], $user1['senders']),
                array_merge($user2['recipients'], $user2['senders'])
            )
        );

        return $this->whereNotIn('id', [$this->getKey(), $other->getKey()])->whereIn('id', $mutualFriendships);
    }

    /**
     * Get the query builder for friendsOfFriends ('friend' model)
     *
     * @param  string  $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function friendsOfFriendsQueryBuilder($groupSlug = '')
    {
        $friendships = $this->findFriendships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients = $friendships->pluck('recipient_id')->all();
        $senders = $friendships->pluck('sender_id')->all();

        $friendIds = array_unique(array_merge($recipients, $senders));

        $friendshipModelName = Interaction::getFriendshipModelName();
        $fofs = $friendshipModelName::where('status', Status::ACCEPTED)
                          ->where(function ($query) use ($friendIds) {
                              $query->where(function ($q) use ($friendIds) {
                                  $q->whereIn('sender_id', $friendIds);
                              })->orWhere(function ($q) use ($friendIds) {
                                  $q->whereIn('recipient_id', $friendIds);
                              });
                          })
                          ->whereGroup($this, $groupSlug)
                          ->get(['sender_id', 'recipient_id']);

        $fofIds = array_unique(
            array_merge($fofs->pluck('sender_id')->all(), $fofs->pluck('recipient_id')->all())
        );

//      Alternative way using collection helpers
//        $fofIds = array_unique(
//            $fofs->map(function ($item) {
//                return [$item->sender_id, $item->recipient_id];
//            })->flatten()->all()
//        );


        return $this->whereIn('id', $fofIds)->whereNotIn('id', $friendIds);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function friends()
    {
        $friendshipModelName = Interaction::getFriendshipModelName();
        return $this->morphMany($friendshipModelName, 'sender');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function groups()
    {
        $friendshipGroupsModelName = Interaction::getFriendshipGroupsModelName();
        return $this->morphMany($friendshipGroupsModelName, 'friend');
    }

    protected function getOrPaginate($builder, $perPage, array $fields = ['*'])
    {
        if ($perPage == 0) {
            return $builder->get($fields);
        }

        return $builder->paginate($perPage, $fields);
    }
}
