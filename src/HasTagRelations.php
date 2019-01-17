<?php

namespace Spatie\Tags;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasTagRelations
{
    public function hasManyTaggedWith($related, $tagsKey, $anyOrAll = 'any')
    {
        $instance = $this->newRelatedInstance($related);

        return $this->newHasManyTaggedWith(
            $instance->newQuery(), $this, $tagsKey, $anyOrAll
        );
    }

    public function newHasManyTaggedWith(Builder $query, Model $parent, $tagsKey, $anyOrAll = 'all')
    {
        return new HasManyTaggedWith($query, $parent, $tagsKey, $anyOrAll);
    }
}