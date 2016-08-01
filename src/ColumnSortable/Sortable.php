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
            $default_array = $this->formatDefaultArray($default);
            if (Config::get('columnsortable.allow_request_modification', true) && !empty($default_array)) {
                Request::merge($default_array);
            }
            return $this->queryOrderBuilder($query, $default_array);
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
        $parentKey = $parentTable . '.' . $parentModel->primaryKey; // table.key

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
            $order = Config::get('columnsortable.default_order', 'asc');
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
        $order = Config::get('columnsortable.default_order', 'asc');
        reset($a);

        if ((bool) count(array_filter(array_keys($a), 'is_string'))) {
            $sort = key($a);
            $order = array_get($a, $sort, $order);
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
            $parameters[1] = $parameters[0];
        }

        $sort = $sortOriginal = $parameters[0];
        $title = $parameters[1];

        $formatting_function = Config::get('columnsortable.formatting_function', null);

        if (!is_null($formatting_function) && function_exists($formatting_function)) {
            $title = call_user_func($formatting_function, $title);
        }

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
            $order = Input::get('order') === 'desc' ? 'asc' : 'desc';
        } else {
            $icon = Config::get('columnsortable.sortable_icon');
            $order = Config::get('columnsortable.default_order_unsorted', 'asc');
        }

        $parameters = [
            'sort' => $sortOriginal,
            'order' => $order,
        ];

        $queryString = http_build_query(array_merge(array_filter(Request::except('sort', 'order', 'page')), $parameters));
        $anchorClass = Config::get('columnsortable.anchor_class', null);
        if ($anchorClass !== null) {
            $anchorClass = 'class="' . $anchorClass . '"';
        }

        $iconAndTextSeparator = Config::get('columnsortable.icon_text_separator', '');

        $clickableIcon = Config::get('columnsortable.clickable_icon', false);
        $trailingTag = $iconAndTextSeparator . '<i class="' . $icon . '"></i>' . '</a>' ;
        if ($clickableIcon === false) {
            $trailingTag = '</a>' . $iconAndTextSeparator . '<i class="' . $icon . '"></i>';
        }

        return '<a ' . $anchorClass . ' href="'. url(Request::path() . '?' . $queryString) . '"' . '>' . htmlentities($title) . $trailingTag;
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
