<?php
namespace Boolbuilder;

function transform($group, $filters = [])
{
    if (!$group) {
        return [];
    }

    $QB = isset($group['QB']) ? $group['QB'] : '';
    $rules = isset($group['$rules']) ? $group['$rules'] : [];

    if (count($rules) < 1) {
        return [];
    }

    $postFilterUserFuncName = __NAMESPACE__ . '\\transformGroupPostFilter';

    if (isset($filters[$QB])) {
        $userProvidedFilter = $filters[$QB];
        return $userProvidedFilter(
            $group,
            $rules,
            $filters,
            $postFilterUserFuncName
        );
    }

    return call_user_func(
        __NAMESPACE__ . '\\transformGroupDefaultFilter',
        $group,
        $rules,
        $filters,
        $postFilterUserFuncName
    );
}

function transformGroupPostFilter($group, $rules, $filters)
{

}

function transformGroupDefaultFilter($group, $rules, $filters, $postFilter)
{

}

function transformRule($group, $rule, $filters)
{

}

function mergeByClause($accumulator, $data)
{

}
