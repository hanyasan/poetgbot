<?php

namespace App\Services\Mapper;

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
     */
    public function mapFromJson(string $dtoClass, string $data): object
    {
        return $this
            ->mapper
            ->map(
                $dtoClass,
                Source::json($data)
            );

    }
}
