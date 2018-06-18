<?php
namespace Boolbuilder;

function transform($group, $filters = [])
{
    if (!$group) {
        return [];
    }

    $QB = isset($group['QB']) ? $group['QB'] : '';
    $rules = isset($group['rules']) ? $group['rules'] : [];

    if (count($rules) < 1) {
        return [];
    }

    $postFilterUserFuncName = __NAMESPACE__ . '\\transformGroupPostFilter';

    if (isset($filters[$QB])) {
        $userProvidedFilter = $filters[$QB];
        return [
            'bool' => $userProvidedFilter(
                $group,
                $rules,
                $filters,
                $postFilterUserFuncName
            )
        ];
    }

    return [
        'bool' => transformGroupDefaultFilter(
            $group,
            $rules,
            $filters,
            $postFilterUserFuncName
        )
    ];
}

function transformGroupPostFilter($group, $rules, $filters)
{
    $clausesAndFragments = array_map(function ($rule) use ($group, $filters) {
        return [
            'clause' => \Boolbuilder\ES\getClause($group, $rule),
            'fragment' => transformRule($group, $rule, $filters)
        ];
    }, $rules);

    return array_reduce($clausesAndFragments, function ($carry, $data) {
        $clause = $data['clause'];
        $fragment = $data['fragment'];

        $existingFragments = isset($carry[$clause]) ? $carry[$clause] : [];

        return array_merge($carry, [
            $clause => array_merge($existingFragments, [$fragment])
        ]);
    }, []);
}

function transformGroupDefaultFilter(
    $group,
    $rules,
    $filters,
    $postFilterUserFuncName
)
{
    return call_user_func($postFilterUserFuncName, $group, $rules, $filters);
}

function transformRule($group, $rule, $filters)
{
    $condition = isset($group['condition']) ? $group['condition'] : '';
    $operator = isset($rule['operator']) ? $rule['operator'] : '';
    $rules = isset($rule['rules']) ? $rule['rules'] : [];

    if (count($rules) > 0) {
        return call_user_func(__NAMESPACE__ . '\\transform', $rule, $filters);
    }

    $fragment = \Boolbuilder\ES\getFragment($rule);

    // this is a corner case, when we have an "or" group and a negative operator,
    // we express this with a sub boolean query and must_not
    if (
        strtoupper($condition) === 'OR' &&
        \Boolbuilder\ES\isNegativeOperator($operator)
    ) {

        return ['bool' => ['must_not' => [$fragment]]];
    }

    return $fragment;
}
