<?php

namespace App\Actions\Discovery;

use App\Repositories\DiscoveryMetadataRepository;

final readonly class GetDiscoveryMetadata
{
    public function __construct(
        private DiscoveryMetadataRepository $metadata,
    ) {}

    /**
     * @return array{
     *     categories: \Illuminate\Database\Eloquent\Collection,
     *     districts: \Illuminate\Database\Eloquent\Collection,
     *     tags: \Illuminate\Database\Eloquent\Collection
     * }
     */
    public function handle(): array
    {
        return [
            'categories' => $this->metadata->activeCategories(),
            'districts' => $this->metadata->activeDistricts(),
            'tags' => $this->metadata->activeTags(),
        ];
    }
}
