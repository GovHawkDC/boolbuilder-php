<?php
namespace Boolbuilder;

use Boolbuilder\ES;

function transform($group, $filters = [], $options = [])
{
    if (!$group) {
        return [];
    }

    $QB = isset($group['QB']) ? $group['QB'] : '';
    $rules = isset($group['rules']) ? $group['rules'] : [];

    if (count($rules) < 1) {
        return [];
    }

    if (isset($filters[$QB])) {
        $userProvidedFilter = $filters[$QB];
        $postFilterUserFuncName = __NAMESPACE__ . '\\transformGroupPostFilter';

        return [
            'bool' => $userProvidedFilter(
                $group,
                $rules,
                $filters,
                $options,
                $postFilterUserFuncName
            )
        ];
    }

    return [
        'bool' => transformGroupPostFilter($group, $rules, $filters, $options)
    ];
}

function transformGroupPostFilter($group, $rules, $filters, $options)
{
    return array_reduce($rules, function ($carry, $rule) use (
        $group,
        $filters,
        $options
    ) {
        $clause = ES\getClause($group, $rule);
        $fragment = transformRule($group, $rule, $filters, $options);

        $existingFragments = isset($carry[$clause]) ? $carry[$clause] : [];

        return array_merge($carry, [
            $clause => array_merge($existingFragments, [$fragment])
        ]);
    }, []);
}

function transformRule($group, $rule, $filters, $options)
{
    $condition = isset($group['condition']) ? $group['condition'] : '';
    $operator = isset($rule['operator']) ? $rule['operator'] : '';
    $rules = isset($rule['rules']) ? $rule['rules'] : [];

    if (count($rules) > 0) {
        return transform($rule, $filters, $options);
    }

    $fragment = ES\getFragment($rule);

    // this is a corner case, when we have an "or" group and a negative operator,
    // we express this with a sub boolean query and must_not
    if (strtoupper($condition) === 'OR' && ES\isNegativeOperator($operator)) {

        return ['bool' => ['must_not' => [$fragment]]];
    }

    return $fragment;
}
