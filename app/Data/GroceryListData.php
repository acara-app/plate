<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/** @codeCoverageIgnore */
final class GroceryListData extends Data
{
    /**
     * @param  DataCollection<int, GroceryItemData>  $items
     */
    public function __construct(
        #[DataCollectionOf(GroceryItemData::class)]
        public DataCollection $items,
    ) {}
}
