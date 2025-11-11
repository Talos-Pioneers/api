<?php

namespace App\Http\Requests;

use App\Enums\GameVersion;
use App\Enums\Region;
use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlueprintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('blueprint'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:255'],
            'title' => ['sometimes', 'string', 'max:255'],
            'version' => ['sometimes', Rule::enum(GameVersion::class)],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(Status::class)],
            'region' => ['nullable', Rule::enum(Region::class)],
            'buildings' => ['nullable', 'array'],
            'buildings.*' => ['string'],
            'item_inputs' => ['nullable', 'array'],
            'item_inputs.*' => ['string'],
            'item_outputs' => ['nullable', 'array'],
            'item_outputs.*' => ['string'],
            'is_anonymous' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'max:10240'],
        ];
    }
}
