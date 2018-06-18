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

        $result = Boolbuilder\transform($QBdata);

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

        $result = Boolbuilder\transform($QBdata);

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

        $result = Boolbuilder\transform($QBdata);

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

    public function testQBData3()
    {
        $QBdata = [];

        $result = Boolbuilder\transform($QBdata);

        $expected = [];

        $this->assertEquals($result, $expected);
    }

    public function testQBData4()
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
                            'operator' => 'is_null',
                            'value' => null
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

        $result = Boolbuilder\transform($QBdata);

        $expected = [
            'bool' => [
                'must' => [
                    ['match' => ['name' => '123']],
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'bool' => [
                                        'must_not' => [
                                            ['exists' => ['field' => 'misc']]
                                        ]
                                    ]
                                ],
                                ['terms' => ['type' => ['book']]]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($result, $expected);
    }

    public function testQBData5()
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
                    'QB' => 'book',
                    'condition' => 'or',
                    'rules' => [
                        [
                            'id' => 'misc',
                            'field' => 'misc',
                            'type' => 'string',
                            'input' => 'text',
                            'operator' => 'is_null',
                            'value' => null
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

        $filters = [
            'book' => function (
                $group,
                $rules,
                $filters,
                $options,
                $postFilterUserFuncName
            ) {
                return [
                    'must' => [
                        ['terms' => ['_type' => ['book']]],
                        [
                            'bool' => call_user_func(
                                $postFilterUserFuncName,
                                $group,
                                $rules,
                                $filters,
                                $options
                            )
                        ]
                    ]
                ];
            }
        ];

        $result = Boolbuilder\transform($QBdata, $filters);

        $expected = [
            'bool' => [
                'must' => [
                    ['match' => ['name' => '123']],
                    [
                        'bool' => [
                            'must' => [
                                ['terms' => ['_type' => ['book']]],
                                [
                                    'bool' => [
                                        'should' => [
                                            [
                                                'bool' => [
                                                    'must_not' => [
                                                        [
                                                            'exists' => [
                                                                'field' => 'misc'
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ],
                                            ['terms' => ['type' => ['book']]]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($result, $expected);
    }

    public function testQBData6()
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
                    'QB' => 'book',
                    'condition' => 'or',
                    'rules' => [
                        [
                            'id' => 'misc',
                            'field' => 'misc',
                            'type' => 'string',
                            'input' => 'text',
                            'operator' => 'is_null',
                            'value' => null
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

        $filters = [
            'book' => function (
                $group,
                $rules,
                $filters,
                $options,
                $postFilterUserFuncName
            ) {
                return [
                    'must' => [
                        ['terms' => ['_type' => ['book']]],
                        [
                            'bool' => call_user_func(
                                $postFilterUserFuncName,
                                $group,
                                $rules,
                                $filters,
                                $options
                            )
                        ]
                    ]
                ];
            }
        ];

        $options = ['filterFields' => ['misc']];

        $result = Boolbuilder\transform($QBdata, $filters, $options);

        $expected = [
            'bool' => [
                'must' => [
                    ['match' => ['name' => '123']],
                    [
                        'bool' => [
                            'must' => [
                                ['terms' => ['_type' => ['book']]],
                                [
                                    'bool' => [
                                        'should' => [
                                            ['terms' => ['type' => ['book']]]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($result, $expected);
    }

    public function testQBData7()
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
                    'QB' => 'book',
                    'condition' => 'or',
                    'rules' => [
                        [
                            'id' => 'misc',
                            'field' => 'misc',
                            'type' => 'string',
                            'input' => 'text',
                            'operator' => 'is_null',
                            'value' => null
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

        $filters = [
            'book' => function (
                $group,
                $rules,
                $filters,
                $options,
                $postFilterUserFuncName
            ) {
                return [
                    'must' => [
                        ['terms' => ['_type' => ['book']]],
                        [
                            'bool' => call_user_func(
                                $postFilterUserFuncName,
                                $group,
                                $rules,
                                $filters,
                                $options
                            )
                        ]
                    ]
                ];
            }
        ];

        $options = ['filterOperators' => ['is_not_null', 'is_null']];

        $result = Boolbuilder\transform($QBdata, $filters, $options);

        $expected = [
            'bool' => [
                'must' => [
                    ['match' => ['name' => '123']],
                    [
                        'bool' => [
                            'must' => [
                                ['terms' => ['_type' => ['book']]],
                                [
                                    'bool' => [
                                        'should' => [
                                            ['terms' => ['type' => ['book']]]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($result, $expected);
    }
}
