<?php

namespace App\Http\Requests\Admin\Book;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', Book::find($this->route('id')));
    }

    public function rules(): array
    {
        $bookId = $this->route('id');

        return [
            'slug' => ['required', 'string', 'max:255', Rule::unique('books', 'slug')->ignore($bookId)],
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
