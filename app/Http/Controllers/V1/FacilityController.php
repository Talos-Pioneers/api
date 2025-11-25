<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FacilityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $facilities = QueryBuilder::for(Facility::class)
            ->whereNotNull('type')
            ->allowedFilters([
                'slug',
                AllowedFilter::exact('type'),
            ])
            ->allowedSorts(['slug', 'type', 'created_at', 'updated_at'])
            ->defaultSort('slug')
            ->get();

        return FacilityResource::collection($facilities);
    }

    /**
     * Display the specified resource.
     */
    public function show(Facility $facility): FacilityResource
    {
        return new FacilityResource($facility);
    }
}
