<?php
namespace Boolbuilder\ES;

function getArrayValue($value)
{
    if (is_array($value)) {
        return $value;
    }

    if (is_string($value)) {
        return array_map('trim', explode(',', $value));
    }

    throw new Exception(
        sprintf(
            'Unable to build ES bool query with value type: "%s"',
            gettype($value)
        )
    );
}
