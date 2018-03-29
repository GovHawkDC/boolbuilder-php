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
}
