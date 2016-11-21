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

        $icon = Config::get('columnsortable.default_icon_set');

        foreach (Config::get('columnsortable.columns') as $value) {
            if (in_array($sortColumn, $value['rows'])) {
                $icon = $value['class'];
            }
        }

        if (Request::get('sort') == $sortParameter && in_array(Request::get('order'), ['asc', 'desc'])) {
            $icon .= (Request::get('order') === 'asc' ? Config::get('columnsortable.asc_suffix',
                '-asc') : Config::get('columnsortable.desc_suffix', '-desc'));
            $direction = Request::get('order') === 'desc' ? 'asc' : 'desc';
        } else {
            $icon = Config::get('columnsortable.sortable_icon');
            $direction = Config::get('columnsortable.default_direction_unsorted', 'asc');
        }

        $iconAndTextSeparator = Config::get('columnsortable.icon_text_separator', '');

        $clickableIcon = Config::get('columnsortable.clickable_icon', false);
        $trailingTag = $iconAndTextSeparator . '<i class="' . $icon . '"></i>' . '</a>';
        if ($clickableIcon === false) {
            $trailingTag = '</a>' . $iconAndTextSeparator . '<i class="' . $icon . '"></i>';
        }

        $anchorClass = self::getAnchorClass();

        $queryString = http_build_query(
            array_merge(
                $queryParameters,
                array_filter(Request::except('sort', 'order', 'page')),
                [
                    'sort' => $sortParameter,
                    'order' => $direction,
                ]
            )
        );

        return '<a' . $anchorClass . ' href="' . url(Request::path() . '?' . $queryString) . '"' . '>' . htmlentities($title) . $trailingTag;
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
        $explodeResult = self::explodeSortParameter($parameters[0]);
        $sortColumn = (empty($explodeResult)) ? $parameters[0] : $explodeResult[1];
        $title = (count($parameters) === 1) ? $sortColumn : $parameters[1];
        $queryParameters = (isset($parameters[2]) && is_array($parameters[2])) ? $parameters[2] : [];

        return [$sortColumn, $parameters[0], $title, $queryParameters];
    }

    /**
     * Explodes parameter if possible and returns array [relation, column]
     * Empty array is returned if explode could not run eg: separator was not found.
     *
     * @param $parameter
     *
     * @return array
     *
     * @throws \Kyslik\ColumnSortable\Exceptions\ColumnSortableException when explode does not produce array of size two
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
        if (!is_null($formatting_function) && function_exists($formatting_function)) {
            $title = call_user_func($formatting_function, $title);
        }
        return $title;
    }

    /**
     * @return string
     */
    private static function getAnchorClass()
    {
        $anchorClass = Config::get('columnsortable.anchor_class', null);
        if ($anchorClass !== null) {
            return ' class="' . $anchorClass . '"';
        }
        return '';
    }
}
