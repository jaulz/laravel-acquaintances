<?php


namespace Liliom\Acquaintances\Traits;

use Illuminate\Support\Facades\Event;
use Liliom\Acquaintances\Interaction;

/**
 * Trait CanFollow.
 */
trait CanFollow
{
    /**
     * Interaction an item or items.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $targets
     * @param string                                        $class
     *
     * @return array
     *
     * @throws \Exception
     */
    public function follow($targets, $class = __CLASS__)
    {
        Event::fire('acq.followships.follow', [$this, $targets]);
        return Interaction::attachRelations($this, 'followings', $targets, $class);
    }

    /**
     * Unfollow an item or items.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $targets
     * @param string                                        $class
     *
     * @return array
     */
    public function unfollow($targets, $class = __CLASS__)
    {
        Event::fire('acq.followships.unfollow', [$this, $targets]);
        return Interaction::detachRelations($this, 'followings', $targets, $class);
    }

    /**
     * Toggle follow an item or items.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $targets
     * @param string                                        $class
     *
     * @return array
     *
     * @throws \Exception
     */
    public function toggleFollow($targets, $class = __CLASS__)
    {
        return Interaction::toggleRelations($this, 'followings', $targets, $class);
    }

    /**
     * Check if user is following given item.
     *
     * @param int|array|\Illuminate\Database\Eloquent\Model $target
     * @param string                                        $class
     *
     * @return bool
     */
    public function isFollowing($target, $class = __CLASS__)
    {
        return Interaction::isRelationExists($this, 'followings', $target, $class);
    }

    /**
     * Return item followings.
     *
     * @param string $class
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followings($class = __CLASS__)
    {
        return $this->morphedByMany($class, config('acquaintances.morph_prefix'),
            config('acquaintances.tables.interactions'))
                    ->wherePivot('relation', '=', Interaction::RELATION_FOLLOW)
                    ->withPivot('followable_type', 'relation', 'created_at');
    }
}
