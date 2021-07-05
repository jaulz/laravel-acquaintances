<?php


namespace Multicaret\Acquaintances\Traits;

use Multicaret\Acquaintances\Interaction;

/**
 * Trait CanBeVoted.
 */
trait CanBeVoted
{
    /**
     * Check if item is voted by given user.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $user
     *
     * @return bool
     */
    public function isVotedBy($user, $type = null)
    {
        return Interaction::isRelationExists($this, 'voters', $user);
    }

    /**
     * Check if item is upvoted by given user.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $user
     *
     * @return bool
     */
    public function isUpvotedBy($user)
    {
        return Interaction::isRelationExists($this, 'upvoters', $user);
    }

    /**
     * Check if item is downvoted by given user.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $user
     *
     * @return bool
     */
    public function isDownvotedBy($user)
    {
        return Interaction::isRelationExists($this, 'downvoters', $user);
    }

    /**
     * Return voters.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function voters()
    {
        return $this->morphToMany(config('auth.providers.users.model'), 'subject',
            config('acquaintances.tables.interactions'))
                    ->wherePivotIn('relation', [Interaction::RELATION_UPVOTE, Interaction::RELATION_DOWNVOTE])
                    ->withPivot(...Interaction::$pivotColumns);
    }

    /**
     * Return upvoters.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function upvoters()
    {
        return $this->morphToMany(config('auth.providers.users.model'), 'subject',
            config('acquaintances.tables.interactions'))
                    ->wherePivot('relation', '=', Interaction::RELATION_UPVOTE)
                    ->withPivot(...Interaction::$pivotColumns);
    }

    /**
     * Return downvoters.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function downvoters()
    {
        return $this->morphToMany(config('auth.providers.users.model'), 'subject',
            config('acquaintances.tables.interactions'))
                    ->wherePivot('relation', '=', Interaction::RELATION_DOWNVOTE)
                    ->withPivot(...Interaction::$pivotColumns);
    }
}
