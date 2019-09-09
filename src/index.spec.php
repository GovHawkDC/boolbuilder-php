<?php
namespace GovHawkDC\Boolbuilder\Spec;

use PHPUnit\Framework\TestCase;
use GovHawkDC\Boolbuilder;

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

        $options = [];
        $options['typeFuncMap'] = [
            'book' => function (
                $group,
                $rules,
                $options,
                $nextFunc
            ) {
                return [
                    'must' => [
                        ['terms' => ['_type' => ['book']]],
                        [
                            'bool' => $nextFunc($group, $rules, $options)
                        ]
                    ]
                ];
            }
        ];

        $result = Boolbuilder\transform($QBdata, $options);

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

        $options = [];
        $options['typeFuncMap'] = [
            'book' => function (
                $group,
                $rules,
                $options,
                $nextFunc
            ) {
                return [
                    'must' => [
                        ['terms' => ['_type' => ['book']]],
                        [
                            'bool' => $nextFunc(
                                $group,
                                $rules,
                                $options
                            )
                        ]
                    ]
                ];
            }
        ];
        $options['excludeFields'] = ['misc'];

        $result = Boolbuilder\transform($QBdata, $options);

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

        $options = [];
        $options['typeFuncMap'] = [
            'book' => function (
                $group,
                $rules,
                $options,
                $nextFunc
            ) {
                return [
                    'must' => [
                        ['terms' => ['_type' => ['book']]],
                        [
                            'bool' => $nextFunc(
                                $group,
                                $rules,
                                $options
                            )
                        ]
                    ]
                ];
            }
        ];
        $options['excludeOperators'] = ['is_not_null', 'is_null'];

        $result = Boolbuilder\transform($QBdata, $options);

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

    public function testQBData8()
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

        $options = [];
        $options['typeFuncMap'] = [
            'book' => function (
                $group,
                $rules,
                $options,
                $nextFunc
            ) {
                return [
                    'must' => [
                        ['terms' => ['_type' => ['book']]],
                        [
                            'bool' => $nextFunc(
                                $group,
                                $rules,
                                $options
                            )
                        ]
                    ]
                ];
            }
        ];
        $options['includeFields'] = ['misc'];

        $result = Boolbuilder\transform($QBdata, $options);

        $expected = [
            'bool' => [
                'must' => [
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
                                            ]
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

    public function testQBData9()
    {
        $QBdata = [
            'condition' => 'OR',
            'rules' => [
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
                        ]
                    ]
                ],
                [
                    'QB' => 'book',
                    'condition' => 'AND',
                    'rules' => [
                        [
                            'QB' => 'QBRule',
                            'field' => 'state',
                            'id' => 'state',
                            'input' => 'checkbox',
                            'operator' => 'in',
                            'type' => 'string',
                            'value' => ['me', 'nv', 'mi', 'ri', 'md']
                        ]
                    ]
                ]
            ]
        ];

        $options = [];
        $options['excludeFields'] = ['state'];

        $result = Boolbuilder\transform($QBdata, $options);

        $expected = [
            'bool' => [
                'should' => [
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'bool' => [
                                        'must_not' => [
                                            ['exists' => ['field' => 'misc']]
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

    public function testAlterRuleFieldName()
    {
        $data = [
            'condition' => 'OR',
            'rules' => [
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
                        ]
                    ]
                ]
            ]
        ];

        $options = [];
        $options['ruleFuncMap'] = [];
        $options['ruleFuncMap']['book'] = [];
        $options['ruleFuncMap']['book']['misc'] = function ($rule) {
            return array_merge($rule, [
                'id' => "{$rule['field']}.subfield",
                'field' => "{$rule['field']}.subfield"
            ]);
        };

        $result = Boolbuilder\transform($data, $options);

        $expected = [
            'bool' => [
                'should' => [
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'bool' => [
                                        'must_not' => [
                                            ['exists' => ['field' => 'misc.subfield']]
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

    public function testAlterRuleValues()
    {
        $data = [
            'condition' => 'OR',
            'rules' => [
                [
                    'QB' => 'book',
                    'condition' => 'or',
                    'rules' => [
                        [
                            'id' => 'misc',
                            'field' => 'misc',
                            'type' => 'string',
                            'input' => 'text',
                            'operator' => 'in',
                            'value' => [
                                'HELLO',
                                'WORLD'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $options = [];
        $options['ruleFuncMap'] = [];
        $options['ruleFuncMap']['book'] = [];
        $options['ruleFuncMap']['book']['misc'] = function ($rule) {
            return array_merge($rule, [
                'value' => array_map('strtolower', $rule['value'])
            ]);
        };

        $result = Boolbuilder\transform($data, $options);

        $expected = [
            'bool' => [
                'should' => [
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'terms' => [
                                        'misc' => [
                                            'hello',
                                            'world'
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
