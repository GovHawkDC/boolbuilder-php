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

    public function testQBData2()
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
                ],
                [
                    'condition' => 'or',
                    'rules' => [
                        [
                            'id' => 'misc',
                            'field' => 'misc',
                            'type' => 'string',
                            'input' => 'text',
                            'operator' => 'equal',
                            'value' => '123'
                        ],
                        [
                            'id' => 'type',
                            'field' => 'type',
                            'type' => 'string',
                            'input' => 'checkbox',
                            'operator' => 'in',
                            'value' => ['book']
                        ]
                    ]
                ]
            ]
        ];

        $result = \Boolbuilder\transform($QBdata);

        $expected = [
            'bool' => [
                'must' => [
                    ['match' => ['name' => '123']],
                    [
                        'bool' => [
                            'should' => [
                                ['match_phrase' => ['misc' => '123']],
                                ['terms' => ['type' => ['book']]]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($result, $expected);
    }
}
