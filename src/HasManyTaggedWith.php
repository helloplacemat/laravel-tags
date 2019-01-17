<?php

namespace Spatie\Tags;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class HasManyTaggedWith extends Relation
{
    protected $tagsKey;

    /**
     * @var string
     */
    private $anyOrAll;

    /**
     * HasManyTaggedWith constructor.
     *
     * @param Builder $query
     * @param Model $parent
     * @param $tagsKey
     * @param string $anyOrAll
     */
    public function __construct(Builder $query, Model $parent, $tagsKey, $anyOrAll = 'any')
    {
        $this->tagsKey = $tagsKey;

        $this->anyOrAll = $anyOrAll;

        parent::__construct($query, $parent);

        if ( ! in_array(HasTags::class, class_uses($this->related))) {
            throw new \InvalidArgumentException('Related model must use Spatie\Tags\HasTags');
        }
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $method = $this->anyOrAll == 'all' ? 'withAllTags' : 'withAnyTags';

            $this->query->{$method}($this->getTags($this->parent));
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array $models
     *
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        if (static::$constraints) {
            $allTags = collect();

            foreach ($models as $model) {
                $tags = $this->getTags($model);

                $allTags = $allTags->concat($tags)->unique();
            }

            $this->query->withAnyTags($allTags);
        }
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array $models
     * @param  string $relation
     *
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array $models
     * @param  \Illuminate\Database\Eloquent\Collection $results
     * @param  string $relation
     *
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $method = $this->anyOrAll == 'all' ? 'has' : 'whereIn';

        foreach ($models as $model) {
            $tags = $this->getTags($model);

            $model->setRelation($relation, $this->getRelationValue($results, $method, $tags));
        }

        return $models;
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return ! is_null($this->getTags($this->parent))
            ? $this->query->get()
            : $this->related->newCollection();
    }

    protected function getTags(Model $model)
    {
        if (is_callable($this->tagsKey)) {
            return call_user_func($this->tagsKey, $model);
        }

        return $model->getAttribute($this->tagsKey);
    }

    /**
     * @param Collection $results
     * @param string $method
     * @param $tags
     *
     * @return Collection
     */
    protected function getRelationValue(Collection $results, string $method, $tags)
    {
        return $results->filter(function ($result) use ($method, $tags) {
            return $result->tags()->{$method}($tags)->isNotEmpty();
        })->all();
    }
}