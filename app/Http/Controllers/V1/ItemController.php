<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $items = QueryBuilder::for(Item::class)
            ->allowedFilters([
                'slug',
                AllowedFilter::exact('type'),
            ])
            ->allowedSorts(['slug', 'type', 'created_at', 'updated_at'])
            ->defaultSort('slug')
            ->paginate(25)
            ->appends(request()->query());

        return ItemResource::collection($items);
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item): ItemResource
    {
        return new ItemResource($item);
    }
}
