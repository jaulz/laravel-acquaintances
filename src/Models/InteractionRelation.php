<?php


namespace Jaulz\Acquaintances\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;
use Jaulz\Acquaintances\Interaction;
use Illuminate\Support\Str;

/**
 * Class InteractionRelation.
 */
class InteractionRelation extends MorphPivot
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var array
     */
    protected $with = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function interactable()
    {
        return $this->morphTo('interactable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function user()
    {
        return $this->belongsTo(Interaction::getUserModelName());
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $type
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query, $type = null)
    {
        $query->select('interactable_id', 'interactable_type', \DB::raw('COUNT(*) AS count'))
              ->groupBy('interactable_id', 'interactable_type')
              ->orderByDesc('count');

        if ($type) {
            $query->where('interactable_type', $this->normalizeinteractableType($type));
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getTable()
    {
        if ( ! $this->table) {
            $this->table = config('acquaintances.tables.interactions', 'interactions');
        }

        return parent::getTable();
    }

    /**
     * {@inheritdoc}
     */
    public function getDates()
    {
        return [parent::CREATED_AT];
    }

    /**
     * @param  string  $type
     *
     * @return string
     * @throws \InvalidArgumentException
     *
     */
    protected function normalizeinteractableType($type)
    {
        $morphMap = Relation::morphMap();

        if ( ! empty($morphMap) && in_array($type, $morphMap, true)) {
            $type = array_search($type, $morphMap, true);
        }

        if (class_exists($type)) {
            return $type;
        }

        $namespace = config('acquaintances.model_namespace', 'App');

        $modelName = $namespace.'\\'.Str::studly($type);

        if ( ! class_exists($modelName)) {
            throw new InvalidArgumentException("Model {$modelName} not exists. Please check your config 'acquaintances.model_namespace'.");
        }

        return $modelName;
    }
}
