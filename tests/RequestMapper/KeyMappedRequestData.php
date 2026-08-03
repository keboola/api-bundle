<?php

declare(strict_types=1);

namespace Keboola\ApiBundle\Tests\RequestMapper;

use Keboola\ApiBundle\RequestMapper\Attribute\RequestKeyMap;
use Symfony\Component\Validator\Constraints as Assert;

#[RequestKeyMap(['#data' => 'data'])]
class KeyMappedRequestData
{
    public function __construct(
        #[Assert\Length(max: 16)]
        public readonly string $data,
        public readonly ?string $name = null,
    ) {
    }
}
