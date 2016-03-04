<?php

namespace Kyslik\ColumnSortable;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kyslik\ColumnSortable\Exceptions\ColumnSortableException;
use Illuminate\Database\Eloquent\Relations\Relation;

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
     * @param HasOne     $relation
     *
     * @return query
     */
    private function queryJoinBuilder($query, Relation $relation)
    {
        $relatedModel = $relation->getRelated();
        $parentModel = $relation->getParent();

        $relatedTable = $relatedModel->getTable();
        $parentTable = $parentModel->getTable();

        $relatedKey = $parentTable . '.' . $relation->getForeignKey(); // table.key
        $parentKey = $relatedTable . '.' . $relatedModel->primaryKey; // table.key

        return $query->select($parentTable . '.*')->leftJoin($relatedTable, $relatedKey, '=', $parentKey);
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
        $sort = $sortName = array_get($a, 'sort', null);

        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'asc';
        }

        if (!is_null($sort)) {

            if ($oneToOneSort = $this->getOneToOneSortOrNull($sort)) {

                $relationName = $oneToOneSort[0];
                $sort = $oneToOneSort[1];

                try {
                    $relation = $query->getRelation($relationName);
                } catch (\Exception $e) { }

                if(isset($relation) && ($relation instanceof BelongsTo || $relation instanceof HasOne)) {
                  $model = $relation->getRelated();
                  $query = $this->queryJoinBuilder($query, $relation);
                }

            }

            $sort = $model->getTable() . '.' . $sort;

            if ($this->columnExists($this, $sortName)) {
                $query = $query->orderBy($sort, $order);
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
            if (count($oneToOneSort) !== 2 && $this->sortable) {
                return null;
            }
            return $oneToOneSort;
        }

        return null;
    }

    public function getSortable() {
      return isset($this->sortable) ? $this->sortable : $this->getFillable();
    }

    /**
     * @param $model
     * @param $column
     *
     * @return bool
     */
    private function columnExists($model, $column) {
        return in_array($column, $model->getSortable());
    }
}
