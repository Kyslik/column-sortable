<?php

namespace Kyslik\ColumnSortable;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Kyslik\ColumnSortable\Exceptions\ColumnSortableException;
use BadMethodCallException;
use ErrorException;

/**
 * Trait Sortable.
 */
trait Sortable
{
    /**
     * @param            $query
     * @param array|null $default
     *
     * @return mixed
     */
    public function scopeSortable($query, array $default = null)
    {
        if (Input::has('sort') && Input::has('order')) {
            return $this->queryOrderBuilder($query, Input::only(['sort', 'order']));
        } elseif (!is_null($default)) {
            return $this->queryOrderBuilder($query, $this->formatDefaultArray($default));
        } else {
            return $query;
        }
    }

     /**
     * @param            $query
     * @param Relation   $relation
     *
     * @throws InvalidArgumentException
     * @return string query
     */
    private function queryJoinBuilder($query, Relation $relation)
    {
        $relatedModel = $relation->getRelated();
        $relatedTable = $relatedModel->getTable();
        $parentModel = $relation->getParent();
        $parentTable = $parentModel->getTable();

        if (is_a($relation, BelongsTo::class)) {
            $relatedKey = $relatedTable . '.' . $relation->getOtherKey(); // table.key
            $parentKey = $parentTable . '.' . $relation->getForeignKey(); // table.key
        } else if (is_a($relation, HasOne::class)) {
            $relatedKey = $relation->getForeignKey(); // table.key
            $parentKey = $parentTable . '.' . $parentModel->primaryKey; // table.key
        } else {
            throw new InvalidArgumentException('Relation must be of type BelongsTo or HasOne');
        }
        return $query->select($parentTable . '.*')->join($relatedTable, $parentKey, '=', $relatedKey);
    }

    /**
     * @param       $query
     * @param array $a
     *
     * @return query
     */
    private function queryOrderBuilder($query, array $a)
    {
        $model = $this;

        $order = array_get($a, 'order', 'asc');
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'asc';
        }

        $sort = array_get($a, 'sort', null);
        if (!is_null($sort)) {
            if ($oneToOneSort = $this->getOneToOneSortOrNull($sort)) {
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
                return $query->orderBy($sort, $order);
            }
        }

        return $query;
    }

    /**
     * @param array $a
     *
     * @return array
     */
    private function formatDefaultArray(array $a)
    {
        $order = null;
        reset($a);

        if ((bool) count(array_filter(array_keys($a), 'is_string'))) {
            $sort = key($a);
            $order = array_get($a, $sort, null);
        } else {
            $sort = current($a);
        }

        if (!$sort) {
            return [];
        }

        return ['sort' => $sort, 'order' => $order];
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public static function link(array $parameters) //Extending Blade; Blade sends array.
    {
        if (count($parameters) === 1) {
            $parameters[1] = ucfirst($parameters[0]);
        }

        $sort = $sortOriginal = $parameters[0];
        $title = $parameters[1];

        $icon = Config::get('columnsortable.default_icon_set');

        if ($oneToOneSort = self::getOneToOneSortOrNull($sort)) {
            $sort = $oneToOneSort[1];
        }

        foreach (Config::get('columnsortable.columns') as $key => $value) {
            if (in_array($sort, $value['rows'])) {
                $icon = $value['class'];
            }
        }

        if (Input::get('sort') == $sortOriginal && in_array(Input::get('order'), ['asc', 'desc'])) {
            $asc_suffix = Config::get('columnsortable.asc_suffix', '-asc');
            $desc_suffix = Config::get('columnsortable.desc_suffix', '-desc');
            
            $icon = $icon . (Input::get('order') === 'asc' ? $asc_suffix : $desc_suffix);
        } else {
            $icon = Config::get('columnsortable.sortable_icon');
        }

        $parameters = [
            'sort' => $sortOriginal,
            'order' => Input::get('order') === 'desc' ? 'asc' : 'desc',
        ];

        $queryString = http_build_query(array_merge(Request::except('sort', 'order', 'page'), $parameters));
        $anchorClass = Config::get('columnsortable.anchor_class', null);
        if ($anchorClass !== null) {
            $anchorClass = 'class="' . $anchorClass . '"';
        }

        return '<a ' . $anchorClass . ' href="'. url(Request::path() . '?' . $queryString) . '"' . '>' . htmlentities($title) . '</a>' . ' ' . '<i class="' . $icon . '"></i>';
    }

    /**
     * @param $sort
     *
     * @return array|null
     */
    private static function getOneToOneSortOrNull($sort)
    {
        $separator = Config::get('columnsortable.uri_relation_column_separator', '.');
        if (str_contains($sort, $separator)) {
            $oneToOneSort = explode($separator, $sort);
            if (count($oneToOneSort) !== 2) {
                throw new ColumnSortableException();
            }
            return $oneToOneSort;
        }

        return null;
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
}
