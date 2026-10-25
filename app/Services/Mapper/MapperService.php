<?php

namespace App\Services\Mapper;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Exception\InvalidSource;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use CuyZ\Valinor\Mapper\Source\Source;

final readonly class MapperService
{
    private TreeMapper $mapper;

    public function __construct(
    ) {
        $this->mapper = (new MapperBuilder())->allowSuperfluousKeys()->mapper();
    }

    /**
     * @template T of object
     * @param class-string<T> $dtoClass
     * @param array<array-key, mixed> $data
     * @return T
     * @throws MappingError
     */
    public function mapFromArray(string $dtoClass, array $data): object
    {
        return $this
            ->mapper
            ->map(
                $dtoClass,
                Source::array($data)
            );
    }

    /**
     * @template T of object
     * @param class-string<T> $dtoClass
     * @return T
     * @throws MappingError
     * @throws InvalidSource
     */
    public function mapFromJson(string $dtoClass, string $data): object
    {
        return $this
            ->mapper
            ->map(
                $dtoClass,
                Source::json($data)->camelCaseKeys()
            );

    }
}
