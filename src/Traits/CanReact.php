<?php

namespace Multicaret\Acquaintances\Traits;

use Illuminate\Support\Facades\Event;
use Multicaret\Acquaintances\Interaction;
use App\Http\Resources\Partial\InteractionRelationEnum;
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
      'relation_type' => $reaction,
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
   * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
   */
  public function reactions($class = __CLASS__)
  {
    return $this->morphedByMany(
      $class,
      'subject',
      config('acquaintances.tables.interactions')
    )
      ->wherePivot('relation', '=', Interaction::RELATION_REACT)
      ->withPivot(...Interaction::$pivotColumns)
      ->using(Interaction::getInteractionRelationModelName());
  }

  /**
   * Toggle reaction on subject.
   *
   * @param  Model  $subject
   * @param  string  $reaction
   *
   * @return array
   */
  public function toggleReaction(Model $subject, string $reaction)
  {
    // Toggle vote in transaction
    $ownReaction = null;
    DB::transaction(function () use ($subject, $reaction) {
      $ownReaction = $subject->ownReaction()->first();

      $toggled = false;
      if ($ownReaction && $ownReaction->relation_type === $reaction) {
        $ownReaction->delete();
        $toggled = true;
      }

      if (!$toggled) {
        $ownReaction = $this->react($subject, $reaction);
      }
    });

    // Reload vote
    $subject->refresh();

    return $this;
  }
}
