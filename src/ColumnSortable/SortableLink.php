<?php

namespace Kyslik\ColumnSortable;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Kyslik\ColumnSortable\Exceptions\ColumnSortableException;

/**
 * Class SortableLink
 * @package Kyslik\ColumnSortable
 */
class SortableLink
{

    /**
     * @param array $parameters
     *
     * @return string
     */
    public static function render(array $parameters)
    {
        list($sortColumn, $sortParameter, $title, $queryParameters) = self::parseParameters($parameters);

        $title = self::applyFormatting($title);

        if ($mergeTitleAs = Config::get('columnsortable.inject_title_as', null)) {
            Request::merge([$mergeTitleAs => $title]);
        }

        list($icon, $direction) = self::determineDirection($sortColumn, $sortParameter);

        $trailingTag = self::formTrailingTag($icon);

        $anchorClass = self::getAnchorClass($sortParameter);

        $queryString = self::buildQueryString($queryParameters, $sortParameter, $direction);

        return '<a'.$anchorClass.' href="'.url(Request::path().'?'.$queryString).'"'.'>'.htmlentities($title).$trailingTag;
    }


    /**
     * @param array $parameters
     *
     * @return array
     */
    public static function parseParameters(array $parameters)
    {
        //TODO: let 2nd parameter be both title, or default query parameters
        //TODO: needs some checks before determining $title
        $explodeResult   = self::explodeSortParameter($parameters[0]);
        $sortColumn      = (empty($explodeResult)) ? $parameters[0] : $explodeResult[1];
        $title           = (count($parameters) === 1) ? $sortColumn : $parameters[1];
        $queryParameters = (isset($parameters[2]) && is_array($parameters[2])) ? $parameters[2] : [];

        return [$sortColumn, $parameters[0], $title, $queryParameters];
    }


    /**
     * Explodes parameter if possible and returns array [column, relation]
     * Empty array is returned if explode could not run eg: separator was not found.
     *
     * @param $parameter
     *
     * @return array
     *
     * @throws \Kyslik\ColumnSortable\Exceptions\ColumnSortableException
     */
    public static function explodeSortParameter($parameter)
    {
        $separator = Config::get('columnsortable.uri_relation_column_separator', '.');

        if (str_contains($parameter, $separator)) {
            $oneToOneSort = explode($separator, $parameter);
            if (count($oneToOneSort) !== 2) {
                throw new ColumnSortableException();
            }

            return $oneToOneSort;
        }

        //TODO: should return ['column', 'relation']
        return [];
    }


    /**
     * @param string $title
     *
     * @return string
     */
    private static function applyFormatting($title)
    {
        $formatting_function = Config::get('columnsortable.formatting_function', null);
        if ( ! is_null($formatting_function) && function_exists($formatting_function)) {
            $title = call_user_func($formatting_function, $title);
        }

        return $title;
    }


    /**
     * @param $sortColumn
     * @param $sortParameter
     *
     * @return array
     */
    private static function determineDirection($sortColumn, $sortParameter)
    {
        $icon = self::selectIcon($sortColumn);

        if (Request::get('sort') == $sortParameter && in_array(Request::get('order'), ['asc', 'desc'])) {
            $icon      .= (Request::get('order') === 'asc' ? Config::get('columnsortable.asc_suffix', '-asc') :
                Config::get('columnsortable.desc_suffix', '-desc'));
            $direction = Request::get('order') === 'desc' ? 'asc' : 'desc';

            return [$icon, $direction];
        } else {
            $icon      = Config::get('columnsortable.sortable_icon');
            $direction = Config::get('columnsortable.default_direction_unsorted', 'asc');

            return [$icon, $direction];
        }
    }


    /**
     * @param $sortColumn
     *
     * @return string
     */
    private static function selectIcon($sortColumn)
    {
        $icon = Config::get('columnsortable.default_icon_set');

        foreach (Config::get('columnsortable.columns', []) as $value) {
            if (in_array($sortColumn, $value['rows'])) {
                $icon = $value['class'];
            }
        }

        return $icon;
    }


    /**
     * @param $icon
     *
     * @return string
     */
    private static function formTrailingTag($icon)
    {
        if ( ! Config::get('columnsortable.enable_icons', true)) {
            return '</a>';
        }

        $iconAndTextSeparator = Config::get('columnsortable.icon_text_separator', '');

        $clickableIcon = Config::get('columnsortable.clickable_icon', false);
        $trailingTag   = $iconAndTextSeparator.'<i class="'.$icon.'"></i>'.'</a>';

        if ($clickableIcon === false) {
            $trailingTag = '</a>'.$iconAndTextSeparator.'<i class="'.$icon.'"></i>';

            return $trailingTag;
        }

        return $trailingTag;
    }


    /**
     * @param $sortColumn
     *
     * @return string
     */
    private static function getAnchorClass($sortColumn)
    {
        $class = [];

        $anchorClass = Config::get('columnsortable.anchor_class', null);
        if ($anchorClass !== null) {
            $class[] = $anchorClass;
        }

        $activeClass = Config::get('columnsortable.active_anchor_class', null);
        if ($activeClass !== null && self::shouldShowActive($sortColumn)) {
            $class[] = $activeClass;
        }

        $orderClassPrefix = Config::get('columnsortable.order_anchor_class_prefix', null);
        if ($orderClassPrefix !== null && self::shouldShowActive($sortColumn)) {
            $class[] =
                $orderClassPrefix.(Request::get('order') === 'asc' ? Config::get('columnsortable.asc_suffix', '-asc') :
                    Config::get('columnsortable.desc_suffix', '-desc'));
        }

        return (empty($class)) ? '' : ' class="'.implode(' ', $class).'"';
    }


    /**
     * @param $sortColumn
     *
     * @return boolean
     */
    private static function shouldShowActive($sortColumn)
    {
        return Request::has('sort') && Request::get('sort') == $sortColumn;
    }


    /**
     * @param $queryParameters
     * @param $sortParameter
     * @param $direction
     *
     * @return string
     */
    private static function buildQueryString($queryParameters, $sortParameter, $direction)
    {
        $checkStrlenOrArray = function ($element) {
            return is_array($element) ? $element : strlen($element);
        };

        $persistParameters = array_filter(Request::except('sort', 'order', 'page'), $checkStrlenOrArray);
        $queryString       = http_build_query(array_merge($queryParameters, $persistParameters, [
            'sort'  => $sortParameter,
            'order' => $direction,
        ]));

        return $queryString;
    }
}
