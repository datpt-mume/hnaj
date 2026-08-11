<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscoveryMetadataResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'categories' => CategoryResource::collection($this->resource['categories']),
            'districts' => DistrictResource::collection($this->resource['districts']),
            'tags' => TagResource::collection($this->resource['tags']),
        ];
    }
}
