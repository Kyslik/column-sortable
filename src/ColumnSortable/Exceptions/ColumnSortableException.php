<?php

namespace Kyslik\ColumnSortable\Exceptions;

use Exception;

class ColumnSortableException extends Exception
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        if ($code === 1) {
            $message = 'Relation \''. $message . '\' does not exist. Define it in your model.';
        } elseif ($code === 2) {
            $message = 'Relation \'' . $message . '\' is not instance of HasOne, HasMany or BelongsTo.';
        } else {
            $message = 'Invalid sort argument, explode() did not produce array with size of 2 elements.';
        }

        parent::__construct($message, $code, $previous);
    }
}
