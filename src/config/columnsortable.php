<?php

return [


    /*
    Array that defines columns with numerical data. 
    These columns are going to have 'numeric_icon_set' next to sortable link.
    */
    'numeric_columns'  => ['created_at',
                           'updated_at',
                           'level',
                           'id'],


    /*
    Array that defines columns with amount data. 
    These columns are going to have 'amount_icon_set' next to sortable link.
    */
    'amount_columns'   => [],


    /*
    Array that defines columns with amount data. 
    These columns are going to have 'alpha_icon_set' next to sortable link.
    */
    'alpha_columns'    => ['name',
                           'descrtiption',
                           'email',
                           'slug'],


    /*
    Before implementing your own classes (not Font Awesome),
    note that full class names are ending with either 'asc' or 'desc'
    e.g.: 'fa fa-sort-numeric-desc'. Order (asc|desc) is appended automatically.
    */


    /*
    Defines icon set to use when sorted data is numerical.
    */
    'numeric_icon_set' => 'fa fa-sort-numeric',


    /*
    Defines icon set to use when sorted data is amount.
    */
    'amount_icon_set'  => 'fa fa-sort-amount',


    /*
    Defines icon set to use when sorted data is alphabetical.
    */
    'alpha_icon_set'   => 'fa fa-sort-alpha',


    /*
    Defines icon set to use when sorted data is none above.
    */
    'default_icon_set' => 'fa fa-sort',

    /*
    Icon that shows when generating sortable link while column is not sorted.
    */
    'sortable_icon'    => 'fa fa-sort' 

];