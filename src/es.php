<?php
namespace GovHawkDC\Boolbuilder\ES;

function getClause($group, $rule)
{
    $condition = isset($group['condition']) ? $group['condition'] : 'AND';
    $condition = strtoupper($condition);
    switch ($condition) {
        case 'OR':
            return 'should';
        case 'AND':
            // Kinda confusing, but, w/ nesting, sometimes $rule can be a "group", so we
            // need to make sure $rule is a "rule" by testing for the absence of nested
            // rules (as a rule terminates; it doesn't have nested sub-rules)
            if (!isset($rule['rules']) && isNegativeOperator($rule)) {
                return 'must_not';
            }
            return 'must';
        default:
            $e = sprintf('Unknown condition "%s"', strval($condition));
            throw new \Exception($e);
    }
}

function getFragment($group, $rule)
{
    $fragment = getFragmentHelper($group, $rule);
    // this is a corner case, when we have an "or" group and a negative operator, we
    // express this with a nested bool query and "must_not"
    $condition = isset($group['condition']) ? $group['condition'] : 'AND';
    $condition = strtoupper($condition);
    if ($condition === 'OR' && isNegativeOperator($rule)) {
        return [
            'bool' => [
                'must_not' => [
                    $fragment
                ]
            ]
        ];
    }
    return $fragment;
}

function getFragmentHelper($group, $rule)
{
    if (isWildcardesque($rule)) {
        if (is_string($rule['value'])) {
            return [
                'wildcard' => [
                    $rule['field'] => $rule['value']
                ]
            ];
        }
        // isWildcardesque makes sure that $rule['value'] is an array in the following
        // format...
        return [
            'wildcard' => [
                $rule['field'] => [
                    'value' => $rule['value'][0],
                    'boost' => floatval($rule['value'][1])
                ]
            ]
        ];
    }

    switch ($rule['operator']) {
        case 'between':
            return [
                'range' => [
                    $rule['field'] => [
                        'gte' => $rule['value'][0],
                        'lte' => $rule['value'][1]
                    ]
                ]
            ];
        case 'contains':
            return [
                'match' => [
                    $rule['field'] => $rule['value']
                ]
            ];
        case 'equal':
        case 'not_equal':
            return [
                'match_phrase' => [
                    $rule['field'] => $rule['value']
                ]
            ];
        case 'greater':
            return [
                'range' => [
                    $rule['field'] => [
                        'gt' => $rule['value']
                    ]
                ]
            ];
        case 'greater_or_equal':
            return [
                'range' => [
                    $rule['field'] => [
                        'gte' => $rule['value']
                    ]
                ]
            ];
        case 'in':
        case 'not_in':
            if (is_string($rule['value'])) {
                return [
                    'terms' => [
                        $rule['field'] => array_map('trim', explode(',', $rule['value']))
                    ]
                ];
            }
            return [
                'terms' => [
                    $rule['field'] => $rule['value']
                ]
            ];
        case 'is_not_null':
        case 'is_null':
            return [
                'exists' => [
                    'field' => $rule['field']
                ]
            ];
        case 'less':
            return [
                'range' => [
                    $rule['field'] => [
                        'lt' => $rule['value']
                    ]
                ]
            ];
        case 'less_or_equal':
            return [
                'range' => [
                    $rule['field'] => [
                        'lte' => $rule['value']
                    ]
                ]
            ];
        case 'proximity':
            return [
                'match_phrase' => [
                    $rule['field'] => [
                        'query' => $rule['value'][0],
                        'slop' => intval($rule['value'][1])
                    ]
                ]
            ];
        default:
            $e = sprintf('Unknown operator "%s"', strval($rule['operator']));
            throw new \Exception($e);
    }
}

function getQuery($fragments)
{
    if (empty($fragments)) {
        return [];
    }
    return [
        'bool' => $fragments
    ];
}

function isBoostesque($rule)
{
    if (!is_array($rule['value']) || count($rule['value']) !== 2) {
        return false;
    }
    // @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html
    if (is_string($rule['value'][0]) && is_numeric($rule['value'][1])) {
        return true;
    }
    return false;
}

function isNegativeOperator($rule)
{
    $negativeOps = [
        'is_null',
        'not_equal',
        'not_in'
    ];
    return in_array($rule['operator'], $negativeOps, true);
}

function isWildcardesque($rule)
{
    if ($rule['type'] !== 'string') {
        return false;
    }
    // The existence operators 'is_not_null' and 'is_null' do not require checking of the
    // value string, so we bypass wildcard pattern check... Proximity is purposely
    // avoided so we don't create a false positive where "slop" is intended
    $skipOps = [
        'is_not_null',
        'is_null',
        'proximity'
    ];
    if (in_array($rule['operator'], $skipOps, true)) {
        return false;
    }
    // Value is a string (e.g., via single text field) and its type is intended to be a string
    $pattern = '/.(\\*|\\?)/';
    if (is_string($rule['value'])) {
        return boolval(preg_match($pattern, $rule['value']));
    }
    // Possible "boost" case; if it appears so, check the query string...
    if (isBoostesque($rule)) {
        return boolval(preg_match($pattern, $rule['value'][0]));
    }
    return false;
}
