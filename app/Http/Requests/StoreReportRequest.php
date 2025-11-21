<?php

namespace App\Http\Requests;

use App\Models\Blueprint;
use App\Models\BlueprintCollection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Report::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reportable_type' => [
                'required',
                'string',
                Rule::in([Blueprint::class, BlueprintCollection::class]),
            ],
            'reportable_id' => [
                'required',
                'string',
                Rule::exists($this->getReportableTable(), 'id'),
            ],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get the table name for the reportable type.
     */
    protected function getReportableTable(): string
    {
        $reportableType = $this->input('reportable_type');

        return match ($reportableType) {
            Blueprint::class => 'blueprints',
            BlueprintCollection::class => 'blueprint_collections',
            default => 'blueprints', // fallback, will fail validation if type is invalid
        };
    }
}
