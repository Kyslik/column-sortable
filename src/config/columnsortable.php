<?php

return [

    /*
    Spec columns
    */
    'columns' => [
        'numeric_columns'  => [
            'rows' => ['created_at', 'updated_at', 'level', 'id'],
            'class' => 'fa fa-sort-numeric'
        ],
        'amount_columns'   => [
            'rows' => ['price'],
            'class' => 'fa fa-sort-amount'
        ],
        'alpha_columns'    => [
            'rows' => ['name', 'description', 'email', 'slug'],
            'class' => 'fa fa-sort-alpha',
        ],
    ],

    /*
    Defines icon set to use when sorted data is none above.
    */
    'default_icon_set' => 'fa fa-sort',

    /*
    Icon that shows when generating sortable link while column is not sorted.
    */
    'sortable_icon'    => 'fa fa-sort'

];