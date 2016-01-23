<?php

return [

    /*
    Spec columns.
    */
    'columns' => [
        'alpha_columns'    => [
            'rows' => ['description', 'email', 'name', 'slug'],
            'class' => 'fa fa-sort-alpha',
        ],
        'amount_columns'   => [
            'rows' => ['amount', 'price'],
            'class' => 'fa fa-sort-amount'
        ],
        'numeric_columns'  => [
            'rows' => ['created_at', 'updated_at', 'level', 'id', 'phone_number'],
            'class' => 'fa fa-sort-numeric'
        ],
    ],

    /*
    Defines icon set to use when sorted data is none above.
    */
    'default_icon_set' => 'fa fa-sort',

    /*
    Icon that shows when generating sortable link while column is not sorted.
    */
    'sortable_icon'    => 'fa fa-sort',

    /*
    Default anchor class, if value is null none is added. (must be type of null)
    */
    'anchor_class'      => null,

    /*
    Relation - column separator. ex: detail.phone_number means relation "detail" and column "phone_number".
     */
    'uri_relation_column_separator' => '.'

];
