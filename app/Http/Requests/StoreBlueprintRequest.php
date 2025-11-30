<?php

namespace App\Http\Requests;

use App\Enums\GameVersion;
use App\Enums\Region;
use App\Enums\ServerRegion;
use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function Laravel\Prompts\info;

class StoreBlueprintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        info(app()->getLocale());

        return $this->user()->can('create', \App\Models\Blueprint::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'version' => ['required', Rule::enum(GameVersion::class)],
            'description' => ['nullable', 'string', 'max:2500'],
            'status' => ['nullable', Rule::enum(Status::class)],
            'region' => ['nullable', Rule::enum(Region::class)],
            'server_region' => ['required', Rule::enum(ServerRegion::class)],
            'is_anonymous' => ['nullable', 'boolean'],
            'facilities' => ['nullable', 'array'],
            'facilities.*.id' => ['required', 'integer', 'exists:facilities,id'],
            'facilities.*.quantity' => ['required', 'integer', 'min:1'],
            'item_inputs' => ['nullable', 'array'],
            'item_inputs.*.id' => ['required', 'integer', 'exists:items,id'],
            'item_inputs.*.quantity' => ['required', 'integer', 'min:1'],
            'item_outputs' => ['nullable', 'array'],
            'item_outputs.*.id' => ['required', 'integer', 'exists:items,id'],
            'item_outputs.*.quantity' => ['required', 'integer', 'min:1'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'max:30720'],

        ];
    }
}
