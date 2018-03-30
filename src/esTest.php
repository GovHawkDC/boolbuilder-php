<?php
include 'es.php';

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
}
