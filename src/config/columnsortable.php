<?php
//TODO: comments
return [

    'numeric_columns'  => ['created_at',
                           'updated_at',
                           'id'],

    'amount_columns'   => [],

    'alpha_columns'    => ['name',
                           'description',
                           'email'],

    'numeric_icon_set' => 'fa-sort-numeric',
    'amount_icon_set'  => 'fa-sort-amount',
    'alpha_icon_set'   => 'fa-sort-alpha',

    'default_icon_set' => 'fa-sort',

    /*
    Icon that shows when generating sortable link while column is not sorted.
    */
    'sortable_icon'    => 'fa-sort' 

];