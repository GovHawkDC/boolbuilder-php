<?php
namespace GovHawkDC\Boolbuilder;

use GovHawkDC\Boolbuilder\ES;

const ALL_RULES = '*';
const ALL_TYPES = '*';
const DEFAULT_QB_GROUP = 'QBGroup';
const NESTED_TYPE_HANDLING_ALLOW = 'allow';
const NESTED_TYPE_HANDLING_CONDITIONAL = 'conditional';
const NESTED_TYPE_HANDLING_DENY = 'deny';
const NESTED_TYPE_HANDLING_EMPTY = 'empty';

function handleGroup($group, $options, $context)
{
    // No user funcs for default "QB"
    if (!isset($group['QB']) || $group['QB'] === DEFAULT_QB_GROUP) {
        // Current $group w/ default "QB" inherits parent's "QB"
        $group['QB'] = $context['$ref']['$parent']['QB'];
        return [$group, $context];
    }
    // No user funcs for repeating "QB" (e.g., child $group with same "QB")
    if ($group['QB'] === $context['$ref']['$parent']['QB']) {
        return [$group, $context];
    }
    // Transition from default ancestor "groups" to non-default "QB"
    if ($context['$ref']['$parent']['QB'] === DEFAULT_QB_GROUP) {
        return applyTypeFunc($group, $options, $context);
    }
    // Transition from non-default ancestor "groups" to a different non-default "QB"
    switch ($options['nestedTypeHandling']) {
        case NESTED_TYPE_HANDLING_ALLOW:
            return applyTypeFunc($group, $options, $context);
        case NESTED_TYPE_HANDLING_CONDITIONAL:
            if (isset($options['nestedTypeTransitionMap'][$context['$ref']['$parent']['QB']])) {
                $allowedTypes = $options['nestedTypeTransitionMap'][$context['$ref']['$parent']['QB']];
            } elseif (isset($options['nestedTypeTransitionMap'][ALL_TYPES])) {
                $allowedTypes = $options['nestedTypeTransitionMap'][ALL_TYPES];
            } else {
                $allowedTypes = [];
            }
            $checkTypes = [ALL_TYPES, $group['QB']];
            $matches = array_intersect($checkTypes, $allowedTypes);
            if (empty($matches)) {
                return [[], $context];
            }
            return applyTypeFunc($group, $options, $context);
        case NESTED_TYPE_HANDLING_DENY:
            throw new \Exception('Unable to process nested group of different custom type');
        case NESTED_TYPE_HANDLING_EMPTY:
            return [[], $context];
        default:
            throw new \Exception('Unknown nestedTypeHandling option value');
    }
}

function applyTypeFunc($group, $options, $context)
{
    if (empty($group['rules'])) {
        return [$group, $context];
    }
    if (isset($options['typeFuncMap'][$group['QB']])) {
        $userFunc = $options['typeFuncMap'][$group['QB']];
        return $userFunc($group, $options, $context);
    }
    return [$group, $context];
}

function handleRule($group, $rule, $options, $context)
{
    // We do not want to attempt to process a nested "group" as a "rule"
    if (isset($rule['rules'])) {
        return [$rule, $context];
    }
    // Apply user func for field on any type if available.
    if (isset($options['ruleFuncMap'][ALL_TYPES][$rule['field']])) {
        $userFunc = $options['ruleFuncMap'][ALL_TYPES][$rule['field']];
        return $userFunc($group, $rule, $options, $context);
    }
    // Apply user func to $rule if available
    if (isset($options['ruleFuncMap'][$group['QB']][$rule['field']])) {
        $userFunc = $options['ruleFuncMap'][$group['QB']][$rule['field']];
        return $userFunc($group, $rule, $options, $context);
    }
    // Apply user func that applies to any $rule in a specific QB type.
    if (isset($options['ruleFuncMap'][$group['QB']][ALL_RULES])) {
        $userFunc = $options['ruleFuncMap'][$group['QB']][ALL_RULES];
        return $userFunc($group, $rule, $options, $context);
    }
    return [$rule, $context];
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
    $parent = [];
    $parent['QB'] = DEFAULT_QB_GROUP;
    $parent['condition'] = 'OR';
    $parent['rules'] = [$group];
    $context = [];
    return transformGroup($group, $options, $parent, $context, 0, $maxDepth);
}

function transformGroup($group, $options, $parent, $context, $depth, $maxDepth)
{
    if ($depth > $maxDepth) {
        throw new \Exception('Max depth exceeded');
    }
    $ref = empty($context['$ref']) ? [] : $context['$ref'];
    $context['$ref'] = [];
    $context['$ref']['$parent'] = $parent;
    $context['$ref']['$ref'] = empty($ref) ? $context['$ref'] : $ref;
    list($group, $context) = handleGroup($group, $options, $context);
    if (empty($group['rules'])) {
        return [];
    }
    return transformRules($group, $options, $context, $depth, $maxDepth);
}

function transformRule($group, $rule, $options, $context, $depth, $maxDepth)
{
    if (isset($rule['rules'])) {
        return transformGroup($rule, $options, $group, $context, $depth + 1, $maxDepth);
    }
    if (isRuleExcluded($group, $rule, $options)) {
        return [];
    }
    return ES\getQuery($group, $rule);
}

function transformRules($group, $options, $context, $depth, $maxDepth)
{
    $clauses = [];
    foreach ($group['rules'] as $rule) {
        list($rule, $context) = handleRule($group, $rule, $options, $context);
        if (empty($rule)) {
            continue;
        }
        $query = transformRule($group, $rule, $options, $context, $depth, $maxDepth);
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
