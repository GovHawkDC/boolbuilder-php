<?php
namespace GovHawkDC\Boolbuilder;

use GovHawkDC\Boolbuilder\ES;

function transform($group, $options = [])
{
    return transformGroup($group, $options);
}

function transformGroup($group, $options)
{
    $QB = isset($group['QB']) ? $group['QB'] : '';
    if (empty($group['rules'])) {
        return [];
    }
    // Allow user functions to handle specific data types (e.g., change the default output)
    // while processing
    if (isset($options['typeFuncMap'][$QB])) {
        $userFunc = $options['typeFuncMap'][$QB];
        $nextFunc = __NAMESPACE__ . '\\transformRules';
        return $userFunc($group, $group['rules'], $options, $nextFunc);
    }
    return transformRules($group, $group['rules'], $options);
}

function transformRules($group, $rules, $options)
{
    $ts = [];
    foreach ($rules as $rule) {
        $fragment = transformRule($group, $rule, $options);
        if (empty($fragment)) {
            continue;
        }
        $clause = ES\getClause($group, $rule);
        if (!isset($ts[$clause])) {
            $ts[$clause] = [];
        }
        $ts[$clause][] = $fragment;
    }
    return ES\getQuery($ts);
}

function transformRule($group, $rule, $options)
{
    if (isset($rule['rules'])) {
        return transformGroup($rule, $options);
    }

    if (isRuleExcluded($rule, $options)) {
        return [];
    }

    $QB = isset($group['QB']) ? $group['QB'] : '';
    if (isset($options['ruleFuncMap'][$QB][$rule['field']])) {
        $userFunc = $options['ruleFuncMap'][$QB][$rule['field']];
        // TODO: Change signature of $userFunc to take $group as well...
        return ES\getFragment($group, $userFunc($rule));
    }
    return ES\getFragment($group, $rule);
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
