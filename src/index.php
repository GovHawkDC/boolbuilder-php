<?php
namespace GovHawkDC\Boolbuilder;

use GovHawkDC\Boolbuilder\ES;

function transform($group, $options = [])
{
    if (!$group) {
        return [];
    }

    $QB = isset($group['QB']) ? $group['QB'] : '';
    $rules = isset($group['rules']) ? $group['rules'] : [];
    if (count($rules) < 1) {
        return [];
    }

    // Allow user functions to handle specific data types (e.g., change the default output)
    // while processing
    if (isset($options['typeMapFuncs'][$QB])) {
        $userFunc = $options['typeMapFuncs'][$QB];
        $nextFunc = __NAMESPACE__ . '\\transformGroup';

        $t = $userFunc($group, $rules, $options, $nextFunc);
        if (empty($t)) {
            return [];
        }

        return ['bool' => $t];
    }

    $t = transformGroup($group, $rules, $options);
    if (empty($t)) {
        return [];
    }

    return ['bool' => $t];
}

function transformGroup($group, $rules, $options)
{
    return array_reduce(
        $rules,
        function ($carry, $rule) use ($group, $options) {
            $clause = ES\getClause($group, $rule);
            $fragment = transformRule($group, $rule, $options);
            if (empty($fragment)) {
                return $carry;
            }

            $existingFragments = isset($carry[$clause])
                ? $carry[$clause]
                : [];
            return array_merge($carry, [
                $clause => array_merge($existingFragments, [$fragment])
            ]);
        },
        []
    );
}

function transformRule($group, $rule, $options)
{
    $condition = isset($group['condition']) ? $group['condition'] : '';
    $operator = isset($rule['operator']) ? $rule['operator'] : '';
    $rules = isset($rule['rules']) ? $rule['rules'] : [];

    if (count($rules) > 0) {
        return transform($rule, $options);
    }

    if (isRuleExcluded($rule, $options)) {
        return [];
    }

    if (isset($options['ruleFiltersPre'][$rule['field']])) {
        $userFunc = $options['ruleFiltersPre'][$rule['field']];
        $fragment = ES\getFragment($userFunc($rule));
    } else {
        $fragment = ES\getFragment($rule);
    }

    // this is a corner case, when we have an "or" group and a
    // negative operator, we express this with a sub boolean
    // query and must_not
    if (strtoupper($condition) === 'OR' &&
        ES\isNegativeOperator($operator)
    ) {
        return ['bool' => ['must_not' => [$fragment]]];
    }

    return $fragment;
}

function isRuleExcluded($rule, $options)
{
    if (isset($options['includeFields']) &&
        !in_array($rule['field'], $options['includeFields'], true)
    ) {
        return true;
    }

    if (isset($options['excludeFields']) &&
        in_array($rule['field'], $options['excludeFields'], true)
    ) {
        return true;
    }

    if (isset($options['excludeOperators']) &&
        in_array($rule['operator'], $options['excludeOperators'], true)
    ) {
        return true;
    }

    return false;
}
