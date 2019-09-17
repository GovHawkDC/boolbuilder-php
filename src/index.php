<?php
namespace GovHawkDC\Boolbuilder;

use GovHawkDC\Boolbuilder\ES;

const NESTED_TYPE_HANDLING_ALLOW = 'allow';
const NESTED_TYPE_HANDLING_DENY = 'deny';
const NESTED_TYPE_HANDLING_EMPTY = 'empty';
const DEFAULT_QB = 'QBGroup';

function handleGroup($group, $options, $parentQB)
{
    // No user funcs for default "QB"
    if (!isset($group['QB']) || $group['QB'] === DEFAULT_QB) {
        // Current $group w/ default "QB" inherits $parentQB
        $group['QB'] = $parentQB;
        return $group;
    }
    // No user funcs for repeating "QB" (e.g., child $group with same "QB")
    if ($group['QB'] === $parentQB) {
        return $group;
    }
    // Transition from default ancestor "groups" to non-default "QB"
    if ($parentQB === DEFAULT_QB) {
        if (isset($options['typeFuncMap'][$group['QB']])) {
            $userFunc = $options['typeFuncMap'][$group['QB']];
            return $userFunc($group, $options);
        }
        return $group;
    }
    // Transition from non-default ancestor "groups" to a different non-default "QB"
    switch ($options['nestedTypeHandling']) {
        case NESTED_TYPE_HANDLING_ALLOW:
            if (isset($options['typeFuncMap'][$group['QB']])) {
                $userFunc = $options['typeFuncMap'][$group['QB']];
                return $userFunc($group, $options);
            }
            return $group;
        case NESTED_TYPE_HANDLING_DENY:
            throw new \Exception('Unable to process nested group of different custom type');
        case NESTED_TYPE_HANDLING_EMPTY:
            return [];
        default:
            throw new \Exception('Unknown nestedTypeHandling option value');
    }
}

function handleRule($group, $rule, $options)
{
    // We do not want to attempt to process a nested "group" as a "rule"
    if (isset($rule['rules'])) {
        return $rule;
    }
    // Apply user func to $rule if available
    if (isset($options['ruleFuncMap'][$group['QB']][$rule['field']])) {
        $userFunc = $options['ruleFuncMap'][$group['QB']][$rule['field']];
        return $userFunc($group, $rule, $options);
    }
    return $rule;
}

function isRuleExcluded($group, $rule, $options)
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

function transform($group, $options = [])
{
    $defaults = [];
    $defaults['nestedTypeHandling'] = NESTED_TYPE_HANDLING_DENY;
    $options = array_merge($defaults, $options);
    return transformGroup($group, $options, DEFAULT_QB);
}

function transformGroup($group, $options, $parentQB)
{
    $group = handleGroup($group, $options, $parentQB);
    if (empty($group['rules'])) {
        return [];
    }
    return transformRules($group, $options);
}

function transformRule($group, $rule, $options)
{
    if (isset($rule['rules'])) {
        return transformGroup($rule, $options, $group['QB']);
    }
    if (isRuleExcluded($group, $rule, $options)) {
        return [];
    }
    return ES\getQuery($group, $rule);
}

function transformRules($group, $options)
{
    $clauses = [];
    foreach ($group['rules'] as $rule) {
        $rule = handleRule($group, $rule, $options);
        $query = transformRule($group, $rule, $options);
        if (empty($query)) {
            continue;
        }
        $clause = ES\getClause($group, $rule);
        if (!isset($clauses[$clause])) {
            $clauses[$clause] = [];
        }
        $clauses[$clause][] = $query;
    }
    return ES\getCompoundQuery($clauses);
}
