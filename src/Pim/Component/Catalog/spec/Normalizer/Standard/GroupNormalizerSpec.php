<?php

namespace spec\Pim\Component\Catalog\Normalizer\Standard;

use PhpSpec\ObjectBehavior;
use Akeneo\Pim\Enrichment\Component\Product\Model\GroupInterface;
use Akeneo\Pim\Structure\Component\Model\GroupTypeInterface;
use Pim\Component\Catalog\Normalizer\Standard\TranslationNormalizer;

class GroupNormalizerSpec extends ObjectBehavior
{
    function let(TranslationNormalizer $translationNormalizer)
    {
        $this->beConstructedWith($translationNormalizer);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pim\Component\Catalog\Normalizer\Standard\GroupNormalizer');
    }

    function it_is_a_normalizer()
    {
        $this->shouldImplement('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    }

    function it_supports_standard_normalization(GroupInterface $group, GroupTypeInterface $groupType)
    {
        $group->getType()->willReturn($groupType);

        $this->supportsNormalization(new \stdClass(), 'standard')->shouldReturn(false);
        $this->supportsNormalization($group, 'xml')->shouldReturn(false);
        $this->supportsNormalization($group, 'json')->shouldReturn(false);
    }

    function it_normalizes_group($translationNormalizer, GroupInterface $group, GroupTypeInterface $groupType)
    {
        $translationNormalizer->normalize($group, 'standard', [])->willReturn([]);

        $group->getCode()->willReturn('my_code');
        $group->getType()->willReturn($groupType);
        $groupType->getCode()->willReturn('RELATED');

        $this->normalize($group)->shouldReturn([
            'code'   => 'my_code',
            'type'   => 'RELATED',
            'labels' => []
        ]);
    }
}
