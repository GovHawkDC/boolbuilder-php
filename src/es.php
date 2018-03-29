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

function getClause($group, $rule)
{
    $condition = isset($group['condition']) ? $group['condition'] : 'AND';

    switch (strtoupper($condition)) {
        case 'OR':
            return 'should';
        case 'AND':
            return isNegativeOperator($rule['operator']) ? 'must_not' : 'must';
        default:
            throw new Exception(
                "Unable to build ES bool query with condition: "$condition""
            );
    }
}

function isNegativeOperator($operator)
{
    switch ($operator) {
        case 'is_null':
        case 'not_equal':
        case 'not_in':
            return true;
        default:
            return false;
    }
}
