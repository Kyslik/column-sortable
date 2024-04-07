<?php

namespace Kyslik\ColumnSortable;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Kyslik\ColumnSortable\Exceptions\ColumnSortableException;

/**
 * Sortable trait.
 */
trait Sortable
{

    /**
     * @param Builder $query
     * @param array|string|null $defaultParameters
     *
     * @return Builder
     * @throws ColumnSortableException
     */
    public function scopeSortable(Builder $query, $defaultParameters = null): Builder
    {
        if (request()->allFilled(['sort', 'direction'])) { // allFilled() is macro
            return $this->queryOrderBuilder($query, request()->only(['sort', 'direction']));
        }

        if (is_null($defaultParameters)) {
            $defaultParameters = $this->getDefaultSortable();
        }

        if ( ! is_null($defaultParameters)) {
            $defaultSortArray = $this->formatToParameters($defaultParameters);
            if (config('columnsortable.allow_request_modification', true) && ! empty($defaultSortArray)) {
                request()->merge($defaultSortArray);
            }

            return $this->queryOrderBuilder($query, $defaultSortArray);
        }

        return $query;
    }


    /**
     * Returns the first element of defined sortable columns from the Model
     *
     * @return array|null
     */
    private function getDefaultSortable(): ?array
    {
        if (config('columnsortable.default_first_column', false)) {
            $sortBy = Arr::first($this->sortable);
            if ( ! is_null($sortBy)) {
                return [$sortBy => config('columnsortable.default_direction', 'asc')];
            }
        }

        return null;
    }


    /**
     * @param Builder $query
     * @param array                              $sortParameters
     *
     * @return Builder
     *
     * @throws ColumnSortableException
     */
    private function queryOrderBuilder(Builder $query, array $sortParameters): Builder
    {
        $model = $this;

        list($column, $direction) = $this->parseParameters($sortParameters);

        if (is_null($column)) {
            return $query;
        }

        $explodeResult = SortableLink::explodeSortParameter($column);
        if ( ! empty($explodeResult)) {
            $relationName = $explodeResult[0];
            $column       = $explodeResult[1];

            try {
                $relation = $query->getRelation($relationName);
                $query    = $this->queryJoinBuilder($query, $relation);
            } catch (BadMethodCallException $e) {
                throw new ColumnSortableException($relationName, 1, $e);
            } catch (\Exception $e) {
                throw new ColumnSortableException($relationName, 2, $e);
            }

            $model = $relation->getRelated();
        }

        if (method_exists($model, Str::camel($column).'Sortable')) {
            return call_user_func_array([$model, Str::camel($column).'Sortable'], [$query, $direction]);
        }

        if (isset($model->sortableAs) && in_array($column, $model->sortableAs)) {
            $query = $query->orderBy($column, $direction);
        } elseif ($this->columnExists($model, $column)) {
            $column = $model->getTable().'.'.$column;
            $query  = $query->orderBy($column, $direction);
        }

        return $query;
    }


    /**
     * @param array $parameters
     *
     * @return array
     */
    private function parseParameters(array $parameters): array
    {
        $column = Arr::get($parameters, 'sort');
        if (empty($column)) {
            return [null, null];
        }

        $direction = Arr::get($parameters, 'direction', []);
        if ( ! in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = config('columnsortable.default_direction', 'asc');
        }

        return [$column, $direction];
    }


    /**
     * @param Builder $query
     * @param BelongsTo|\Illuminate\Database\Eloquent\Relations\HasOne $relation
     *
     * @return Builder
     *
     * @throws \Exception
     */
    private function queryJoinBuilder(Builder $query, $relation): Builder
    {
        $relatedTable = $relation->getRelated()->getTable();
        $parentTable  = $relation->getParent()->getTable();

        if ($parentTable === $relatedTable) {
            $query       = $query->from($parentTable.' as parent_'.$parentTable);
            $parentTable = 'parent_'.$parentTable;
            $relation->getParent()->setTable($parentTable);
        }

        if ($relation instanceof HasOne) {
            $relatedPrimaryKey = $relation->getQualifiedForeignKeyName();
            $parentPrimaryKey  = $relation->getQualifiedParentKeyName();
        } elseif ($relation instanceof BelongsTo) {
            $relatedPrimaryKey = $relation->getQualifiedOwnerKeyName();
            $parentPrimaryKey  = $relation->getQualifiedForeignKeyName();
        } else {
            throw new \Exception();
        }

        return $this->formJoin($query, $parentTable, $relatedTable, $parentPrimaryKey, $relatedPrimaryKey);
    }


    /**
     * @param $model
     * @param $column
     *
     * @return bool
     */
    private function columnExists($model, $column): bool
    {
        return (isset($model->sortable)) ? in_array($column, $model->sortable) :
            Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), $column);
    }


    /**
     * @param array|string $array
     *
     * @return array
     */
    private function formatToParameters($array): array
    {
        if (empty($array)) {
            return [];
        }

        $defaultDirection = config('columnsortable.default_direction', 'asc');

        if (is_string($array)) {
            return ['sort' => $array, 'direction' => $defaultDirection];
        }

        return (key($array) === 0) ? ['sort' => $array[0], 'direction' => $defaultDirection] : [
            'sort'      => key($array),
            'direction' => reset($array),
        ];
    }


    /**
     * @param $query
     * @param $parentTable
     * @param $relatedTable
     * @param $parentPrimaryKey
     * @param $relatedPrimaryKey
     *
     * @return mixed
     */
    private function formJoin($query, $parentTable, $relatedTable, $parentPrimaryKey, $relatedPrimaryKey)
    {
        $joinType = config('columnsortable.join_type', 'leftJoin');

        return $query->select($parentTable.'.*')->{$joinType}($relatedTable, $parentPrimaryKey, '=', $relatedPrimaryKey);
    }
}
