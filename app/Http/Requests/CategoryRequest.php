<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class CategoryRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|unique:categories,name',
            'parent_id' => ['nullable', Rule::exists('categories', 'id')->whereNull('deleted_at')]
        ];
        // If the request is a PUT or PATCH request, we need to exclude the current category from the unique rule
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['name'] .= ',' . $this->route('category')->id;
        }

        return $rules;
    }
}
