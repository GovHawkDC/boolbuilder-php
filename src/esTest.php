<?php
use PHPUnit\Framework\TestCase;

final class EsTest extends TestCase
{
    public function testArrayArgToGetArrayValueIsSelf()
    {
        $this->assertEquals(\Boolbuilder\ES\getArrayValue([1, 2]), [1, 2]);
    }

    public function testStringArgToGetArrayValueIsArray()
    {
        $this->assertEquals(\Boolbuilder\ES\getArrayValue('1, 2'), ['1', '2']);
    }

    public function testNonArrayStringArgToGetArrayValueThrows()
    {
        $this->expectException(\Exception::class);
        \Boolbuilder\ES\getArrayValue(true);
    }

    public function testIsNullOperatorArgToGetFragmentReturnsExists()
    {
        $this->assertEquals(
            \Boolbuilder\ES\getFragment([
                'field' => 'name',
                'operator' => 'is_null'
            ]),
            ['exists' => ['field' => 'name']]
        );
    }

    public function testProximityOperatorArgToGetFragmentReturnsStandard()
    {
        $this->assertEquals(
            \Boolbuilder\ES\getFragment([
                'field' => 'name',
                'operator' => 'proximity',
                'value' => ['a', '2']
            ]),
            ['match_phrase' => ['name' => ['query' => 'a', 'slop' => '2']]]
        );
    }

    public function testDefaultCondAndNegativeToGetClauseReturnsMustNot()
    {
        $this->assertEquals(
            \Boolbuilder\ES\getClause([], ['operator' => 'not_equal']),
            'must_not'
        );
    }

    public function testDefaultCondAndNonNegativeToGetClauseReturnsMust()
    {
        $this->assertEquals(
            \Boolbuilder\ES\getClause([], ['operator' => 'equal']),
            'must'
        );
    }

    public function testOrCondAndAnythingToGetClauseReturnsMust()
    {
        $this->assertEquals(
            \Boolbuilder\ES\getClause(['condition' => 'OR'], [
                'operator' => 'not_equal'
            ]),
            'should'
        );
    }

    public function testUnhandledCondToGetClauseThrows()
    {
        $this->expectException(\Exception::class);
        \Boolbuilder\ES\getClause(['condition' => 'xor'], [
            'operator' => 'not_equal'
        ]);
    }

    public function testWildcardSplatCharToGetOperatorIsWildcard()
    {
        $this->assertEquals(
            \Boolbuilder\ES\getOperator(['value' => 'hello*']),
            'wildcard'
        );
    }

    public function testWildcardQuestionCharToGetOperatorIsWildcard()
    {
        $this->assertEquals(
            \Boolbuilder\ES\getOperator(['value' => 'hello world?']),
            'wildcard'
        );
    }

    public function testContainsToGetOperatorIsMatch()
    {
        $this->assertEquals(
            \Boolbuilder\ES\getOperator([
                'operator' => 'contains',
                'value' => ''
            ]),
            'match'
        );
    }

    public function testUnknownToGetOperatorIsRange()
    {
        $this->assertEquals(
            \Boolbuilder\ES\getOperator([
                'operator' => '>>>',
                'value' => 'hello world'
            ]),
            'range'
        );
    }

    public function testBetweenToGetValueIsGteLteArray()
    {
        $rule = ['operator' => 'between', 'value' => ['1', '2']];
        $operator = \Boolbuilder\ES\getOperator($rule);
        $this->assertEquals(\Boolbuilder\ES\getValue($rule, $operator), [
            'gte' => '1',
            'lte' => '2'
        ]);
    }

    public function testUnhandledToGetValueThrows()
    {
        $this->expectException(\Exception::class);
        $rule = ['operator' => '<>', 'value' => ['1', '2']];
        $operator = \Boolbuilder\ES\getOperator($rule);
        \Boolbuilder\ES\getValue($rule, $operator);
    }

    public function testIsNullToIsNegativeOperatorIsTrue()
    {
        $this->assertEquals(
            \Boolbuilder\ES\isNegativeOperator('is_null'),
            true
        );
    }

    public function testGreaterToIsNegativeOperatorIsFalse()
    {
        $this->assertEquals(
            \Boolbuilder\ES\isNegativeOperator('greater'),
            false
        );
    }
}
