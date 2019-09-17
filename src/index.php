<?php
namespace GovHawkDC\Boolbuilder;

use GovHawkDC\Boolbuilder\ES;

const DEFAULT_QB = 'QBGroup';

function handleGroup($group, $options, $context)
{
    // Setting to default to simplify other checks
    if (!isset($group['QB'])) {
        $group['QB'] = $context;
    }
    // We don't want to allow user funcs to be applied to the default "QB" or to
    // type contexts that have already been created...
    if (in_array($group['QB'], [DEFAULT_QB, $context], true)) {
        return $group;
    }
    // We do not allow for nested processing of custom types
    if ($context !== DEFAULT_QB) {
        throw new \Exception('Unable to process nested group of different custom type');
    }
    // Apply user func to $group if available
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

function transformGroup($group, $options, $context)
{
    $group = handleGroup($group, $options, $context);
    if (empty($group['rules'])) {
        return [];
    }
    return transformRules($group, $group['rules'], $options, $group['QB']);
}

function transformRule($group, $rule, $options, $context)
{
    if (isset($rule['rules'])) {
        if ($context !== DEFAULT_QB) {
            return transformGroup($rule, $options, $context);
        }
        return transformGroup($rule, $options, $group['QB']);
    }
    if (isRuleExcluded($group, $rule, $options)) {
        return [];
    }
    return ES\getQuery($group, $rule);
}

function transformRules($group, $rules, $options, $context)
{
    $clauses = [];
    foreach ($rules as $rule) {
        $rule = handleRule($group, $rule, $options);
        $query = transformRule($group, $rule, $options, $context);
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
