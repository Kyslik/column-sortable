<?php namespace Kyslik\ColumnSortable;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;


trait Sortable {

    public function scopeSortable($query)
    {
        if ((Input::has('sort') && Input::has('order')) && (Schema::hasColumn($this->getTable(), Input::get('sort')))) return $query->orderBy(Input::get('sort'), Input::get('order'));
        else
            return $query;
    }

    public static function link(array $parameters) //sending array because of blade extension
    {
        if (count($parameters) == 1) $parameters[1] = ucfirst($parameters[0]);

        $col = $parameters[0];
        $title = $parameters[1];

        $numeric_columns = array(config('columnsortable.numeric_columns'));
        $amount_columns = array(config('columnsortable.amount_columns'));
        $alpha_columns = array(config('columnsortable.alpha_columns'));

        $numeric_icon_set = config('columnsortable.numeric_columns');
        $amount_icon_set = config('columnsortable.amount_icon_set');
        $alpha_icon_set = config('columnsortable.alpha_icon_set');

        $default_icon_set = config('columnsortable.default_icon_set');
        $sortable_icon = config('columnsortable.sortable_icon'); //icon that shows when sortable but column is not sorted at the time

        if (Input::get('sort') == $col)
        {
            if (in_array(Input::get('sort'), $numeric_columns)) $icon = $numeric_icon_set;
            elseif (in_array(Input::get('sort'), $amount_columns)) $icon = $amount_icon_set;
            elseif (in_array(Input::get('sort'), $alpha_columns)) $icon = $alpha_icon_set;
            else $icon = $default_icon_set;

            $icon = $icon . '-' . Input::get('order');
        }
        else
            $icon = $sortable_icon;

        $icon = '<i class="fa ' . $icon . '"></i>';

        $parameters = array_merge(Input::all(), array('sort'  => $col,
                                                      'order' => (Input::get('order') === 'asc' ? 'desc' : 'asc')));
        $url = route(Route::currentRouteName(), $parameters);

        return '<a href="' . $url . '"' . '>' . htmlentities($title) . '</a>' . ' ' . $icon;
    }


}