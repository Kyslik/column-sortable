<?php

namespace Kyslik\ColumnSortable;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Kyslik\ColumnSortable\Exceptions\InvalidSortArgumentException;
use Kyslik\ColumnSortable\Exceptions\RelationDoesNotExistsException;
use BadMethodCallException;

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
    private function queryJoinBuilder($query, HasOne $relation)
    {
        $relatedModel = $relation->getRelated();
        $relatedKey = $relation->getForeignKey(); // table.key
        $relatedTable = $relatedModel->getTable();

        $parentModel = $relation->getParent();
        $parentTable = $parentModel->getTable();
        $parentKey = $parentTable.'.'.$parentModel->primaryKey; // table.key

        return $query->select($parentTable.'.*')->join($relatedTable, $parentKey, '=', $relatedKey);
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
            $separator = Config::get('columnsortable.uri_relation_column_separator', '.');
            if (str_contains($sort, $separator)) {
                $oneToOneSort = explode($separator, Input::get('sort'));
                if (count($oneToOneSort) !== 2) {
                    throw new InvalidSortArgumentException();
                }

                $relation_name = $oneToOneSort[0];
                $sort = $oneToOneSort[1];

                try {
                    $relation = $query->getRelation($relation_name);
                } catch (BadMethodCallException $e) {
                    throw new RelationDoesNotExistsException($relation_name);
                }

                $query = $this->queryJoinBuilder($query, $relation);
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

        $col = $parameters[0];
        $title = $parameters[1];

        $icon = Config::get('columnsortable.default_icon_set');

        foreach (Config::get('columnsortable.columns') as $key => $value) {
            if (in_array($col, $value['rows'])) {
                $icon = $value['class'];
            }
        }

        if (Input::get('sort') == $col && in_array(Input::get('order'), ['asc', 'desc'])) {
            $icon = $icon.'-'.Input::get('order');
        } else {
            $icon = Config::get('columnsortable.sortable_icon');
        }

        $parameters = [
            'sort' => $col,
            'order' => Input::get('order') === 'desc' ? 'asc' : 'desc',
        ];

        $query_string = http_build_query(array_merge(Request::route()->parameters(), $parameters));

        $anchor_class = Config::get('columnsortable.anchor_class', null);
        if ($anchor_class !== null) {
            $anchor_class = 'class="'.$anchor_class.'"';
        }

        return '<a '.$anchor_class.' href="'.url(Request::path().'?'.$query_string).'"'.'>'.htmlentities($title).'</a>'.' '.'<i class="'.$icon.'"></i>';
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
