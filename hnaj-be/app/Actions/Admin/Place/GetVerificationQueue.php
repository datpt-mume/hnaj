<?php

namespace App\Actions\Admin\Place;

use App\Repositories\AdminPlaceRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetVerificationQueue
{
    public function __construct(
        private readonly AdminPlaceRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, \App\Models\Place>
     */
    public function handle(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->repository->verificationQueue($filters, $perPage, $page);
    }
}
