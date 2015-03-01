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

    public static function link(array $parameters) //Extending Blade; Blade sends array.
    {
        if (count($parameters) == 1) $parameters[1] = ucfirst($parameters[0]);

        $col = $parameters[0];
        $title = $parameters[1];

        $numeric_columns = Config::get('columnsortable.numeric_columns');
        $amount_columns = Config::get('columnsortable.amount_columns');
        $alpha_columns = Config::get('columnsortable.alpha_columns');

        $numeric_icon_set = Config::get('columnsortable.numeric_icon_set');
        $amount_icon_set = Config::get('columnsortable.amount_icon_set');
        $alpha_icon_set = Config::get('columnsortable.alpha_icon_set');

        $default_icon_set = Config::get('columnsortable.default_icon_set');
        $sortable_icon = Config::get('columnsortable.sortable_icon');

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