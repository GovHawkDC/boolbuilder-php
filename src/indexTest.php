<?php
use PHPUnit\Framework\TestCase;

final class IndexTest extends TestCase
{
    public function testQBData0()
    {
        $QBdata = [
            'condition' => 'AND',
            'rules' => [
                [
                    'id' => 'name',
                    'field' => 'name',
                    'type' => 'string',
                    'input' => 'text',
                    'operator' => 'contains',
                    'value' => '123'
                ]
            ]
        ];

        $result = \Boolbuilder\transform($QBdata);

        $expected = ['bool' => ['must' => [['match' => ['name' => '123']]]]];

        $this->assertEquals($result, $expected);
    }

    public function testQBData1()
    {
        $QBdata = [
            'condition' => 'OR',
            'rules' => [
                [
                    'id' => 'name',
                    'field' => 'name',
                    'type' => 'string',
                    'input' => 'text',
                    'operator' => 'contains',
                    'value' => '123'
                ]
            ]
        ];

        $result = \Boolbuilder\transform($QBdata);

        $expected = ['bool' => ['should' => [['match' => ['name' => '123']]]]];

        $this->assertEquals($result, $expected);
    }
}
