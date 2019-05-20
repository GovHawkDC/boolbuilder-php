<?php
use PHPUnit\Framework\TestCase;
use GovHawkDC\Boolbuilder\ES;

final class EsTest extends TestCase
{
    public function testArrayArgToGetArrayValueIsSelf()
    {
        $this->assertEquals(ES\getArrayValue([1, 2]), [1, 2]);
    }

    public function testStringArgToGetArrayValueIsArray()
    {
        $this->assertEquals(ES\getArrayValue('1, 2'), ['1', '2']);
    }

    public function testNonArrayStringArgToGetArrayValueThrows()
    {
        $this->expectException(\Exception::class);
        ES\getArrayValue(true);
    }

    public function testIsNullOperatorArgToGetFragmentReturnsExists()
    {
        $this->assertEquals(
            ES\getFragment(['field' => 'name', 'operator' => 'is_null']),
            ['exists' => ['field' => 'name']]
        );
    }

    public function testProximityOperatorArgToGetFragmentReturnsStandard()
    {
        $this->assertEquals(
            ES\getFragment([
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
            ES\getClause([], ['operator' => 'not_equal']),
            'must_not'
        );
    }

    public function testDefaultCondAndNonNegativeToGetClauseReturnsMust()
    {
        $this->assertEquals(ES\getClause([], ['operator' => 'equal']), 'must');
    }

    public function testOrCondAndAnythingToGetClauseReturnsMust()
    {
        $this->assertEquals(
            ES\getClause(['condition' => 'OR'], ['operator' => 'not_equal']),
            'should'
        );
    }

    public function testUnhandledCondToGetClauseThrows()
    {
        $this->expectException(\Exception::class);
        ES\getClause(['condition' => 'xor'], ['operator' => 'not_equal']);
    }

    public function testWildcardSplatCharToGetOperatorIsWildcard()
    {
        $this->assertEquals(ES\getOperator(['value' => 'hello*']), 'wildcard');
    }

    public function testWildcardQuestionCharToGetOperatorIsWildcard()
    {
        $this->assertEquals(
            ES\getOperator(['value' => 'hello world?']),
            'wildcard'
        );
    }

    public function testContainsToGetOperatorIsMatch()
    {
        $this->assertEquals(
            ES\getOperator(['operator' => 'contains', 'value' => '']),
            'match'
        );
    }

    public function testUnknownToGetOperatorIsRange()
    {
        $this->assertEquals(
            ES\getOperator(['operator' => '>>>', 'value' => 'hello world']),
            'range'
        );
    }

    public function testBetweenToGetValueIsGteLteArray()
    {
        $rule = ['operator' => 'between', 'value' => ['1', '2']];
        $operator = ES\getOperator($rule);
        $this->assertEquals(ES\getValue($rule, $operator), [
            'gte' => '1',
            'lte' => '2'
        ]);
    }

    public function testUnhandledToGetValueThrows()
    {
        $this->expectException(\Exception::class);
        $rule = ['operator' => '<>', 'value' => ['1', '2']];
        $operator = ES\getOperator($rule);
        ES\getValue($rule, $operator);
    }

    public function testIsNullToIsNegativeOperatorIsTrue()
    {
        $this->assertEquals(ES\isNegativeOperator('is_null'), true);
    }

    public function testGreaterToIsNegativeOperatorIsFalse()
    {
        $this->assertEquals(ES\isNegativeOperator('greater'), false);
    }
}
