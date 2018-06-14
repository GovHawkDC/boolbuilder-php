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

    throw new \Exception(
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
            return isset($rule['operator']) &&
            isNegativeOperator($rule['operator'])
                ? 'must_not'
                : 'must';
        default:
            throw new \Exception(
                sprintf(
                    'Unable to build ES bool query with condition: "%s"',
                    $condition
                )
            );
    }
}

function getFragment($rule)
{
    $field = $rule['field'];

    switch ($rule['operator']) {
        case 'is_not_null':
        case 'is_null':
            return ['exists' => ['field' => $field]];
        default:
            return [getOperator($rule) => [$field => getValue($rule)]];
    }
}

function getValue($rule)
{
    $operator = $rule['operator'];
    $value = $rule['value'];

    switch ($operator) {
        case 'between':
            return ['gte' => $value[0], 'lte' => $value[1]];
        case 'contains':
        case 'equal':
        case 'is_not_null':
        case 'is_null':
        case 'not_equal':
            return $value;
        case 'greater':
            return ['gt' => $value];
        case 'greater_or_equal':
            return ['gte' => $value];
        case 'in':
        case 'not_in':
            return getArrayValue($value);
        case 'less':
            return ['lt' => $value];
        case 'less_or_equal':
            return ['lte' => $value];
        case 'proximity':
            return ['query' => $value[0], 'slop' => $value[1]];
        default:
            throw new \Exception(
                sprintf(
                    'Unable to build ES bool query with operator: "%s"',
                    $operator
                )
            );
    }
}

function getOperator($rule)
{
    // NOTE: Using `json_encode` here to stringify any type of value that
    // is passed, since it can be a string, array, etc.
    if (preg_match('/.(\\*|\\?)/', json_encode($rule['value']))) {

        return 'wildcard';
    }

    switch ($rule['operator']) {
        case 'contains':
            return 'match';
        case 'equal':
        case 'not_equal':
        case 'proximity':
            return 'match_phrase';
        case 'in':
        case 'not_in':
            return 'terms';
        default:
            return 'range';
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

function isWildcardesqueRule($rule)
{
    // Don't want to step on toes of explicit case where "slop" is intended
    if ($rule['operator'] === 'proximity') {

        return false;
    }

    $isStringType = boolval(isset($rule['type']) && $rule['type'] === 'string');

    if (!$isStringType) {
        return false;
    }

    $v = $rule['value'];

    // Value is a string (e.g., via single text field) and its type
    // is intended to be a string
    if (is_string($v)) {

        return true;
    }

    // Covers "boost" case
    // @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html
    if (
        is_array($v) && count($v) === 2 && is_string($v[0]) && is_numeric($v[1])
    ) {

        return true;
    }

    return false;
}
