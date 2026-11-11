<?php

namespace App\Http\Requests\Admin\Book;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Book::class);
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255', 'unique:books,slug'],
            'type' => ['required', Rule::in(['FREE', 'PAID'])],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['DRAFT', 'PUBLISHED', 'ARCHIVED'])],
            'language' => ['required', 'string', 'max:5'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['integer', Rule::exists('authors', 'id')],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
            'translations.*.content' => ['nullable', 'string'],
            'translations.*.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.*.meta_description' => ['nullable', 'string'],
        ];
    }
}
