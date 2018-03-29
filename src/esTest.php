<?php
include 'es.php';

use PHPUnit\Framework\TestCase;

final class EsTest extends TestCase
{
    public function testArrayArgIsSelf()
    {
        $this->assertEquals(\Boolbuilder\ES\getArrayValue([1, 2]), [1, 2]);
    }
}
