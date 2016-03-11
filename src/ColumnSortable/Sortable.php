<?php

namespace Kyslik\ColumnSortable;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;

use Kyslik\ColumnSortable\Exceptions\ColumnSortableException;
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
            foreach($default as $sort => $order) {
              $query = $query->orderBy($sort, $order);
            }
            return $query;
        } else {
            return $query;
        }
    }

    /**
     * You can use BelongsTo or HasOne in {$relation}
     *
     * @param Builder $query
     * @param array $relation
     *
     * @return relation
     */
    private function queryJoinBuilder($query, $relations)
    {
      $baseQuery = $query;
      $relation = null;

      $query = $query->select($this->getTable() . '.*');

      foreach($relations as $relationName) {

        try {
          $relation = $baseQuery->getRelation($relationName);
        } catch (\Exception $e) {
          throw new ColumnSortableException($relationName, 2, $e);
        }

        $relatedModel = $relation->getRelated();
        $parentModel = $relation->getParent();

        $relatedKey = ($relation instanceof BelongsTo) ? $relation->getQualifiedForeignKey() : $relation->getForeignKey();
        $parentKey = ($relation instanceof BelongsTo) ? $relation->getQualifiedOtherKeyName() : $relation->getQualifiedParentKeyName();

        $query = $query->leftJoin($relatedModel->getTable(), $relatedKey, '=', $parentKey);

        $baseQuery = $relation->getQuery();
      }

      return $relation->getRelated();

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
        $sortList = $this->getSortable();
        $sortKey = array_get($a, 'sort', null);
        $sortName = $sort = array_get($sortList, $sortKey, $sortKey);

        if(!$this->sortExists($this, $sortName)) { //ignore integer sortable keys
          return $query;
        }

        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'asc';
        }

        if (!is_null($sort)) {
            $relations = $this->getSortRelations($sort);

            if (!is_null($relations)) {
              $model = $this->queryJoinBuilder($query, $relations['relations']);
              $sort = $relations['field'];
            }

            $sort = $model->getTable() . '.' . $sort;

            if ($this->sortExists($this, $sortName)) {
                $query = $query->orderBy($sort, $order);
            }

        }

        return $query;

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
    private function getSortRelations($sort)
    {
        $separator = Config::get('columnsortable.uri_relation_column_separator', '.');
        if (str_contains($sort, $separator)) {
          $items = explode($separator, $sort);
          $field = array_pop($items);
          $relations = $items;
          return ['field' => $field, 'relations' => $relations];
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
    private function sortExists($model, $column) {
        return in_array($column, $model->getSortable());
    }
}