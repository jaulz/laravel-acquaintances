<?php


namespace Jaulz\Acquaintances\Traits;

use Jaulz\Acquaintances\Interaction;

/**
 * Trait CanBeViewed.
 */
trait CanBeViewed
{
    /**
     * Check if a model has been viewed by given model.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $user
     *
     * @return bool
     */
    public function isViewedBy($user)
    {
        return Interaction::isRelationExists($this, 'viewers', $user);
    }

    /**
     * Return viewers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function viewers()
    {
        return $this->morphToMany(Interaction::getUserModelName(), 'interactable',
            config('acquaintances.tables.interactions'))
                    ->wherePivot('relation', '=', Interaction::RELATION_VIEW)
                    ->withPivot(...Interaction::$pivotColumns)
                    ->using(Interaction::getInteractionRelationModelName());
    }
}
