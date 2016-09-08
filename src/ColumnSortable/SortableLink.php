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
     * @return string
     */
    public static function render(array $parameters)
    {
        list($sort, $title) = self::parseParameters($parameters);
        $sortOriginal = $sort;

        unset($parameters);

        $icon = Config::get('columnsortable.default_icon_set');

        if ($oneToOneSort = self::getOneToOneSortOrNull($sort)) {
            $sort = $oneToOneSort[1];
        }

        foreach (Config::get('columnsortable.columns') as $value) {
            if (in_array($sort, $value['rows'])) {
                $icon = $value['class'];
            }
        }

        if (Request::get('sort') == $sortOriginal && in_array(Request::get('order'), ['asc', 'desc'])) {
            $asc_suffix = Config::get('columnsortable.asc_suffix', '-asc');
            $desc_suffix = Config::get('columnsortable.desc_suffix', '-desc');
            $icon = $icon . (Request::get('order') === 'asc' ? $asc_suffix : $desc_suffix);
            $order = Request::get('order') === 'desc' ? 'asc' : 'desc';
        } else {
            $icon = Config::get('columnsortable.sortable_icon');
            $order = Config::get('columnsortable.default_direction_unsorted', 'asc');
        }

        $parameters = [
            'sort' => $sortOriginal,
            'order' => $order,
        ];

        $queryString = http_build_query(array_merge(array_filter(Request::except('sort', 'order', 'page')),
            $parameters));
        $anchorClass = Config::get('columnsortable.anchor_class', null);
        if ($anchorClass !== null) {
            $anchorClass = 'class="' . $anchorClass . '"';
        }

        $iconAndTextSeparator = Config::get('columnsortable.icon_text_separator', '');

        $clickableIcon = Config::get('columnsortable.clickable_icon', false);
        $trailingTag = $iconAndTextSeparator . '<i class="' . $icon . '"></i>' . '</a>';
        if ($clickableIcon === false) {
            $trailingTag = '</a>' . $iconAndTextSeparator . '<i class="' . $icon . '"></i>';
        }

        return '<a ' . $anchorClass . ' href="' . url(Request::path() . '?' . $queryString) . '"' . '>' . htmlentities($title) . $trailingTag;
    }

    /**
     * @param array $parameters
     * @return array
     */
    private static function parseParameters(array $parameters)
    {
        if (count($parameters) === 1) {
            $title = self::getOneToOneSortOrNull($parameters[0]);
            $title = (is_null($title)) ? $parameters[0] : $title[1];
        } else {
            $title = $parameters[1];
        }

        $formatting_function = Config::get('columnsortable.formatting_function', null);
        if (!is_null($formatting_function) && function_exists($formatting_function)) {
            $title = call_user_func($formatting_function, $title);
        }

        return [$parameters[0], $title];
    }

    /**
     * @param $sort
     * @return array|null
     * @throws ColumnSortableException
     */
    public static function getOneToOneSortOrNull($sort)
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
}
