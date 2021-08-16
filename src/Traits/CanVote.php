<?php


namespace Jaulz\Acquaintances\Traits;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Jaulz\Acquaintances\Interaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Trait CanVote.
 */
trait CanVote
{
    /**
     * Vote an item or items.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $targets
     * @param  string  $type
     * @param  int  $value
     * @param  string  $class
     *
     * @return array
     *
     * @throws \Exception
     */
    public function vote($targets, $type = 'up', $value = 1, $class = __CLASS__)
    {
        $this->cancelVote($targets);

        return Interaction::attachRelations($this, Interaction::RELATION_VOTE, $targets, $class, ['type' => $type, 'value' => $value]);
    }

    /**
     * Upvote an item or items.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $targets
     * @param  int  $value
     * @param  string  $class
     *
     * @return array
     *
     * @throws \Exception
     */
    public function upvote($targets, $value = 1, $class = __CLASS__)
    {
        Event::dispatch('acq.vote.up', [$this, $targets]);

        return $this->vote($targets, 'up', $value, $class);
    }

    /**
     * Downvote an item or items.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $targets
     * @param  int  $value
     * @param  string  $class
     *
     * @return array
     *
     * @throws \Exception
     */
    public function downvote($targets, $value = 1, $class = __CLASS__)
    {
        Event::dispatch('acq.vote.down', [$this, $targets]);

        return $this->vote($targets, 'down', $value, $class);
    }

    /**
     * Cancel vote for an item or items.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $targets
     * @param  string  $class
     *
     * @return Jaulz\Acquaintances\Traits\CanVote
     */
    public function cancelVote($targets, $class = __CLASS__)
    {
        Interaction::detachRelations($this, 'votes', $targets, $class);
        Event::dispatch('acq.vote.cancel', [$this, $targets]);

        return $this;
    }

    /**
     * Return item votes.
     *
     * @param  string  $class
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function votes($class = __CLASS__)
    {
        return $this->morphedByMany($class, 'subject',
            config('acquaintances.tables.interactions'))
                    ->wherePivot('relation', Interaction::RELATION_VOTE)
                    ->withPivot(...Interaction::$pivotColumns)
                    ->using(Interaction::getInteractionRelationModelName());
    }

    /**
     * Return item upvotes.
     *
     * @param  string  $class
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function upvotes($class = __CLASS__)
    {
        return $this->morphedByMany($class, 'subject',
            config('acquaintances.tables.interactions'))
                    ->wherePivot('relation', '=', Interaction::RELATION_VOTE)
                    ->wherePivot('type', '=', 'up')
                    ->withPivot(...Interaction::$pivotColumns)
                    ->using(Interaction::getInteractionRelationModelName());
    }

    /**
     * Return item downvotes.
     *
     * @param  string  $class
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function downvotes($class = __CLASS__)
    {
        return $this->morphedByMany($class, 'subject',
            config('acquaintances.tables.interactions'))
                    ->wherePivot('relation', '=', Interaction::RELATION_VOTE)
                    ->wherePivot('type', '=', 'down')
                    ->withPivot(...Interaction::$pivotColumns)
                    ->using(Interaction::getInteractionRelationModelName());
    }

    /**
     * Toggle vote on object.
     *
     * @param  Model  $subject
     * @param  string  $voteType
     *
     * @return array
     */
    public function toggleVote(Model $subject, $type = 'up', $value = 1)
    {
      // Toggle vote in transaction
      $vote = null;
      DB::transaction(function () use ($subject, $type, $vote) {
        $vote = $subject->voteBy($this->getKey())->first();
  
        // Explicitly delete vote because cancelVote uses "detach" internally
        // which does not trigger delete events on models
        $toggled = false;
        if ($vote) {
          $vote->delete();
          $toggled = strval($vote->type) === $type;
        }
  
        if (!$toggled) {
          $vote = $this->vote($this);
        }
      });
  
      return $this;
    }
}
