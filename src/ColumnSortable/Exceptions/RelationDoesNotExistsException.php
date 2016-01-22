<?php

namespace Kyslik\ColumnSortable\Exceptions;

use Exception;

class RelationDoesNotExistsException extends Exception
{
    public function __construct($relation_name, $code = 0, Exception $previous = null)
    {
        $message = 'Sorry, relation \''. $relation_name . '\' does not exist. Define it in your model.';
        parent::__construct($message, $code, $previous);
    }
}
