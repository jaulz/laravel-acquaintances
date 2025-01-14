<?php

namespace Jaulz\Acquaintances\Traits;

use Illuminate\Support\Facades\Event;
use Jaulz\Acquaintances\Interaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait CanReact
{
  /**
   * React to an item.
   *
   * @param  int|array|\Illuminate\Database\Eloquent\Model  $targets
   * @param  string  $reaction
   * @param  string  $class
   *
   * @return array
   *
   * @throws \Exception
   */
  public function react($targets, string $reaction, $class = __CLASS__): array
  {
    Event::dispatch('acq.ratings.react', [$this, $targets]);

    return Interaction::attachRelations($this, 'reactions', $targets, $class, [
      'type' => $reaction,
    ]);
  }

  /**
   * Unreact an item or items.
   *
   * @param  int|array|\Illuminate\Database\Eloquent\Model  $targets
   * @param  null  $ratingType
   * @param  string  $class
   *
   * @return array
   * @throws \Exception
   */
  public function unreact($targets, $class = __CLASS__)
  {
    Event::dispatch('acq.ratings.unreact', [$this, $targets]);

    return Interaction::detachRelations(
      $this,
      'reactions',
      $targets,
      $class,
      []
    );
  }

  /**
   * Check if a model has reactions by given model.
   *
   * @param  int|array|\Illuminate\Database\Eloquent\Model  $target
   * @param  string  $class
   *
   * @return bool
   */
  public function hasReacted($target, $class = __CLASS__)
  {
    return Interaction::isRelationExists($this, 'reactions', $target, $class);
  }

  /**
   * Return item reactions.
   *
   * @param  string  $class
   *
   * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
   */
  public function reactions($class = __CLASS__)
  {
    return $this->morphedByMany(
      $class,
      'interactable',
      config('acquaintances.tables.interactions')
    )
      ->wherePivot('relation', '=', Interaction::RELATION_REACT)
      ->withPivot(...Interaction::$pivotColumns)
      ->using(Interaction::getInteractionRelationModelName());
  }

  /**
   * Toggle reaction on interactable.
   *
   * @param  Model  $interactable
   * @param  string  $reactionType
   *
   * @return array
   */
  public function toggleReaction(Model $interactable, string $reactionType)
  {
    $reaction = null;
    DB::transaction(function () use ($interactable, $reaction, $reactionType) {
      $reaction = $interactable->reactionBy($this->getKey())->first();

      $toggled = false;
      if ($reaction && $reaction->type === $reactionType) {
        $reaction->delete();
        $toggled = true;
      }

      if (!$toggled) {
        $reaction = $this->react($interactable, $reactionType);
      }
    });

    return $this;
  }
}
