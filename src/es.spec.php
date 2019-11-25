<?php
namespace GovHawkDC\Boolbuilder\Spec;

use PHPUnit\Framework\TestCase;

use GovHawkDC\Boolbuilder\ES;

final class ESTest extends TestCase
{
    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/query-dsl-wildcard-query.html
     */
    public function testWildcardStringQuery()
    {
        $rule = [];
        $rule['field'] = 'user';
        $rule['operator'] = 'equal';
        $rule['type'] = 'string';
        $rule['value'] = 'ki*y';

        $query = [];
        $query['wildcard'] = [];
        $query['wildcard']['user'] = 'ki*y';

        $this->assertEquals($query, ES\getQueryHelper([], $rule));
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/query-dsl-wildcard-query.html
     */
    public function testWildcardBoostQuery()
    {
        $rule = [];
        $rule['field'] = 'user';
        $rule['operator'] = 'equal';
        $rule['type'] = 'string';
        $rule['value'] = ['ki*y', 2.0];

        $query = [];
        $query['wildcard'] = [];
        $query['wildcard']['user'] = [];
        $query['wildcard']['user']['value'] = 'ki*y';
        $query['wildcard']['user']['boost'] = 2.0;

        $this->assertEquals($query, ES\getQueryHelper([], $rule));
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/query-dsl-range-query.html
     */
    public function testRangeBetweenQuery()
    {
        $rule = [];
        $rule['field'] = 'age';
        $rule['operator'] = 'between';
        $rule['type'] = 'integer';
        $rule['value'] = [10, 20];

        $query = [];
        $query['range'] = [];
        $query['range']['age'] = [];
        $query['range']['age']['gte'] = 10;
        $query['range']['age']['lte'] = 20;

        $this->assertEquals($query, ES\getQueryHelper([], $rule));
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/query-dsl-range-query.html
     */
    public function testRangeGreaterThanQuery()
    {
        $rule = [];
        $rule['field'] = 'age';
        $rule['operator'] = 'greater';
        $rule['type'] = 'integer';
        $rule['value'] = 10;

        $query = [];
        $query['range'] = [];
        $query['range']['age'] = [];
        $query['range']['age']['gt'] = 10;

        $this->assertEquals($query, ES\getQueryHelper([], $rule));
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/query-dsl-match-query.html
     */
    public function testMatchQuery()
    {
        $rule = [];
        $rule['field'] = 'message';
        $rule['operator'] = 'contains';
        $rule['type'] = 'string';
        $rule['value'] = 'this is a test';

        $query = [];
        $query['match'] = [];
        $query['match']['message'] = 'this is a test';

        $this->assertEquals($query, ES\getQueryHelper([], $rule));
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/query-dsl-match-query.html#query-dsl-match-query-phrase
     */
    public function testMatchPhraseQuery()
    {
        $rule = [];
        $rule['field'] = 'message';
        $rule['operator'] = 'equal';
        $rule['type'] = 'string';
        $rule['value'] = 'this is a test';

        $query = [];
        $query['match_phrase'] = [];
        $query['match_phrase']['message'] = 'this is a test';

        $this->assertEquals($query, ES\getQueryHelper([], $rule));
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/query-dsl-terms-query.html
     */
    public function testTermsQuery()
    {
        $rule = [];
        $rule['field'] = 'user';
        $rule['operator'] = 'in';
        $rule['type'] = 'string';
        $rule['value'] = ['kimchy', 'elasticsearch'];

        $query = [];
        $query['terms'] = [];
        $query['terms']['user'] = ['kimchy', 'elasticsearch'];

        $this->assertEquals($query, ES\getQueryHelper([], $rule));
    }

    /**
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/2.0/query-dsl-exists-query.html
     */
    public function testExistsQuery()
    {
        $rule = [];
        $rule['field'] = 'user';
        $rule['operator'] = 'is_not_null';
        $rule['type'] = 'string';

        $query = [];
        $query['exists'] = [];
        $query['exists']['field'] = 'user';

        $this->assertEquals($query, ES\getQueryHelper([], $rule));
    }

    /**
     * @see https://stackoverflow.com/a/50381380/1858091
     */
    public function testSlopQuery()
    {
        $rule = [];
        $rule['field'] = 'message';
        $rule['operator'] = 'proximity';
        $rule['type'] = 'string';
        $rule['value'] = ['ki*y time', 2];

        $query = [];
        $query['match_phrase'] = [];
        $query['match_phrase']['message'] = [];
        $query['match_phrase']['message']['query'] = 'ki*y time';
        $query['match_phrase']['message']['slop'] = 2;

        $this->assertEquals($query, ES\getQueryHelper([], $rule));
    }

    public function testSimpleSyntaxQuery()
    {
        $rule = [];
        $rule['field'] = 'message';
        $rule['operator'] = 'syntax';
        $rule['type'] = 'string';
        $rule['value'] = '(new york city) OR (big apple)';

        $query = [];
        $query['query_string'] = [];
        $query['query_string']['query'] = '(new york city) OR (big apple)';

        $this->assertEquals($query, ES\getQueryHelper([], $rule));
    }

    public function testSyntaxQuery()
    {
        $rule = [];
        $rule['field'] = 'message';
        $rule['operator'] = 'syntax';
        $rule['type'] = 'string';
        $rule['value'] = [];
        $rule['value']['query'] = '(new york city) OR (big apple)';
        $rule['value']['default_operator'] = 'AND';

        $query = [];
        $query['query_string'] = [];
        $query['query_string']['query'] = '(new york city) OR (big apple)';
        $query['query_string']['default_operator'] = 'AND';

        $this->assertEquals($query, ES\getQueryHelper([], $rule));
    }

    public function testUnknownOperatorQuery()
    {
        $this->expectException(\Exception::class);

        $rule = [];
        $rule['field'] = 'message';
        $rule['operator'] = 'smooth';
        $rule['type'] = 'string';
        $rule['value'] = 'this is a test';
        ES\getQueryHelper([], $rule);
    }

    public function testShouldNegativeQueryCornerCase()
    {
        $group = [];
        $group['condition'] = 'OR';

        $rule = [];
        $rule['field'] = 'user';
        $rule['operator'] = 'is_null';
        $rule['type'] = 'string';

        $subquery = [];
        $subquery['exists'] = [];
        $subquery['exists']['field'] = 'user';

        $query = [];
        $query['bool'] = [];
        $query['bool']['must_not'] = [];
        $query['bool']['must_not'][] = $subquery;

        $this->assertEquals($query, ES\getQuery($group, $rule));
    }

    public function testMustNegativeClause()
    {
        $group = [];
        $group['condition'] = 'AND';

        $rule = [];
        $rule['field'] = 'user';
        $rule['operator'] = 'not_equal';
        $rule['type'] = 'string';
        $rule['value'] = 'elasticsearch';

        $this->assertEquals('must_not', ES\getClause($group, $rule));
    }

    public function testNestedGroupClause()
    {
        $group = [];
        $group['condition'] = 'AND';

        // NOTE: $rule is actually the shape of a "group"
        $rule = [];
        $rule['condition'] = 'OR';
        $rule['rules'] = [];

        $this->assertEquals('must', ES\getClause($group, $rule));
    }

    public function testMustNotClause()
    {
        $group = [];
        $group['condition'] = 'NOT';

        // NOTE: $rule is actually the shape of a "group"
        $rule = [];
        $rule['condition'] = 'OR';
        $rule['rules'] = [];

        $this->assertEquals('must_not', ES\getClause($group, $rule));
    }

    public function testMustNotNegativeClause()
    {
        $group = [];
        $group['condition'] = 'NOT';

        $rule = [];
        $rule['field'] = 'user';
        $rule['operator'] = 'not_equal';
        $rule['type'] = 'string';
        $rule['value'] = 'elasticsearch';

        $this->assertEquals('must', ES\getClause($group, $rule));
    }

    public function testUnknownConditionClause()
    {
        $this->expectException(\Exception::class);

        $group = [];
        $group['condition'] = 'XOR';

        // NOTE: $rule is actually the shape of a "group"
        $rule = [];
        $rule['condition'] = 'OR';
        $rule['rules'] = [];

        ES\getClause($group, $rule);
    }
}
