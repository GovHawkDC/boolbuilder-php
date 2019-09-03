<?php
namespace GovHawkDC\Boolbuilder\ES;

function getArrayValue($value)
{
    if (is_array($value)) {
        return $value;
    }

    if (is_string($value)) {
        return array_map('trim', explode(',', $value));
    }

    $e = sprintf('Unable to build ES bool query with value type: "%s"', gettype($value));
    throw new \Exception($e);
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
            $e = sprintf('Unable to build ES bool query with condition: "%s"', $condition);
            throw new \Exception($e);
    }
}

function getFragment($rule)
{
    switch ($rule['operator']) {
        case 'is_not_null':
        case 'is_null':
            return ['exists' => ['field' => $rule['field']]];
        default:
            $esOperator = getOperator($rule);
            return [$esOperator => [$rule['field'] => getValue($rule, $esOperator)]];
    }
}

function getValue($rule, $esOperator)
{
    if ($esOperator === 'wildcard') {
        return is_string($rule['value'])
            ? $rule['value']
            : ['value' => $rule['value'][0], 'boost' => floatval($rule['value'][1])];
    }

    switch ($rule['operator']) {
        case 'between':
            return [
                'gte' => $rule['value'][0],
                'lte' => $rule['value'][1]
            ];
        case 'contains':
        case 'equal':
        case 'is_not_null':
        case 'is_null':
        case 'not_equal':
            return $rule['value'];
        case 'greater':
            return ['gt' => $rule['value']];
        case 'greater_or_equal':
            return ['gte' => $rule['value']];
        case 'in':
        case 'not_in':
            return getArrayValue($rule['value']);
        case 'less':
            return ['lt' => $rule['value']];
        case 'less_or_equal':
            return ['lte' => $rule['value']];
        case 'proximity':
            return [
                'query' => $rule['value'][0],
                'slop' => intval($rule['value'][1])
            ];
        default:
            $e = sprintf('Unable to build ES bool query with operator: "%s"', $rule['operator']);
            throw new \Exception($e);
    }
}

function getOperator($rule)
{
    // NOTE: Using `json_encode` here to stringify any type of value that
    // is passed, since it can be a string, array, etc.
    if (isWildcardesqueRule($rule) &&
        preg_match('/.(\\*|\\?)/', json_encode($rule['value']))) {
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
    if (isset($rule['operator']) && $rule['operator'] === 'proximity') {
        return false;
    }

    if (isset($rule['type']) && $rule['type'] !== 'string') {
        return false;
    }

    // Value is a string (e.g., via single text field) and its type is intended to be a string
    if (is_string($rule['value'])) {
        return true;
    }

    // Covers "boost" case
    // @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html
    if (is_array($rule['value']) &&
        count($rule['value']) === 2 &&
        is_string($rule['value'][0]) &&
        is_numeric($rule['value'][1])) {
        return true;
    }

    return false;
}
