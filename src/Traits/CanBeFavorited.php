<?php


namespace Jaulz\Acquaintances\Traits;

use Jaulz\Acquaintances\Interaction;


/**
 * Trait CanBeFavorited.
 */
trait CanBeFavorited
{
    /**
     * Check if a model is favorited by given model.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $user
     *
     * @return bool
     */
    public function isFavoritedBy($user)
    {
        return Interaction::isRelationExists($this, 'favoriters', $user);
    }

    /**
     * Return favoriters.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function favoriters()
    {
        return $this->morphToMany(Interaction::getUserModelName(), 'interactable',
            config('acquaintances.tables.interactions'))
                    ->wherePivot('relation', '=', Interaction::RELATION_FAVORITE)
                    ->withPivot(...Interaction::$pivotColumns)
                    ->using(Interaction::getInteractionRelationModelName());
    }
}
