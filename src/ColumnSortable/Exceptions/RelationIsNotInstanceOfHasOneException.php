<?php

namespace Kyslik\ColumnSortable\Exceptions;

use Exception;

class RelationIsNotInstanceOfHasOne extends Exception
{
    public function __construct($relationName, $code = 0, Exception $previous = null)
    {
        $message = 'Relation \'' . $relationName . '\' is not instance of Illuminate\Database\Eloquent\Relations\HasOne.';
        parent::__construct($message, $code, $previous);
    }
}
