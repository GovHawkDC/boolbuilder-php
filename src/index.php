<?php
namespace GovHawkDC\Boolbuilder;

use GovHawkDC\Boolbuilder\ES;

const DEFAULT_QB = 'QBGroup';

function handleGroup($group, $options, $parentQB)
{
    // If the $parentQB context is non-default, we stay in that context... Since the
    // $parentQB context is non-default already, user funcs will no longer be applied
    if ($parentQB !== DEFAULT_QB) {
        // If the current $group "QB" context is default, we re-set the context to that
        // of the $parentQB
        if (!isset($group['QB']) || $group['QB'] === DEFAULT_QB) {
            $group['QB'] = $parentQB;
            return $group;
        }
        if ($group['QB'] === $parentQB) {
            return $group;
        }
        // Let users know that nesting of different non-default "QB" contexts is not
        // supported...
        throw new \Exception('Unable to process nested group of different custom type');
    }
    // Default "QB" contexts will not have user funcs applied...
    if (!isset($group['QB']) || $group['QB'] === DEFAULT_QB) {
        $group['QB'] = DEFAULT_QB;
        return $group;
    }
    // Finally, check for user func and apply if present...
    if (isset($options['typeFuncMap'][$group['QB']])) {
        $userFunc = $options['typeFuncMap'][$group['QB']];
        return $userFunc($group, $options);
    }
    return $group;
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
