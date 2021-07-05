<?php

namespace Jaulz\Acquaintances\Traits;

use Illuminate\Support\Facades\DB;
use Jaulz\Acquaintances\Interaction;

trait CanBeReactedTo
{
  /**
   * Return reactors.
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
   */
  public function reactors()
  {
    $relation = $this->morphToMany(
      Interaction::getUserModelName(),
      'subject',
      config('acquaintances.tables.interactions')
    )
      ->wherePivot('relation', '=', Interaction::RELATION_REACT)
      ->using(Interaction::getInteractionRelationModelName());

    return $relation->withPivot(...Interaction::$pivotColumns);
  }

  /**
   * Return ratings as interaction items.
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function reactions()
  {
    return $this->hasMany(
      Interaction::getInteractionRelationModelName(),
      'subject_id'
    )->where('relation', '=', RELATION_REACT);
  }

  /**
   * Return reaction counts.
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function reactionCounts()
  {
    return $this->reactions()
      ->select(
        'subject_id',
        'relation',
        'relation_type',
        DB::raw('COUNT(*) as count')
      )
      ->groupBy('relation', 'relation_type', 'subject_id');
  }
}
