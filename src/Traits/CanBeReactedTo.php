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
   * Return reaction of specific user.
   *
   * @return \Illuminate\Database\Eloquent\Relations\MorphOne
   */
  public function reactionBy(any $userId)
  {
    return $this->morphOne(
      Interaction::getInteractionRelationModelName(),
      'subject'
    )->ofMany(['id' => 'max'], function ($query) use ($userId) {
      $query
        ->where('relation', Interaction::RELATION_REACT)
        ->where(config('acquaintances.tables.interactions_user_id_fk_column_name'), $userId);
    });
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
