<?php

declare(strict_types=1);

namespace Keboola\ApiBundle\RequestMapper;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Tree\Message\Messages;
use CuyZ\Valinor\Mapper\Tree\Message\NodeMessage;
use CuyZ\Valinor\MapperBuilder;
use Keboola\ApiBundle\RequestMapper\Attribute\RequestKeyMap;
use Keboola\ApiBundle\RequestMapper\Exception\InvalidPayloadException;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataMapper
{
    /** @var array<class-string, array<non-empty-string, non-empty-string>> */
    private array $keyMapCache = [];

    public function __construct(
        private readonly MapperBuilder $mapperBuilder,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @template T of object
     * @param class-string<T> $objectType
     * @return T
     */
    public function mapData(
        string $objectType,
        mixed $data,
        string $errorMessage,
        int $errorCode,
        bool $enableFlexibleCasting = false,
        bool $enableExtraKeys = false,
    ): object {
        $keyMap = $this->keyMap($objectType);
        if ($keyMap !== []) {
            $data = self::applyKeyMap($data, $keyMap);
        }
        $sourceKeys = array_flip($keyMap);

        $mapperBuilder = $this->mapperBuilder;
        if ($enableFlexibleCasting) {
            $mapperBuilder = $mapperBuilder->enableFlexibleCasting();
        }
        if ($enableExtraKeys) {
            $mapperBuilder = $mapperBuilder->allowSuperfluousKeys();
        }
        $mapperBuilder = $mapperBuilder->allowPermissiveTypes();

        try {
            $object = $mapperBuilder->mapper()->map($objectType, $data);
        } catch (MappingError $e) {
            throw new InvalidPayloadException(
                $errorMessage,
                $errorCode,
                array_map(
                    fn(NodeMessage $message) => [
                        'path' => self::toSourcePath($message->node()->path(), $sourceKeys),
                        'message' => (string) $message,
                    ],
                    [...Messages::flattenFromNode($e->node())->errors()],
                ),
                $e,
            );
        }

        $violations = $this->validator->validate($object);
        if (count($violations) > 0) {
            throw new InvalidPayloadException($errorMessage, $errorCode, array_map(
                fn(ConstraintViolationInterface $error) => [
                    'path' => self::toSourcePath($error->getPropertyPath(), $sourceKeys),
                    'message' => $error->getMessage(),
                ],
                [...$violations],
            ));
        }

        return $object;
    }

    /**
     * Key map declared by {@see RequestKeyMap} on the mapped class, memoized per class.
     *
     * @param class-string $objectType
     * @return array<non-empty-string, non-empty-string>
     */
    private function keyMap(string $objectType): array
    {
        if (!array_key_exists($objectType, $this->keyMapCache)) {
            $attributes = (new ReflectionClass($objectType))->getAttributes(RequestKeyMap::class);

            $this->keyMapCache[$objectType] = $attributes === []
                ? []
                : $attributes[0]->newInstance()->map;
        }

        return $this->keyMapCache[$objectType];
    }

    /**
     * Renames source keys to the property names the mapper expects.
     *
     * A value sent directly under a rename *target* is dropped: only the declared source key may
     * populate the property, so the outcome never depends on the order of keys in the payload.
     *
     * @param array<non-empty-string, non-empty-string> $keyMap
     */
    private static function applyKeyMap(mixed $data, array $keyMap): mixed
    {
        if (!is_iterable($data)) {
            return $data;
        }

        /** @var iterable<array-key, mixed> $data */
        $result = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $keyMap, true)) {
                continue;
            }

            $result[$keyMap[$key] ?? $key] = $value;
        }

        return $result;
    }

    /**
     * Reports an error against the key the client actually sent rather than the property name.
     *
     * @param array<non-empty-string, non-empty-string> $sourceKeys Property name => source key.
     */
    private static function toSourcePath(string $path, array $sourceKeys): string
    {
        if ($sourceKeys === []) {
            return $path;
        }

        // Only the first segment can be renamed; the map applies to top-level payload keys.
        $segments = explode('.', $path, 2);
        $segments[0] = $sourceKeys[$segments[0]] ?? $segments[0];

        return implode('.', $segments);
    }
}
