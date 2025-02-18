<?php

namespace Jaulz\Acquaintances\Traits;

use Illuminate\Support\Facades\DB;
use Jaulz\Acquaintances\Interaction;

trait CanBeReactedTo
{
  /**
   * Return reactors.
   *
   * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
   */
  public function reactors()
  {
    $relation = $this->morphToMany(
      Interaction::getUserModelName(),
      'interactable',
      config('acquaintances.tables.interactions')
    )
      ->wherePivot('relation', '=', Interaction::RELATION_REACT)
      ->using(Interaction::getInteractionRelationModelName());

    return $relation->withPivot(...Interaction::$pivotColumns);
  }

  /**
   * Return reactions as interaction items.
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function reactions()
  {
    return $this->hasMany(
      Interaction::getInteractionRelationModelName(),
      'interactable_id'
    )->where('relation', '=', Interaction::RELATION_REACT);
  }

  /**
   * Return reaction of specific user.
   *
   * @param  any  $userId
   * @return \Illuminate\Database\Eloquent\Relations\MorphOne
   */
  public function reactionBy($userId)
  {
    return $this->morphOne(
      Interaction::getInteractionRelationModelName(),
      'interactable'
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
        'interactable_id',
        'relation',
        'type',
        DB::raw('COUNT(*) as count')
      )
      ->groupBy('relation', 'type', 'interactable_id');
  }
}
