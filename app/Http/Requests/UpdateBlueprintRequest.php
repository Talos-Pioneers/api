<?php

namespace App\Http\Requests;

use App\Enums\GameVersion;
use App\Enums\Region;
use App\Enums\ServerRegion;
use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'description' => ['nullable', 'string', 'max:2500'],
            'status' => ['nullable', Rule::enum(Status::class)],
            'region' => ['nullable', Rule::enum(Region::class)],
            'server_region' => ['required', Rule::enum(ServerRegion::class)],
            'facilities' => ['nullable', 'array'],
            'facilities.*.id' => ['required', 'integer', 'exists:facilities,id'],
            'facilities.*.quantity' => ['required', 'integer', 'min:1'],
            'item_inputs' => ['nullable', 'array'],
            'item_inputs.*.id' => ['required', 'integer', 'exists:items,id'],
            'item_inputs.*.quantity' => ['required', 'integer', 'min:1'],
            'item_outputs' => ['nullable', 'array'],
            'item_outputs.*.id' => ['required', 'integer', 'exists:items,id'],
            'item_outputs.*.quantity' => ['required', 'integer', 'min:1'],
            'is_anonymous' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'gallery' => ['nullable', 'array', 'max:5'],
            'gallery.*' => ['image', 'max:30720'],
            'gallery_keep_ids' => ['nullable', 'array'],
            'gallery_keep_ids.*' => ['required', 'integer', 'exists:media,id'],
            'gallery_order' => ['nullable', 'array'],
            'gallery_order.*' => ['required', 'string'],
            'width' => ['nullable', 'integer', 'min:1', 'max:50'],
            'height' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $galleryKeepIds = $this->input('gallery_keep_ids', []);
            $gallery = $this->file('gallery', []);

            $keptCount = is_array($galleryKeepIds) ? count($galleryKeepIds) : 0;
            $newCount = is_array($gallery) ? count($gallery) : 0;
            $totalCount = $keptCount + $newCount;

            if ($totalCount > 5) {
                $validator->errors()->add(
                    'gallery',
                    'The total number of images (kept + new) cannot exceed 5. You are trying to keep '.$keptCount.' image(s) and upload '.$newCount.' new image(s), which totals '.$totalCount.' image(s).'
                );
            }
        });
    }
}
