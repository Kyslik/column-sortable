<?php

namespace Kyslik\ColumnSortable;

use BadMethodCallException;
use ErrorException;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Kyslik\ColumnSortable\Exceptions\ColumnSortableException;

/**
 * Sortable trait.
 */
trait Sortable
{
    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param array|null $default
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeSortable($query, $default = null)
    {
        if (Request::has('sort') && Request::has('order')) {

            return $this->queryOrderBuilder($query, Request::only(['sort', 'order']));
        } elseif (!is_null($default)) {
            $defaultSortArray = $this->getDefaultSortArray($default);
            if (Config::get('columnsortable.allow_request_modification', true) && !empty($defaultSortArray)) {
                Request::merge($defaultSortArray);
            }

            return $this->queryOrderBuilder($query, $defaultSortArray);
        } else {

            return $query;
        }
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $sortArray
     * @return \Illuminate\Database\Query\Builder
     * @throws ColumnSortableException
     */
    private function queryOrderBuilder($query, array $sortArray)
    {
        $model = $this;
        //dd($model);
        $direction = array_get($sortArray, 'order', 'asc');

        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = Config::get('columnsortable.default_direction', 'asc');
        }

        $sort = array_get($sortArray, 'sort', null);

        if (!is_null($sort)) {
            if ($oneToOneSort = SortableLink::getOneToOneSortOrNull($sort)) {
                $relationName = $oneToOneSort[0];
                $sort = $oneToOneSort[1];
                try {
                    $relation = $query->getRelation($relationName);
                    $query = $this->queryJoinBuilder($query, $relation);
                } catch (BadMethodCallException $e) {
                    throw new ColumnSortableException($relationName, 1, $e);
                } catch (ErrorException $e) {
                    throw new ColumnSortableException($relationName, 2, $e);
                }

                $model = $relation->getRelated();
            }

            if ($this->columnExists($model, $sort)) {
                return $query->orderBy($sort, $direction);
            }
        }

        return $query;
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @param HasOne $relation
     *
     * @return \Illuminate\Database\Query\Builder
     */
    private function queryJoinBuilder($query, HasOne $relation)
    {
        $relatedModel = $relation->getRelated();
        $relatedKey = $relation->getForeignKey(); // table.key
        $relatedTable = $relatedModel->getTable();

        $parentModel = $relation->getParent();
        $parentTable = $parentModel->getTable();
        $parentKey = $parentTable . '.' . $parentModel->primaryKey; // table.key

        return $query->select($parentTable . '.*')->join($relatedTable, $parentKey, '=', $relatedKey);
    }

    /**
     * @param $model
     * @param $column
     *
     * @return bool
     */
    private function columnExists($model, $column)
    {
        if (!isset($model->sortable)) {
            return Schema::hasColumn($model->getTable(), $column);
        } else {
            return in_array($column, $model->sortable);
        }
    }

    /**
     * @param array|string $sort
     *
     * @return array
     */
    private function getDefaultSortArray($sort)
    {
        if (empty($sort)) {
            return [];
        }

        $configDefaultOrder = Config::get('columnsortable.default_direction', 'asc');

        if (is_string($sort)) {
            return ['sort' => $sort, 'order' => $configDefaultOrder];
        }

        reset($sort);
        $each = each($sort);

        if ($each[0] === 0) {
            return ['sort' => $each[1], 'order' => $configDefaultOrder];
        } else {
            return ['sort' => $each[0], 'order' => $each[1]];
        }
    }
}
