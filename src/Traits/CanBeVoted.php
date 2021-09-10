<?php


namespace Jaulz\Acquaintances\Traits;

use Illuminate\Support\Facades\DB;
use Jaulz\Acquaintances\Interaction;

/**
 * Trait CanBeVoted.
 */
trait CanBeVoted
{
    /**
     * Check if item is voted by given user.
     *
     * @param  int|array|\Illuminate\Database\Eloquent\Model  $user
     *
     * @return bool
     */
    public function isVotedBy($user, $type = null)
    {
        return Interaction::isRelationExists($this, 'voters', $user);
    }

    /**
     * Return voters.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function voters()
    {
        return $this->morphToMany(Interaction::getUserModelName(), 'subject',
            config('acquaintances.tables.interactions'))
                    ->wherePivot('relation', Interaction::RELATION_VOTE)
                    ->withPivot(...Interaction::$pivotColumns)
                    ->using(Interaction::getInteractionRelationModelName());
    }

    /**
     * Return votes as interaction items.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function votes()
    {
      return $this->hasMany(
        Interaction::getInteractionRelationModelName(),
        'subject_id'
      )->where('relation', '=', Interaction::RELATION_VOTE);
    }

    /**
     * Return upvoters.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function upvoters()
    {
        return $this->morphToMany(Interaction::getUserModelName(), 'subject',
            config('acquaintances.tables.interactions'))
                    ->wherePivot('relation', '=', Interaction::RELATION_VOTE)
                    ->wherePivot('type', '=', 'up')
                    ->withPivot(...Interaction::$pivotColumns)
                    ->using(Interaction::getInteractionRelationModelName());
    }

    /**
     * Return downvoters.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function downvoters()
    {
        return $this->morphToMany(Interaction::getUserModelName(), 'subject',
            config('acquaintances.tables.interactions'))
                    ->wherePivot('relation', '=', Interaction::RELATION_VOTE)
                    ->wherePivot('type', '=', 'down')
                    ->withPivot(...Interaction::$pivotColumns)
                    ->using(Interaction::getInteractionRelationModelName());
    }

    /**
     * Return vote of specific user.
     *
     * @param  any  $userId
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function voteBy($userId)
    {
        return $this->morphOne(Interaction::getInteractionRelationModelName(), 'subject')
                ->ofMany(['id' => 'max'], function ($query) use ($userId) {
                    $query
                        ->where('relation', Interaction::RELATION_VOTE)
                         ->where(config('acquaintances.tables.interactions_user_id_fk_column_name'), $userId);
                  });
    }

    /**
     * Return vote counts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voteCounts()
    {
      return $this->votes()
        ->select(
          'subject_id',
          'relation',
          'type',
          DB::raw('COUNT(*) as count')
        )
        ->groupBy('relation', 'type', 'subject_id');
    }

    /**
     * Return vote sums.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function voteSums()
    {
      return $this->votes()
        ->select(
          'subject_id',
          'relation',
          'type',
          DB::raw('SUM(value) as sum')
        )
        ->groupBy('relation', 'type', 'subject_id');
    }
}
