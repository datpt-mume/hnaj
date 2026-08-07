<?php

namespace App\Http\Requests\Place;

use App\Actions\Place\SearchPlaces;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation cho endpoint GET /api/places/search.
 *
 * `q` bắt buộc và được trim; không chấp nhận query rỗng hoặc toàn khoảng
 * trắng (FE không gọi API khi query rỗng, nhưng backend vẫn tự kiểm tra vì
 * API là public boundary). `page`/`per_page` tùy chọn, mặc định trang 1 và
 * 10 kết quả (tối đa 50).
 */
class SearchPlaceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:'.SearchPlaces::MAX_QUERY_LENGTH],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:'.SearchPlaces::MAX_PER_PAGE,
            ],
        ];
    }

    /**
     * Query đã trim; rỗng nếu client gửi chuỗi toàn khoảng trắng (validation
     * `required` đã chặn, nhưng giữ an toàn ở đây).
     */
    public function searchQuery(): string
    {
        return trim((string) $this->validated('q', ''));
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? SearchPlaces::DEFAULT_PER_PAGE);
    }
}
