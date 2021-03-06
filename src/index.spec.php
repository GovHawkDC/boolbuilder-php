<?php
namespace GovHawkDC\Boolbuilder\Spec;

use PHPUnit\Framework\TestCase;

use GovHawkDC\Boolbuilder;

final class IndexTest extends TestCase
{
    public function testSimpleTransform()
    {
        $group = [
            'condition' => 'OR',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ]
            ]
        ];

        $query = [
            'bool' => [
                'should' => [
                    [
                        'match' => [
                            'user' => 'elasticsearch'
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($query, Boolbuilder\transform($group));
    }

    public function testSimpleNestedTransform()
    {
        $group = [
            'condition' => 'AND',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ],
                [
                    'condition' => 'OR',
                    'rules' => [
                        [
                            'field' => 'message',
                            'type' => 'string',
                            'operator' => 'equal',
                            'value' => 'this is a test'
                        ],
                        [
                            'field' => 'user',
                            'type' => 'string',
                            'operator' => 'in',
                            'value' => ['kimchy', 'elasticsearch']
                        ]
                    ]
                ]
            ]
        ];

        $query = [
            'bool' => [
                'must' => [
                    [
                        'match' => [
                            'user' => 'elasticsearch'
                        ]
                    ],
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'match_phrase' => [
                                        'message' => 'this is a test'
                                    ]
                                ],
                                [
                                    'terms' => [
                                        'user' => ['kimchy', 'elasticsearch']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($query, Boolbuilder\transform($group));
    }

    public function testSimpleMustNotNestedTransform()
    {
        $group = [
            'condition' => 'AND',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ],
                [
                    'condition' => 'NOT',
                    'rules' => [
                        [
                            'condition' => 'AND',
                            'rules' => [
                                [
                                    'field' => 'message',
                                    'type' => 'string',
                                    'operator' => 'equal',
                                    'value' => 'this is a test'
                                ],
                            ]
                        ],
                        [
                            'field' => 'user',
                            'type' => 'string',
                            'operator' => 'in',
                            'value' => ['kimchy', 'elasticsearch']
                        ]
                    ]
                ]
            ]
        ];

        $query = [
            'bool' => [
                'must' => [
                    [
                        'match' => [
                            'user' => 'elasticsearch'
                        ]
                    ],
                    [
                        'bool' => [
                            'must_not' => [
                                [
                                    'bool' => [
                                        'must' => [
                                            [
                                                'match_phrase' => [
                                                    'message' => 'this is a test'
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'terms' => [
                                        'user' => ['kimchy', 'elasticsearch']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($query, Boolbuilder\transform($group));
    }

    public function testSimpleNegativeMustNotNestedTransform()
    {
        $group = [
            'condition' => 'AND',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ],
                [
                    'condition' => 'NOT',
                    'rules' => [
                        [
                            'condition' => 'AND',
                            'rules' => [
                                [
                                    'field' => 'message',
                                    'type' => 'string',
                                    'operator' => 'equal',
                                    'value' => 'this is a test'
                                ],
                            ]
                        ],
                        [
                            'field' => 'user',
                            'type' => 'string',
                            // This part...
                            'operator' => 'not_in',
                            'value' => ['kimchy', 'elasticsearch']
                        ]
                    ]
                ]
            ]
        ];

        $query = [
            'bool' => [
                'must' => [
                    [
                        'match' => [
                            'user' => 'elasticsearch'
                        ]
                    ],
                    [
                        'bool' => [
                            'must_not' => [
                                [
                                    'bool' => [
                                        'must' => [
                                            [
                                                'match_phrase' => [
                                                    'message' => 'this is a test'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            'must' => [
                                [
                                    'terms' => [
                                        'user' => ['kimchy', 'elasticsearch']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($query, Boolbuilder\transform($group));
    }

    public function testExcludeFieldsTransform()
    {
        $group = [
            'condition' => 'AND',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ],
                [
                    'condition' => 'OR',
                    'rules' => [
                        [
                            'field' => 'message',
                            'type' => 'string',
                            'operator' => 'equal',
                            'value' => 'this is a test'
                        ],
                        [
                            'field' => 'user',
                            'type' => 'string',
                            'operator' => 'in',
                            'value' => ['kimchy', 'elasticsearch']
                        ]
                    ]
                ]
            ]
        ];

        $options = [];
        $options['excludeFields'] = ['message'];

        $query = [
            'bool' => [
                'must' => [
                    [
                        'match' => [
                            'user' => 'elasticsearch'
                        ]
                    ],
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'terms' => [
                                        'user' => ['kimchy', 'elasticsearch']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($query, Boolbuilder\transform($group, $options));
    }

    public function testExcludeOperatorsTransform()
    {
        $group = [
            'condition' => 'AND',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ],
                [
                    'condition' => 'OR',
                    'rules' => [
                        [
                            'field' => 'message',
                            'type' => 'string',
                            'operator' => 'equal',
                            'value' => 'this is a test'
                        ],
                        [
                            'field' => 'user',
                            'type' => 'string',
                            'operator' => 'in',
                            'value' => ['kimchy', 'elasticsearch']
                        ]
                    ]
                ]
            ]
        ];

        $options = [];
        $options['excludeOperators'] = ['equal'];

        $query = [
            'bool' => [
                'must' => [
                    [
                        'match' => [
                            'user' => 'elasticsearch'
                        ]
                    ],
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'terms' => [
                                        'user' => ['kimchy', 'elasticsearch']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($query, Boolbuilder\transform($group, $options));
    }

    public function testTypeFuncMapTransform()
    {
        $group = [
            'QB' => 'Chat',
            'condition' => 'AND',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ],
                [
                    'condition' => 'OR',
                    'rules' => [
                        [
                            'field' => 'message',
                            'type' => 'string',
                            'operator' => 'equal',
                            'value' => 'this is a test'
                        ],
                        [
                            'field' => 'user',
                            'type' => 'string',
                            'operator' => 'in',
                            'value' => ['kimchy', 'elasticsearch']
                        ]
                    ]
                ]
            ]
        ];

        $options = [];
        $options['typeFuncMap'] = [];
        $options['typeFuncMap']['Chat'] = function ($group, $options) {
            return [
                'QB' => 'Chat',
                'condition' => 'AND',
                'rules' => [
                    [
                        'field' => 'app',
                        'type' => 'string',
                        'operator' => 'in',
                        'value' => ['video', 'audio']
                    ],
                    $group
                ]
            ];
        };

        $query = [
            'bool' => [
                'must' => [
                    [
                        'terms' => [
                            'app' => ['video', 'audio']
                        ]
                    ],
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'match' => [
                                        'user' => 'elasticsearch'
                                    ]
                                ],
                                [
                                    'bool' => [
                                        'should' => [
                                            [
                                                'match_phrase' => [
                                                    'message' => 'this is a test'
                                                ]
                                            ],
                                            [
                                                'terms' => [
                                                    'user' => ['kimchy', 'elasticsearch']
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

        $this->assertEquals($query, Boolbuilder\transform($group, $options));
    }

    public function testRuleFuncMapTransform()
    {
        $group = [
            'QB' => 'Chat',
            'condition' => 'AND',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ],
                [
                    'condition' => 'OR',
                    'rules' => [
                        [
                            'field' => 'message',
                            'type' => 'string',
                            'operator' => 'equal',
                            'value' => 'this is a test'
                        ],
                        [
                            'field' => 'user',
                            'type' => 'string',
                            'operator' => 'in',
                            'value' => ['kimchy', 'elasticsearch']
                        ]
                    ]
                ]
            ]
        ];

        $options = [];
        $options['ruleFuncMap'] = ['Chat'];
        $options['ruleFuncMap']['Chat'] = [];
        $options['ruleFuncMap']['Chat']['user'] = function ($group, $rule, $options) {
            if (is_string($rule['value'])) {
                $rule['value'] =  strtoupper($rule['value']);
            } elseif (is_array($rule['value'])) {
                $rule['value'] = array_map('strtoupper', $rule['value']);
            }
            return $rule;
        };

        $query = [
            'bool' => [
                'must' => [
                    [
                        'match' => [
                            'user' => 'ELASTICSEARCH'
                        ]
                    ],
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'match_phrase' => [
                                        'message' => 'this is a test'
                                    ]
                                ],
                                [
                                    'terms' => [
                                        'user' => ['KIMCHY', 'ELASTICSEARCH']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($query, Boolbuilder\transform($group, $options));
    }

    public function testNestedCustomTypeTransform()
    {
        $this->expectException(\Exception::class);

        $group = [
            'QB' => 'Chat',
            'condition' => 'AND',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ],
                [
                    'QB' => 'Message',
                    'condition' => 'OR',
                    'rules' => [
                        [
                            'field' => 'message',
                            'type' => 'string',
                            'operator' => 'equal',
                            'value' => 'this is a test'
                        ]
                    ]
                ]
            ]
        ];
        Boolbuilder\transform($group);
    }

    public function testMultipleRuleAndTypeFuncMapTransform()
    {
        $group = [
            'condition' => 'OR',
            'rules' => [
                [
                    'QB' => 'Message',
                    'condition' => 'OR',
                    'rules' => [
                        [
                            'field' => 'user',
                            'type' => 'string',
                            'operator' => 'contains',
                            'value' => 'elasticsearch'
                        ]
                    ]
                ],
                [
                    'QB' => 'Chat',
                    'condition' => 'OR',
                    'rules' => [
                        [
                            'field' => 'message',
                            'type' => 'string',
                            'operator' => 'equal',
                            'value' => 'this is a test'
                        ],
                        [
                            'field' => 'user',
                            'type' => 'string',
                            'operator' => 'in',
                            'value' => ['kimchy', 'elasticsearch']
                        ]
                    ]
                ]
            ]
        ];

        $options = [];
        $options['typeFuncMap'] = [];
        $options['typeFuncMap']['Chat'] = function ($group, $options) {
            return [
                'QB' => 'Chat',
                'condition' => 'AND',
                'rules' => [
                    [
                        'field' => 'app',
                        'type' => 'string',
                        'operator' => 'in',
                        'value' => ['video', 'audio']
                    ],
                    $group
                ]
            ];
        };
        $options['typeFuncMap']['Message'] = function ($group, $options) {
            return [
                'QB' => 'Message',
                'condition' => 'AND',
                'rules' => [
                    [
                        'field' => 'app',
                        'type' => 'string',
                        'operator' => 'in',
                        'value' => ['blog']
                    ],
                    $group
                ]
            ];
        };
        $options['ruleFuncMap'] = ['Chat'];
        $options['ruleFuncMap']['Chat'] = [];
        $options['ruleFuncMap']['Chat']['user'] = function ($group, $rule, $options) {
            if (is_string($rule['value'])) {
                $rule['value'] =  strtoupper($rule['value']);
            } elseif (is_array($rule['value'])) {
                $rule['value'] = array_map('strtoupper', $rule['value']);
            }
            return $rule;
        };

        $query = [
            'bool' => [
                'should' => [
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'terms' => [
                                        'app' => ['blog']
                                    ]
                                ],
                                [
                                    'bool' => [
                                        'should' => [
                                            [
                                                'match' => [
                                                    'user' => 'elasticsearch'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'terms' => [
                                        'app' => ['video', 'audio']
                                    ]
                                ],
                                [
                                    'bool' => [
                                        'should' => [
                                            [
                                                'match_phrase' => [
                                                    'message' => 'this is a test'
                                                ]
                                            ],
                                            [
                                                'terms' => [
                                                    'user' => ['KIMCHY', 'ELASTICSEARCH']
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

        $this->assertEquals($query, Boolbuilder\transform($group, $options));
    }

    public function testAllowNestedCustomTypeTransform()
    {
        $group = [
            'QB' => 'Chat',
            'condition' => 'AND',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ],
                [
                    'QB' => 'Message',
                    'condition' => 'OR',
                    'rules' => [
                        [
                            'field' => 'message',
                            'type' => 'string',
                            'operator' => 'equal',
                            'value' => 'this is a test'
                        ]
                    ]
                ]
            ]
        ];

        $options = [];
        $options['nestedTypeHandling'] = Boolbuilder\NESTED_TYPE_HANDLING_ALLOW;
        $options['typeFuncMap'] = [];
        $options['typeFuncMap']['Chat'] = function ($group, $options) {
            return [
                'QB' => 'Chat',
                'condition' => 'AND',
                'rules' => [
                    [
                        'field' => 'app',
                        'type' => 'string',
                        'operator' => 'in',
                        'value' => ['video', 'audio']
                    ],
                    $group
                ]
            ];
        };
        $options['typeFuncMap']['Message'] = function ($group, $options) {
            return [
                'QB' => 'Message',
                'condition' => 'AND',
                'rules' => [
                    [
                        'field' => 'app',
                        'type' => 'string',
                        'operator' => 'in',
                        'value' => ['blog']
                    ],
                    $group
                ]
            ];
        };

        $nestedQuery = [
            'bool' => [
                'must' => [
                    [
                        'terms' => [
                            'app' => ['blog']
                        ]
                    ],
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'match_phrase' => [
                                        'message' => 'this is a test'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $query = [
            'bool' => [
                'must' => [
                    [
                        'terms' => [
                            'app' => ['video', 'audio']
                        ]
                    ],
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'match' => [
                                        'user' => 'elasticsearch'
                                    ]
                                ],
                                $nestedQuery
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($query, Boolbuilder\transform($group, $options));
    }

    public function testConditionalNestedCustomTypeTransform()
    {
        $group = [
            'QB' => 'Chat',
            'condition' => 'AND',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ],
                [
                    'QB' => 'Message',
                    'condition' => 'OR',
                    'rules' => [
                        [
                            'field' => 'message',
                            'type' => 'string',
                            'operator' => 'equal',
                            'value' => 'this is a test'
                        ]
                    ]
                ],
                [
                    'QB' => 'Post',
                    'condition' => 'OR',
                    'rules' => [
                        [
                            'field' => 'content',
                            'type' => 'string',
                            'operator' => 'equal',
                            'value' => 'hello world'
                        ]
                    ]
                ]
            ]
        ];

        $options = [];
        $options['nestedTypeHandling'] = Boolbuilder\NESTED_TYPE_HANDLING_CONDITIONAL;
        // Not handling "Post", so that should be empty
        $options['nestedTypeTransitionMap'] = [];
        $options['nestedTypeTransitionMap']['Chat'] = ['Message'];
        $options['typeFuncMap'] = [];
        $options['typeFuncMap']['Chat'] = function ($group, $options) {
            return [
                'QB' => 'Chat',
                'condition' => 'AND',
                'rules' => [
                    [
                        'field' => 'app',
                        'type' => 'string',
                        'operator' => 'in',
                        'value' => ['video', 'audio']
                    ],
                    $group
                ]
            ];
        };
        $options['typeFuncMap']['Message'] = function ($group, $options) {
            return [
                'QB' => 'Message',
                'condition' => 'AND',
                'rules' => [
                    [
                        'field' => 'app',
                        'type' => 'string',
                        'operator' => 'in',
                        'value' => ['blog']
                    ],
                    $group
                ]
            ];
        };

        $nestedQuery = [
            'bool' => [
                'must' => [
                    [
                        'terms' => [
                            'app' => ['blog']
                        ]
                    ],
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'match_phrase' => [
                                        'message' => 'this is a test'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $query = [
            'bool' => [
                'must' => [
                    [
                        'terms' => [
                            'app' => ['video', 'audio']
                        ]
                    ],
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'match' => [
                                        'user' => 'elasticsearch'
                                    ]
                                ],
                                $nestedQuery
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($query, Boolbuilder\transform($group, $options));
    }

    public function testEmptyNestedCustomTypeTransform()
    {
        $group = [
            'condition' => 'AND',
            'rules' => [
                [
                    'QB' => 'Chat',
                    'condition' => 'AND',
                    'rules' => [
                        [
                            'field' => 'user',
                            'type' => 'string',
                            'operator' => 'contains',
                            'value' => 'elasticsearch'
                        ],
                        [
                            'QB' => 'Message',
                            'condition' => 'OR',
                            'rules' => [
                                [
                                    'field' => 'message',
                                    'type' => 'string',
                                    'operator' => 'equal',
                                    'value' => 'this is a test'
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'QB' => 'Message',
                    'condition' => 'OR',
                    'rules' => [
                        [
                            'field' => 'message',
                            'type' => 'string',
                            'operator' => 'equal',
                            'value' => 'this is a test'
                        ]
                    ]
                ]
            ]
        ];

        $options = [];
        $options['nestedTypeHandling'] = Boolbuilder\NESTED_TYPE_HANDLING_EMPTY;
        $options['typeFuncMap'] = [];
        $options['typeFuncMap']['Chat'] = function ($group, $options) {
            return [
                'QB' => 'Chat',
                'condition' => 'AND',
                'rules' => [
                    [
                        'field' => 'app',
                        'type' => 'string',
                        'operator' => 'in',
                        'value' => ['video', 'audio']
                    ],
                    $group
                ]
            ];
        };
        // NOTE: This should go unused when "Message" is nested
        $options['typeFuncMap']['Message'] = function ($group, $options) {
            return [
                'QB' => 'Message',
                'condition' => 'AND',
                'rules' => [
                    [
                        'field' => 'app',
                        'type' => 'string',
                        'operator' => 'in',
                        'value' => ['blog']
                    ],
                    $group
                ]
            ];
        };

        $chatQuery = [
            'bool' => [
                'must' => [
                    [
                        'terms' => [
                            'app' => ['video', 'audio']
                        ]
                    ],
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'match' => [
                                        'user' => 'elasticsearch'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $messageQuery = [
            'bool' => [
                'must' => [
                    [
                        'terms' => [
                            'app' => ['blog']
                        ]
                    ],
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'match_phrase' => [
                                        'message' => 'this is a test'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $query = [
            'bool' => [
                'must' => [
                    $chatQuery,
                    $messageQuery
                ]
            ]
        ];

        $this->assertEquals($query, Boolbuilder\transform($group, $options));
    }

    public function testRuleToGroupNestedCustomTypeTransform()
    {
        $group = [
            'QB' => 'Chat',
            'condition' => 'AND',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ],
                [
                    'field' => 'ref',
                    'type' => 'string',
                    'operator' => 'in',
                    'value' => []
                ]
            ]
        ];

        $options = [];
        $options['nestedTypeHandling'] = Boolbuilder\NESTED_TYPE_HANDLING_ALLOW;
        $options['ruleFuncMap'] = [];
        $options['ruleFuncMap']['Chat'] = [];
        $options['ruleFuncMap']['Chat']['ref'] = function ($group, $rule, $options) {
            return [
                'QB' => 'Message',
                'condition' => 'OR',
                'rules' => [
                    [
                        'field' => 'message',
                        'type' => 'string',
                        'operator' => 'equal',
                        'value' => 'this is a test'
                    ]
                ]
            ];
        };
        $options['typeFuncMap'] = [];
        $options['typeFuncMap']['Chat'] = function ($group, $options) {
            return [
                'QB' => 'Chat',
                'condition' => 'AND',
                'rules' => [
                    [
                        'field' => 'app',
                        'type' => 'string',
                        'operator' => 'in',
                        'value' => ['video', 'audio']
                    ],
                    $group
                ]
            ];
        };
        $options['typeFuncMap']['Message'] = function ($group, $options) {
            return [
                'QB' => 'Message',
                'condition' => 'AND',
                'rules' => [
                    [
                        'field' => 'app',
                        'type' => 'string',
                        'operator' => 'in',
                        'value' => ['blog']
                    ],
                    $group
                ]
            ];
        };

        $nestedQuery = [
            'bool' => [
                'must' => [
                    [
                        'terms' => [
                            'app' => ['blog']
                        ]
                    ],
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'match_phrase' => [
                                        'message' => 'this is a test'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $query = [
            'bool' => [
                'must' => [
                    [
                        'terms' => [
                            'app' => ['video', 'audio']
                        ]
                    ],
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'match' => [
                                        'user' => 'elasticsearch'
                                    ]
                                ],
                                $nestedQuery
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($query, Boolbuilder\transform($group, $options));
    }

    public function testMaxDepthTransform()
    {
        $this->expectException(\Exception::class);

        $group = [
            'QB' => 'Chat',
            'condition' => 'AND',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ],
                [
                    'QB' => 'Chat',
                    'condition' => 'AND',
                    'rules' => [
                        [
                            'QB' => 'Chat',
                            'condition' => 'AND',
                            'rules' => [
                                [
                                    'QB' => 'Chat',
                                    'condition' => 'AND',
                                    'rules' => [
                                        [
                                            'field' => 'user',
                                            'type' => 'string',
                                            'operator' => 'contains',
                                            'value' => 'elasticsearch'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Boolbuilder\transform($group, [], 2);
    }

    public function testSimpleAllRulesUserTransform()
    {
        $group = [
            'QB' => 'Chat',
            'condition' => 'OR',
            'rules' => [
                [
                    'field' => 'user',
                    'type' => 'string',
                    'operator' => 'contains',
                    'value' => 'elasticsearch'
                ],
                [
                    'field' => 'message',
                    'type' => 'string',
                    'operator' => 'equal',
                    'value' => 'this is a test'
                ]
            ]
        ];

        $query = [
            'bool' => [
                'should' => [
                    [
                        'wildcard' => [
                            'user' => 'elasticsearch*'
                        ]
                    ],
                    [
                        'wildcard' => [
                            'message' => 'this is a test*'
                        ]
                    ]
                ]
            ]
        ];

        $options = [];
        $options['ruleFuncMap'] = [];
        $options['ruleFuncMap']['Chat'] = [];
        $options['ruleFuncMap']['Chat']['*'] = function ($group, $rule, $options) {
            if (is_string($rule['value'])) {
                $rule['value'] = $rule['value'] . '*';
            }
            return $rule;
        };

        $this->assertEquals($query, Boolbuilder\transform($group, $options));
    }

    public function testAllTypesUserRuleFunc()
    {
        $group = [
            'condition' => 'OR',
            'rules' => [
                [
                    'QB' => 'Message',
                    'condition' => 'AND',
                    'rules' => [
                        [
                            'field' => 'user',
                            'type' => 'string',
                            'operator' => 'contains',
                            'value' => 'kimchy'
                        ]
                    ]
                ],
                [
                    'QB' => 'Chat',
                    'condition' => 'AND',
                    'rules' => [
                        [
                            'field' => 'user',
                            'type' => 'string',
                            'operator' => 'contains',
                            'value' => 'elasticsearch'
                        ]
                    ]
                ]
            ]
        ];

        $options = [];
        $options['ruleFuncMap'] = [];
        $options['ruleFuncMap']['*'] = [];
        $options['ruleFuncMap']['*']['user'] = function ($group, $rule, $options) {
            $rule['value'] = strtoupper($rule['value']);
            return $rule;
        };

        $query = [
            'bool' => [
                'should' => [
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'match' => [
                                        'user' => 'KIMCHY'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'match' => [
                                        'user' => 'ELASTICSEARCH'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($query, Boolbuilder\transform($group, $options));
    }
}
