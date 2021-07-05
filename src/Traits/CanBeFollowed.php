<?php


namespace Jaulz\Acquaintances\Traits;

use Jaulz\Acquaintances\Interaction;


/**
 * Trait CanBeFollowed.
 */
trait CanBeFollowed
{
    /**
     * Check if a model is followed by given model.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $user
     *
     * @return bool
     */
    public function isFollowedBy($user)
    {
        return Interaction::isRelationExists($this, 'followers', $user);
    }

    /**
     * Return followers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followers()
    {
        return $this->morphToMany(config('auth.providers.users.model'), 'subject',
            config('acquaintances.tables.interactions'))
                    ->wherePivot('relation', '=', Interaction::RELATION_FOLLOW)
                    ->withPivot(...Interaction::$pivotColumns);
    }
}
