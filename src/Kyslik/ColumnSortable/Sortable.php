<?php namespace Kyslik\ColumnSortable;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Class Sortable
 * @package Kyslik\ColumnSortable
 */
trait Sortable
{

    /**
     * @param $query
     * @return mixed
     */
    public function scopeSortable($query)
    {

        if (Input::has('sort') && Input::has('order') && $this->columnExists(Input::get('sort')))
            return $query->orderBy(Input::get('sort'), Input::get('order'));
        else
            return $query;
    }

    /**
     * @param array $parameters
     * @return string
     */
    public static function link(array $parameters) //Extending Blade; Blade sends array.
    {
        if (count($parameters) === 1) $parameters[1] = ucfirst($parameters[0]);

        $col = $parameters[0];
        $title = $parameters[1];

        $icon = Config::get('columnsortable.sortable_icon');

        foreach (Config::get('columnsortable.columns') as $key => $value) {
            if(in_array($col, $value['rows'])) {
                $icon = $value['class'];
            }
        }

        if (Input::get('sort') == $col && in_array(Input::get('order'), ['asc', 'desc']))
            $icon = $icon . '-' . Input::get('order');
        else
            $icon = Config::get('columnsortable.sortable_icon');

        $parameters = [
            'sort' => $col,
            'order' => Input::get('order') === 'asc' ? 'desc' : 'asc'
        ];

        parse_str(\Request::getQueryString(),$get_array);
        $url = route(Request::route()->getName(), array_merge($get_array, $parameters));
        return '<a href="' . $url . '"' . '>' . htmlentities($title) . '</a>' . ' ' . '<i class="' . $icon . '"></i>';
    }


    /**
     * @param $column
     * @return bool
     */
    private function columnExists($column)
    {
        if (!isset($this->sortable))
            return Schema::hasColumn($this->getTable(), $column);
        else
            return in_array($column, $this->sortable);
    }

}
