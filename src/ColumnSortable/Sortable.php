<?php

namespace Kyslik\ColumnSortable;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Kyslik\ColumnSortable\Exceptions\ColumnSortableException;

/**
 * Sortable trait.
 */
trait Sortable {

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array|null $defaultParameters
     *
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws \Kyslik\ColumnSortable\Exceptions\ColumnSortableException
     */
    public function scopeSortable($query, $defaultParameters = null) {
        if (request()->allFilled(['sort', 'direction'])) {
            return $this->queryOrderBuilder($query, request()->only(['sort', 'direction']));
        }

        if (is_null($defaultParameters)) {
            $defaultParameters = $this->getDefaultSortable();
        }

        if (!is_null($defaultParameters)) {
            $defaultSortArray = $this->formatToParameters($defaultParameters);
            if (config('columnsortable.allow_request_modification', true) && !empty($defaultSortArray)) {
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
    private function getDefaultSortable() {
        if (config('columnsortable.default_first_column', false)) {
            $sortBy = Arr::first($this->sortable);
            if (!is_null($sortBy)) {
                return [$sortBy => config('columnsortable.default_direction', 'asc')];
            }
        }

        return null;
    }


    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $sortParameters
     *
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @throws ColumnSortableException
     */
    private function queryOrderBuilder($query, array $sortParameters) {
        $columns = array_filter(explode(',', trim($sortParameters['sort'], ',')));
        $directions = array_filter(explode(',', trim($sortParameters['direction'], ',')));

        if (count($columns) != count($directions)) {
            throw new BadMethodCallException();
        }

        $model = $this;

        for ($index = 0; $index < count($columns); $index++) {
            list($column, $direction) = $this->parseParameters([
                'sort' => $columns[$index],
                'direction' => $directions[$index],
            ]);

            if (is_null($column)) {
                return $query;
            }

            $explodeResult = SortableLink::explodeSortParameter($column);
            if (!empty($explodeResult)) {
                $relationName = $explodeResult[0];
                $column = $explodeResult[1];

                try {
                    $relation = $query->getRelation($relationName);
                    $query = $this->queryJoinBuilder($query, $relation);
                } catch (BadMethodCallException $e) {
                    throw new ColumnSortableException($relationName, 1, $e);
                } catch (\Exception $e) {
                    throw new ColumnSortableException($relationName, 2, $e);
                }

                $model = $relation->getRelated();
            }

            if (method_exists($model, Str::camel($column) . 'Sortable')) {
                return call_user_func_array([$model, Str::camel($column) . 'Sortable'], [$query, $direction]);
            }

            if (isset($model->sortableAs) && in_array($column, $model->sortableAs)) {
                $query = $query->orderBy($column, $direction);
            } elseif ($this->columnExists($model, $column)) {
                $column = $model->getTable() . '.' . $column;
                $query = $query->orderBy($column, $direction);
            }
        }

        return $query;
    }


    /**
     * @param array $parameters
     *
     * @return array
     */
    private function parseParameters(array $parameters) {
        $column = Arr::get($parameters, 'sort');
        if (empty($column)) {
            return [null, null];
        }

        $direction = Arr::get($parameters, 'direction', '');
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = config('columnsortable.default_direction', 'asc');
        }

        return [$column, $direction];
    }


    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Relations\Relation $relation
     *
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @throws \Exception
     */
    private function queryJoinBuilder($query, $relation) {
        $relatedTable = $relation->getRelated()->getTable();
        $parentTable = $relation->getParent()->getTable();

        if ($parentTable === $relatedTable) {
            $query = $query->from($parentTable . ' as parent_' . $parentTable);
            $parentTable = 'parent_' . $parentTable;
            $relation->getParent()->setTable($parentTable);
        }

        if ($relation instanceof HasOne) {
            $relatedPrimaryKey = $relation->getQualifiedForeignKeyName();
            $parentPrimaryKey = $relation->getQualifiedParentKeyName();
        } elseif ($relation instanceof BelongsTo) {
            $relatedPrimaryKey = $relation->getQualifiedOwnerKeyName();
            $parentPrimaryKey = $relation->getQualifiedForeignKeyName();
        } else {
            throw new \Exception();
        }

        return $this->formJoin($query, $parentTable, $relatedTable, $parentPrimaryKey, $relatedPrimaryKey);
    }


    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $column
     *
     * @return bool
     */
    private function columnExists($model, $column) {
        return (isset($model->sortable)) ? in_array($column, $model->sortable) :
            Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), $column);
    }


    /**
     * @param array|string $array
     *
     * @return array
     */
    private function formatToParameters($array) {
        if (empty($array)) {
            return [];
        }

        $defaultDirection = config('columnsortable.default_direction', 'asc');

        if (is_string($array)) {
            return ['sort' => $array, 'direction' => $defaultDirection];
        }

        return (key($array) === 0) ? ['sort' => $array[0], 'direction' => $defaultDirection] : [
            'sort' => key($array),
            'direction' => reset($array),
        ];
    }


    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $parentTable
     * @param string $relatedTable
     * @param string $parentPrimaryKey
     * @param string $relatedPrimaryKey
     *
     * @return mixed
     */
    private function formJoin($query, $parentTable, $relatedTable, $parentPrimaryKey, $relatedPrimaryKey) {
        $joinType = config('columnsortable.join_type', 'leftJoin');

        return $query->select($parentTable . '.*')->{$joinType}($relatedTable, $parentPrimaryKey, '=', $relatedPrimaryKey);
    }
}
