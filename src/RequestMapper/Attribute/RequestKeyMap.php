<?php

declare(strict_types=1);

namespace Keboola\ApiBundle\RequestMapper\Attribute;

use Attribute;

/**
 * Declares that some payload fields arrive under a wire key that cannot be used as a PHP
 * property name — for instance the Keboola `#`-prefixed convention for encrypted values.
 *
 * The map is declared on the payload class itself, so the wire contract lives next to the
 * properties it describes rather than at every call site:
 *
 * ```php
 * #[RequestKeyMap(['#data' => 'data'])]
 * final readonly class CreateCredentialsPayload
 * {
 *     public function __construct(
 *         public string $data,
 *     ) {}
 * }
 * ```
 *
 * A value sent directly under a rename *target* (`data` above) is dropped — only the wire key
 * may populate the property, so the result never depends on the order of keys in the request.
 * Validation errors are reported under the wire key, not the property name.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class RequestKeyMap
{
    /**
     * @param non-empty-array<non-empty-string, non-empty-string> $map Wire key => property name.
     */
    public function __construct(
        public readonly array $map,
    ) {
    }
}
