<?php

namespace Kyslik\ColumnSortable;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
     * @param \Illuminate\Database\Query\Builder $query
     * @param array|null $defaultParameters
     *
     * @return \Illuminate\Database\Query\Builder
     * @throws \Kyslik\ColumnSortable\Exceptions\ColumnSortableException
     */
    public function scopeSortable($query, $defaultParameters = null)
    {
        if (request()->allFilled(['sort', 'direction'])) { // allFilled() is macro
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
    private function getDefaultSortable()
    {
        if (config('columnsortable.default_first_column', false)) {
            $sortBy = Arr::first($this->sortable);
            if (!is_null($sortBy)) {
                return [$sortBy => config('columnsortable.default_direction', 'asc')];
            }
        }

        return null;
    }


    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $sortParameters
     *
     * @return \Illuminate\Database\Query\Builder
     *
     * @throws ColumnSortableException
     */
    private function queryOrderBuilder($query, array $sortParameters)
    {
        $model = $this;
        $subModel = false;

        list($column, $direction) = $this->parseParameters($sortParameters);

        if (is_null($column)) {
            return $query;
        }

        $explodeResult = SortableLink::explodeSortParameter($column);
        if (!empty($explodeResult) && count($explodeResult) >= 2) {
            if (count($explodeResult) == 5) {
                $relationName = $explodeResult[0];
                $sub1RelationName = $explodeResult[1];
                $sub2RelationName = $explodeResult[2];
                $sub3RelationName = $explodeResult[3];
                $column = $explodeResult[4];
                try {
                    $relation = $query->getRelation($relationName);
                    $sub1Relation = $relation->getRelation($sub1RelationName);
                    $sub2Relation = $sub1Relation->getRelation($sub2RelationName);
                    $sub3Relation = $sub2Relation->getRelation($sub3RelationName);
                    $query = $this->queryJoinBuilder($query, $relation, $sub1Relation, $sub2Relation, $sub3Relation);
                } catch (BadMethodCallException $e) {
                    throw new ColumnSortableException($relationName, 1, $e);
                } catch (\Exception $e) {
                    throw new ColumnSortableException($relationName, 2, $e);
                }
                $model = $relation->getRelated();
                $sub1Model = $sub1Relation->getRelated();
                $sub2Model = $sub2Relation->getRelated();
                $sub3Model = $sub3Relation->getRelated();
            } else if (count($explodeResult) == 4) {
                $relationName = $explodeResult[0];
                $sub1RelationName = $explodeResult[1];
                $sub2RelationName = $explodeResult[2];
                $column = $explodeResult[3];
                try {
                    $relation = $query->getRelation($relationName);
                    $sub1Relation = $relation->getRelation($sub1RelationName);
                    $sub2Relation = $sub1Relation->getRelation($sub2RelationName);
                    $query = $this->queryJoinBuilder($query, $relation, $sub1Relation, $sub2Relation);
                } catch (BadMethodCallException $e) {
                    throw new ColumnSortableException($relationName, 1, $e);
                } catch (\Exception $e) {
                    throw new ColumnSortableException($relationName, 2, $e);
                }
                $model = $relation->getRelated();
                $sub1Model = $sub1Relation->getRelated();
                $sub2Model = $sub2Relation->getRelated();
            } else if (count($explodeResult) == 3) {
                $relationName = $explodeResult[0];
                $subRelationName = $explodeResult[1];
                $column = $explodeResult[2];
                try {
                    $relation = $query->getRelation($relationName);
                    $subRelation = $relation->getRelation($subRelationName);
                    $query = $this->queryJoinBuilder($query, $relation, $subRelation);
                } catch (BadMethodCallException $e) {
                    throw new ColumnSortableException($relationName, 1, $e);
                } catch (\Exception $e) {
                    throw new ColumnSortableException($relationName, 2, $e);
                }
                $subModel = $subRelation->getRelated();
                $model = $relation->getRelated();
            } else if (count($explodeResult) == 2) {
                $relationName = $explodeResult[0];
                $column = $explodeResult[1];
                try {
                    $relation = $query->getRelation($relationName);
                    $query = $this->queryJoinBuilder($query, $relation, false);
                } catch (BadMethodCallException $e) {
                    throw new ColumnSortableException($relationName, 1, $e);
                } catch (\Exception $e) {
                    throw new ColumnSortableException($relationName, 2, $e);
                }
                $model = $relation->getRelated();
            }
        }

        if (method_exists($model, Str::camel($column) . 'Sortable')) {
            return call_user_func_array([$model, Str::camel($column) . 'Sortable'], [$query, $direction]);
        }

        if (isset($model->sortableAs) && in_array($column, $model->sortableAs)) {
            $query = $query->orderBy($column, $direction);
        } elseif ($this->columnExists($model, $subModel, $column)) {
            if ($subModel) {
                $column = $subModel->getTable() . '.' . $column;
            } else {
                $column = $model->getTable() . '.' . $column;
            }
            $query = $query->orderBy($column, $direction);
        }

        return $query;
    }


    /**
     * @param array $parameters
     *
     * @return array
     */
    private function parseParameters(array $parameters)
    {
        $column = Arr::get($parameters, 'sort');
        if (empty($column)) {
            return [null, null];
        }

        $direction = Arr::get($parameters, 'direction', []);
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = config('columnsortable.default_direction', 'asc');
        }

        return [$column, $direction];
    }


    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param \Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Eloquent\Relations\HasOne $relation
     *
     * @return \Illuminate\Database\Query\Builder
     *
     * @throws \Exception
     */
    private function queryJoinBuilder($query, $relation, $sub1Relation = null, $sub2Relation = null, $sub3Relation = null)
    {
        $sub1RelatedTable = ($sub1Relation) ? $sub1Relation->getRelated()->getTable() : false;
        $sub2RelatedTable = ($sub2Relation) ? $sub2Relation->getRelated()->getTable() : false;
        $sub3RelatedTable = ($sub3Relation) ? $sub3Relation->getRelated()->getTable() : false;

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

        $sub1RelatedParentPrimaryKey = false;
        $sub1RelatedPrimaryKey = false;

        $sub2RelatedParentPrimaryKey = false;
        $sub2RelatedPrimaryKey = false;

        $sub3RelatedParentPrimaryKey = false;
        $sub3RelatedPrimaryKey = false;

        if ($sub1Relation) {
            if ($sub1Relation instanceof HasOne) {
                $sub1RelatedParentPrimaryKey = $sub1Relation->getQualifiedForeignKeyName();
                $sub1RelatedPrimaryKey = $sub1Relation->getQualifiedParentKeyName();
            } elseif ($sub1Relation instanceof BelongsTo) {
                $sub1RelatedParentPrimaryKey = $sub1Relation->getQualifiedOwnerKeyName();
                $sub1RelatedPrimaryKey = $sub1Relation->getQualifiedForeignKeyName();
            } else {
                throw new \Exception();
            }
        }

        if ($sub2Relation) {
            if ($sub2Relation instanceof HasOne) {
                $sub2RelatedParentPrimaryKey = $sub2Relation->getQualifiedForeignKeyName();
                $sub2RelatedPrimaryKey = $sub2Relation->getQualifiedParentKeyName();
            } elseif ($sub2Relation instanceof BelongsTo) {
                $sub2RelatedParentPrimaryKey = $sub2Relation->getQualifiedOwnerKeyName();
                $sub2RelatedPrimaryKey = $sub2Relation->getQualifiedForeignKeyName();
            } else {
                throw new \Exception();
            }
        }

        if ($sub3Relation) {
            if ($sub3Relation instanceof HasOne) {
                $sub3RelatedParentPrimaryKey = $sub3Relation->getQualifiedForeignKeyName();
                $sub3RelatedPrimaryKey = $sub3Relation->getQualifiedParentKeyName();
            } elseif ($sub3Relation instanceof BelongsTo) {
                $sub3RelatedParentPrimaryKey = $sub3Relation->getQualifiedOwnerKeyName();
                $sub3RelatedPrimaryKey = $sub3Relation->getQualifiedForeignKeyName();
            } else {
                throw new \Exception();
            }
        }

        return $this->formJoin($query,
            $parentTable, $relatedTable, $sub1RelatedTable, $sub2RelatedTable, $sub3RelatedTable,
            $parentPrimaryKey, $relatedPrimaryKey,
            $sub1RelatedParentPrimaryKey, $sub1RelatedPrimaryKey,
            $sub2RelatedParentPrimaryKey, $sub2RelatedPrimaryKey,
            $sub3RelatedParentPrimaryKey, $sub3RelatedPrimaryKey,
        );
    }


    /**
     * @param $model
     * @param $column
     *
     * @return bool
     */
    private function columnExists($model, $subModel, $column)
    {
        if (!$subModel) {
            return (isset($model->sortable)) ? in_array($column, $model->sortable) :
                Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), $column);
        }

        return (isset($subModel->sortable)) ? in_array($column, $subModel->sortable) :
            Schema::connection($subModel->getConnectionName())->hasColumn($subModel->getTable(), $column);
    }


    /**
     * @param array|string $array
     *
     * @return array
     */
    private function formatToParameters($array)
    {
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
     * @param $query
     * @param $parentTable
     * @param $relatedTable
     * @param $parentPrimaryKey
     * @param $relatedPrimaryKey
     *
     * @return mixed
     */
    private function formJoin(
        $query,
        $parentTable, $relatedTable, $sub1RelatedTable, $sub2RelatedTable, $sub3RelatedTable,
        $parentPrimaryKey, $relatedPrimaryKey,
        $sub1RelatedParentPrimaryKey, $sub1RelatedPrimaryKey,
        $sub2RelatedParentPrimaryKey, $sub2RelatedPrimaryKey,
        $sub3RelatedParentPrimaryKey, $sub3RelatedPrimaryKey,
    )
    {
        $joinType = config('columnsortable.join_type', 'leftJoin');

        if ($query->getQuery()->columns === null) {
            $query->select($parentTable.'.*');
        }

        $query->{$joinType}($relatedTable, $parentPrimaryKey, '=', $relatedPrimaryKey);

        if ($sub1RelatedTable) {
            $query->{$joinType}($sub1RelatedTable, $sub1RelatedParentPrimaryKey, '=', $sub1RelatedPrimaryKey);
        }
        if ($sub2RelatedTable) {
            $query->{$joinType}($sub2RelatedTable, $sub2RelatedParentPrimaryKey, '=', $sub2RelatedPrimaryKey);
        }
        if ($sub3RelatedTable) {
            $query->{$joinType}($sub3RelatedTable, $sub3RelatedParentPrimaryKey, '=', $sub3RelatedPrimaryKey);
        }

        return $query;
    }
}
