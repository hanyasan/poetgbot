<?php

namespace App\Services\Mapper;

use CuyZ\Valinor\MapperBuilder;
use Illuminate\Support\Carbon;
use CuyZ\Valinor\Mapper\Source\Source;

class MapperService
{
    public function mapFromArray(string $dtoClass, array $data): object
    {
        return (new MapperBuilder())
            ->allowSuperfluousKeys()
            ->registerConstructor(function (string $time): Carbon {
                return Carbon::parse($time);
            })
            ->mapper()
            ->map(
                $dtoClass,
                Source::array($data)
            );
    }

    public function mapFromJson(string $dtoClass, string $data): object
    {
        return (new MapperBuilder())
            ->allowSuperfluousKeys()
            ->registerConstructor(function (string $time): Carbon {
                return Carbon::parse($time);
            })
            ->mapper()
            ->map(
                $dtoClass,
                Source::json($data)
            );

    }
}
