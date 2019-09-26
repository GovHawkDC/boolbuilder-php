<?php
namespace GovHawkDC\Boolbuilder;

use GovHawkDC\Boolbuilder\ES;

const ALL_TYPES = '*';
const DEFAULT_QB_GROUP = 'QBGroup';
const NESTED_TYPE_HANDLING_ALLOW = 'allow';
const NESTED_TYPE_HANDLING_CONDITIONAL = 'conditional';
const NESTED_TYPE_HANDLING_DENY = 'deny';
const NESTED_TYPE_HANDLING_EMPTY = 'empty';

function handleGroup($group, $options, $parentQB)
{
    // No user funcs for default "QB"
    if (!isset($group['QB']) || $group['QB'] === DEFAULT_QB_GROUP) {
        // Current $group w/ default "QB" inherits $parentQB
        $group['QB'] = $parentQB;
        return $group;
    }
    // No user funcs for repeating "QB" (e.g., child $group with same "QB")
    if ($group['QB'] === $parentQB) {
        return $group;
    }
    // Transition from default ancestor "groups" to non-default "QB"
    if ($parentQB === DEFAULT_QB_GROUP) {
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
        case NESTED_TYPE_HANDLING_CONDITIONAL:
            if (isset($options['nestedTypeTransitionMap'][$parentQB])) {
                $allowedTypes = $options['nestedTypeTransitionMap'][$parentQB];
            } elseif (isset($options['nestedTypeTransitionMap'][ALL_TYPES])) {
                $allowedTypes = $options['nestedTypeTransitionMap'][ALL_TYPES];
            } else {
                $allowedTypes = [];
            }
            $checkTypes = [ALL_TYPES, $group['QB']];
            $matches = array_intersect($checkTypes, $allowedTypes);
            if (empty($matches)) {
                return [];
            }
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
    if (in_array($rule['field'], $options['excludeFields'], true)) {
        return true;
    }
    if (in_array($rule['operator'], $options['excludeOperators'], true)) {
        return true;
    }
    return false;
}

function transform($group, $options = [], $maxDepth = 24)
{
    $defaults = [];
    $defaults['excludeFields'] = [];
    $defaults['excludeOperators'] = [];
    $defaults['nestedTypeHandling'] = NESTED_TYPE_HANDLING_DENY;
    $defaults['nestedTypeTransitionMap'] = [];
    $options = array_merge($defaults, $options);
    return transformGroup($group, $options, DEFAULT_QB_GROUP, 0, $maxDepth);
}

function transformGroup($group, $options, $parentQB, $depth, $maxDepth)
{
    if ($depth > $maxDepth) {
        throw new \Exception('Max depth exceeded');
    }
    $group = handleGroup($group, $options, $parentQB);
    if (empty($group['rules'])) {
        return [];
    }
    return transformRules($group, $options, $depth, $maxDepth);
}

function transformRule($group, $rule, $options, $depth, $maxDepth)
{
    if (isset($rule['rules'])) {
        return transformGroup($rule, $options, $group['QB'], $depth + 1, $maxDepth);
    }
    if (isRuleExcluded($group, $rule, $options)) {
        return [];
    }
    return ES\getQuery($group, $rule);
}

function transformRules($group, $options, $depth, $maxDepth)
{
    $clauses = [];
    foreach ($group['rules'] as $rule) {
        $rule = handleRule($group, $rule, $options);
        $query = transformRule($group, $rule, $options, $depth, $maxDepth);
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
