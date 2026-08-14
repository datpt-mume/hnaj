<?php

namespace App\Http\Requests\Admin\Place;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAdminPlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address_text' => ['required', 'string', 'max:2000'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'array', 'max:20'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website_url' => ['nullable', 'string', 'max:2048', 'url'],
            'google_maps_url' => ['required', 'string', 'max:2048', 'url'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0', 'gte:min_price'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'hidden'])],
            'opening_hours' => ['nullable', 'array', 'size:7'],
            'opening_hours.*.day_of_week' => ['required', 'integer', 'between:2,8'],
            'opening_hours.*.schedule_type' => ['required', Rule::in(['regular', 'all_day', 'closed'])],
            'opening_hours.*.opens_at' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'opening_hours.*.closes_at' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*.image_url' => ['required', 'string', 'max:2048', 'url'],
            'images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'thumbnail_image_id' => ['nullable', 'integer', 'exists:place_images,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'google_maps_url.required' => 'Google Maps URL là bắt buộc.',
            'google_maps_url.url' => 'Google Maps URL không hợp lệ.',
        ];
    }
}
