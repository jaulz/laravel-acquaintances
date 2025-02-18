<?php


namespace Jaulz\Acquaintances\Traits;

use Illuminate\Support\Facades\Event;
use Jaulz\Acquaintances\Interaction;

/**
 * Class CanRate
 * @package Jaulz\Acquaintances\Traits
 */
trait CanRate
{
    private static $rateType = null;

    public static function bootCanRate()
    {
        self::$rateType = config('acquaintances.rating.defaults.type');
    }

    public function setRateType(string $ratingType)
    {
        self::$rateType = $ratingType;

        return $this;
    }

    private function rateType($type = null)
    {
        if (empty($type) && empty(self::$rateType)) {
            $this->setRateType(config('acquaintances.rating.defaults.type'));
        } elseif ( ! empty($type)) {
            $this->setRateType($type);
        }

        /*
         * Todo: [minor feature] Check if the passed type exists in types array
         *  config('acquaintances.rating.types')
         */

        return self::$rateType;
    }

    /**
     * Rate an item or items.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $targets
     * @param  float  $amount
     * @param  null  $ratingType
     * @param  string  $class
     *
     * @return array
     *
     * @throws \Exception
     */
    public function rate($targets, float $amount, $ratingType = null, $class = __CLASS__): array
    {
        Event::dispatch('acq.ratings.rate', [$this, $targets]);

        $attachRelations = Interaction::attachRelations($this, 'ratingsTo', $targets, $class, [
            'value' => $amount,
            'type' => $this->rateType($ratingType),
        ]);
        self::$rateType = config('acquaintances.rating.defaults.type');

        return $attachRelations;
    }

    /**
     * UnRate an item or items.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $targets
     * @param  null  $ratingType
     * @param  string  $class
     *
     * @return array
     * @throws \Exception
     */
    public function unrate($targets, $ratingType = null, $class = __CLASS__)
    {
        Event::dispatch('acq.ratings.unrate', [$this, $targets]);

        return Interaction::detachRelations($this, 'ratingsTo', $targets, $class, [
            'type' => $this->rateType($ratingType),
        ]);
    }

    /**
     * Toggle Rate an item or items.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $targets
     * @param  float  $amount
     * @param  null  $ratingType
     * @param  string  $class
     *
     * @return array
     *
     * @throws \Exception
     */
    public function toggleRate($targets, float $amount, $ratingType = null, $class = __CLASS__)
    {
        return Interaction::toggleRelations($this, 'ratingsTo', $targets, $class, [
            'value' => $amount,
            'type' => $this->rateType($ratingType),
        ]);
    }

    /**
     * Check if a model is rated by given model.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $target
     * @param  null  $ratingType
     * @param  string  $class
     *
     * @return bool
     */
    public function hasRated($target, $ratingType = null, $class = __CLASS__)
    {
        return Interaction::isRelationExists($this, 'ratingsTo', $target, $class, [
            'type' => $this->rateType($ratingType),
        ]);
    }


    /**
     * Return item likes.
     *
     * @param  string  $class
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function ratingsTo($class = __CLASS__)
    {
        $relation = $this->morphedByMany($class, 'interactable',
            config('acquaintances.tables.interactions'))
                         ->wherePivot('relation', '=', Interaction::RELATION_RATE)
                         ->using(Interaction::getInteractionRelationModelName());

        $relation = $relation->wherePivot('type', '=', $this->rateType());

        return $relation->withPivot(...Interaction::$pivotColumns);
    }

}
