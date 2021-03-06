<?php

namespace spec\Pim\Bundle\EnrichBundle\ProductQueryBuilder;

use Akeneo\Tool\Component\StorageUtils\Cursor\CursorInterface;
use PhpSpec\ObjectBehavior;
use Akeneo\Pim\Enrichment\Bundle\Elasticsearch\SearchQueryBuilder;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Pim\Component\Catalog\Query\Filter\Operators;
use Pim\Component\Catalog\Query\ProductQueryBuilderInterface;
use Prophecy\Argument;

class ProductAndProductModelQueryBuilderSpec extends ObjectBehavior
{
    function let(ProductQueryBuilderInterface $pqb)
    {
        $this->beConstructedWith($pqb);
    }

    function it_is_a_product_query_builder()
    {
        $this->shouldImplement(ProductQueryBuilderInterface::class);
    }

    function it_adds_a_filter($pqb)
    {
        $pqb->addFilter('id', '=', '42', [])->shouldBeCalled();
        $this->addFilter('id', '=', '42', []);
    }

    function it_adds_a_sorter($pqb)
    {
        $pqb->addSorter('sku', 'DESC', [])->shouldBeCalled();
        $this->addSorter('sku', 'DESC', []);
    }

    function it_provides_the_raw_filters($pqb)
    {
        $pqb->getRawFilters()->willReturn(['an array with awesome raw filters inside']);
        $this->getRawFilters()->shouldReturn(['an array with awesome raw filters inside']);
    }

    function it_provides_a_query_builder_once_configured($pqb, SearchQueryBuilder $searchQb)
    {
        $pqb->getQueryBuilder()->willReturn($searchQb);
        $this->getQueryBuilder()->shouldReturn($searchQb);
    }

    function it_configures_the_query_builder($pqb, SearchQueryBuilder $searchQb)
    {
        $pqb->setQueryBuilder($searchQb)->willReturn($this);
        $this->setQueryBuilder($searchQb)->shouldReturn($this);
    }

    function it_executes_the_query_by_adding_a_filter_on_attributes($pqb, CursorInterface $cursor, SearchQueryBuilder $sqb)
    {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'foo',
                    'operator' => 'CONTAINS',
                    'value'    => '42',
                    'context'  => [],
                    'type'     => 'attribute'
                ],
                [
                    'field'    => 'bar',
                    'operator' => 'IN LIST',
                    'value'    => ['toto'],
                    'context'  => [],
                    'type'     => 'field'
                ],
                [
                    'field'    => 'baz',
                    'operator' => 'EQUALS',
                    'value'    => 'sku_893042',
                    'context'  => [],
                    'type'     => 'attribute'
                ],
            ]
        );

        $pqb->addFilter('parent', Operators::IS_EMPTY, null, [])->shouldNotBeCalled();
        $pqb->execute()->willReturn($cursor);
        $pqb->getQueryBuilder()->willReturn($sqb);

        $sqb->addFilter([
            'bool' => [
                'must_not' => [
                    'bool' => [
                        'filter' => [
                            [
                                'terms' => [ 'attributes_of_ancestors' => ['foo']]
                            ],
                            [
                                'terms' => [ 'attributes_of_ancestors' => ['baz']]
                            ]
                        ],
                    ],
                ]
            ]
        ])->shouldBeCalled();

        $this->execute()->shouldReturn($cursor);
    }

    function it_executes_the_query_by_adding_a_filter_on_attributes_and_categories($pqb, CursorInterface $cursor, SearchQueryBuilder $sqb)
    {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'foo',
                    'operator' => 'CONTAINS',
                    'value'    => '42',
                    'context'  => [],
                    'type'     => 'attribute'
                ],
                [
                    'field'    => 'bar',
                    'operator' => 'IN',
                    'value'    => ['toto'],
                    'context'  => [],
                    'type'     => 'field'
                ],
                [
                    'field'    => 'categories',
                    'operator' => 'IN',
                    'value'    => ['category_A'],
                    'context'  => [],
                    'type'     => 'field'
                ],
            ]
        );

        $pqb->addFilter('parent', Operators::IS_EMPTY, null, [])->shouldNotBeCalled();
        $pqb->execute()->willReturn($cursor);
        $pqb->getQueryBuilder()->willReturn($sqb);
        $sqb->addFilter([
            'bool' => [
                'must_not' => [
                    'bool' => [
                        'filter' => [
                            [
                                'terms' => [ 'attributes_of_ancestors' => ['foo']]
                            ],
                            [
                                'terms' => [ 'categories_of_ancestors' => ['category_A']]
                            ]
                        ],
                    ],
                ]
            ]
        ])->shouldBeCalled();

        $this->execute()->shouldReturn($cursor);
    }

    function it_executes_the_query_by_adding_a_default_filter_on_parents($pqb, CursorInterface $cursor)
    {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'bar',
                    'operator' => 'IN LIST',
                    'value'    => ['toto'],
                    'context'  => [],
                    'type'     => 'field'
                ],
            ]
        );

        $pqb->addFilter('parent', Operators::IS_EMPTY, null, [])->shouldBeCalled();
        $pqb->execute()->willReturn($cursor);

        $this->execute()->shouldReturn($cursor);
    }

    function it_executes_the_query_by_adding_a_default_filter_on_parents_when_a_filter_on_category_does_not_trigger_aggregation($pqb, CursorInterface $cursor)
    {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'categories',
                    'operator' => 'IN OR UNCLASSIFIED',
                    'value'    => ['toto'],
                    'context'  => [],
                    'type'     => 'field'
                ],
            ]
        );

        $pqb->addFilter('parent', Operators::IS_EMPTY, null, [])->shouldBeCalled();
        $pqb->execute()->willReturn($cursor);

        $this->execute()->shouldReturn($cursor);
    }

    function it_executes_the_query_with_operator_is_empty_on_an_attribute($pqb, CursorInterface $cursor, SearchQueryBuilder $sqb)
    {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'foo',
                    'operator' => 'EMPTY',
                    'value'    => null,
                    'context'  => [],
                    'type'     => 'attribute',
                ],
                [
                    'field'    => 'foo_currency1',
                    'operator' => 'EMPTY FOR CURRENCY',
                    'value'    => null,
                    'context'  => [],
                    'type'     => 'attribute',
                ],
                [
                    'field'    => 'foo_currency2',
                    'operator' => 'EMPTY ON ALL CURRENCIES',
                    'value'    => null,
                    'context'  => [],
                    'type'     => 'attribute',
                ],
                [
                    'field'    => 'bar',
                    'operator' => 'IN',
                    'value'    => ['toto'],
                    'context'  => [],
                    'type'     => 'field',
                ],
                [
                    'field'    => 'categories',
                    'operator' => 'IN',
                    'value'    => ['category_A'],
                    'context'  => [],
                    'type'     => 'field',
                ],
            ]
        );

        $pqb->addFilter('parent', Operators::IS_EMPTY, null, [])->shouldNotBeCalled();
        $pqb->execute()->willReturn($cursor);
        $pqb->getQueryBuilder()->willReturn($sqb);
        $sqb->addFilter([
            'bool' => [
                'must_not' => [
                    'bool' => [
                        'filter' => [
                            [
                                'terms' => ['attributes_of_ancestors' => ['foo']],
                            ],
                            [
                                'terms' => ['attributes_of_ancestors' => ['foo_currency1']],
                            ],
                            [
                                'terms' => ['attributes_of_ancestors' => ['foo_currency2']],
                            ],
                            [
                                'terms' => ['categories_of_ancestors' => ['category_A']],
                            ],
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();
        $sqb->addFilter([
            'terms' => ['attributes_for_this_level' => ['foo', 'foo_currency1', 'foo_currency2']],
        ])->shouldBeCalled();

        $this->execute()->shouldReturn($cursor);
    }

    function it_does_not_add_a_default_filter_on_parents_when_there_is_a_source_attribute_filter(
        $pqb,
        CursorInterface $cursor,
        SearchQueryBuilder $sqb
    ) {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'bar',
                    'operator' => 'IN LIST',
                    'value'    => ['toto'],
                    'context'  => [],
                    'type'     => 'attribute'
                ],
            ]
        );

        $pqb->addFilter('parent', Argument::cetera())->shouldNotBeCalled();
        $pqb->execute()->willReturn($cursor);
        $pqb->getQueryBuilder()->willReturn($sqb);
        $sqb->addFilter([
            'bool' => [
                'must_not' => [
                    'bool' => [
                        'filter' => [
                            [
                                'terms' => [ 'attributes_of_ancestors' => ['bar']]
                            ]
                        ],
                    ],
                ]
            ]
        ])->shouldBeCalled();

        $this->execute()->shouldReturn($cursor);
    }

    function it_does_not_add_a_default_filter_on_parents_when_there_is_a_source_parent_filter($pqb, CursorInterface $cursor)
    {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'parent',
                    'operator' => 'IN LIST',
                    'value'    => ['toto'],
                    'context'  => [],
                    'type'     => 'field'
                ],
            ]
        );

        $pqb->addFilter('parent', Argument::cetera())->shouldNotBeCalled();
        $pqb->execute()->willReturn($cursor);

        $this->execute()->shouldReturn($cursor);
    }

    function it_does_not_add_a_default_filter_on_parents_nor_group_when_there_is_a_filter_on_enabled($pqb, CursorInterface $cursor)
    {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'enabled',
                    'operator' => '=',
                    'value'    => true,
                    'context'  => [],
                    'type'     => 'field'
                ],
            ]
        );

        $pqb->addFilter('entity_type', '=', ProductInterface::class, Argument::cetera())->shouldBeCalled();
        $pqb->addFilter('parent', Argument::cetera())->shouldNotBeCalled();
        $pqb->execute()->willReturn($cursor);

        $this->execute()->shouldReturn($cursor);
    }

    function it_does_not_add_a_default_filter_on_parents_when_there_is_a_source_id_filter($pqb, CursorInterface $cursor)
    {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'id',
                    'operator' => 'IN LIST',
                    'value'    => ['toto'],
                    'context'  => [],
                    'type'     => 'field'
                ],
            ]
        );

        $pqb->addFilter('parent', Argument::cetera())->shouldNotBeCalled();
        $pqb->execute()->willReturn($cursor);

        $this->execute()->shouldReturn($cursor);
    }

    function it_does_not_add_a_default_filter_on_parents_when_there_is_a_source_identifier_filter($pqb, CursorInterface $cursor)
    {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'identifier',
                    'operator' => 'IN LIST',
                    'value'    => ['toto'],
                    'context'  => [],
                    'type'     => 'field'
                ],
            ]
        );

        $pqb->addFilter('parent', Argument::cetera())->shouldNotBeCalled();
        $pqb->execute()->willReturn($cursor);

        $this->execute()->shouldReturn($cursor);
    }

    function it_does_not_add_a_default_filter_on_parents_when_there_is_a_source_entity_type_filter($pqb, CursorInterface $cursor)
    {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'entity_type',
                    'operator' => 'EQUALS',
                    'value'    => 'toto',
                    'context'  => [],
                    'type'     => 'field'
                ],
            ]
        );

        $pqb->addFilter('parent', Argument::cetera())->shouldNotBeCalled();
        $pqb->execute()->willReturn($cursor);

        $this->execute()->shouldReturn($cursor);
    }

    function it_does_not_add_a_default_filter_on_parents_when_there_is_a_source_ancestor_filter($pqb, CursorInterface $cursor)
    {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'ancestor.id',
                    'operator' => 'IN LIST',
                    'value'    => ['toto'],
                    'context'  => [],
                    'type'     => 'field'
                ],
            ]
        );

        $pqb->addFilter('parent', Argument::cetera())->shouldNotBeCalled();
        $pqb->execute()->willReturn($cursor);

        $this->execute()->shouldReturn($cursor);
    }

    function it_does_not_add_a_default_filter_on_parents_when_there_is_a_source_ancestor_or_self_filter($pqb, CursorInterface $cursor)
    {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'self_and_ancestor.id',
                    'operator' => 'IN LIST',
                    'value'    => ['toto'],
                    'context'  => [],
                    'type'     => 'field'
                ],
            ]
        );

        $pqb->addFilter('parent', Argument::cetera())->shouldNotBeCalled();
        $pqb->execute()->willReturn($cursor);

        $this->execute()->shouldReturn($cursor);
    }

    function it_does_not_add_a_default_filter_on_parents_when_there_is_a_filter_on_category_with_operator_IN(
        $pqb,
        CursorInterface $cursor,
        SearchQueryBuilder $sqb
    ) {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'categories',
                    'operator' => 'IN',
                    'value'    => ['category_A', 'category_B'],
                    'context'  => [],
                    'type'     => 'field'
                ],
            ]
        );

        $pqb->addFilter('parent', Argument::cetera())->shouldNotBeCalled();
        $pqb->execute()->willReturn($cursor);
        $pqb->getQueryBuilder()->willReturn($sqb);

        $this->execute()->shouldReturn($cursor);
    }
    function it_does_not_aggregate_when_there_is_a_filter_on_parent($pqb, CursorInterface $cursor,  SearchQueryBuilder $sqb)
    {
        $pqb->getRawFilters()->willReturn(
            [
                [
                    'field'    => 'parent',
                    'operator' => '=',
                    'value'    => 'model-tshirt-divided-blue',
                    'context'  => [],
                    'type'     => 'field'
                ],
                [
                    'field'    => 'foo',
                    'operator' => 'CONTAINS',
                    'value'    => '42',
                    'context'  => [],
                    'type'     => 'attribute'
                ],
                [
                    'field'    => 'categories',
                    'operator' => 'IN LIST',
                    'value'    => ['category_A', 'category_'],
                    'context'  => [],
                    'type'     => 'field'
                ],
            ]
        );

        $sqb->addFilter(Argument::cetera())->shouldNotBeCalled();

        $pqb->execute()->willReturn($cursor);
        $pqb->getQueryBuilder()->willReturn($sqb);
        $this->execute()->shouldReturn($cursor);
    }
}
