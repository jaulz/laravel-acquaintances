<?php


namespace Jaulz\Acquaintances\Traits;

use Jaulz\Acquaintances\Interaction;

/**
 * Trait CanBeSubscribed.
 */
trait CanBeSubscribed
{
    /**
     * Check if a model is subscribed by given model.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $user
     *
     * @return bool
     */
    public function isSubscribedBy($user)
    {
        return Interaction::isRelationExists($this, 'subscribers', $user);
    }

    /**
     * Return subscribers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function subscribers()
    {
        return $this->morphToMany(config('auth.providers.users.model'), 'subject',
            config('acquaintances.tables.interactions'))
                    ->wherePivot('relation', '=', Interaction::RELATION_SUBSCRIBE)
                    ->withPivot(...Interaction::$pivotColumns);
    }
}
