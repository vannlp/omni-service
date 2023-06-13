<?php


namespace App\V1\Controllers;

use App\Collection;
use App\Supports\Message;
use Illuminate\Http\Request;
use App\V1\Transformers\Collection\CollectionSearchTransformer;

class CollectionController extends BaseController
{
    public function search(Request $request)
    {
        $collections = Collection::search($request)
                        ->with(['products'])
                        ->paginate($request->input('limit', 20));

        return $this->response->paginator($collections, new CollectionSearchTransformer());
    }

    public function show($collectionId)
    {
        $collection = Collection::findOrFail($collectionId);

        return $this->response->item($collection, new CollectionSearchTransformer());
    }

    public function create(Request $request)
    {
        $attributes = $this->validate($request, [
            'name' => 'required',
            'description' => 'nullable'
        ]);

        Collection::create($attributes);

        return ['status' => Message::get("R001", 'Collection')];
    }

    public function update($collectionId, Request $request)
    {
        $collection = Collection::findOrFail($collectionId);

        $attributes = $this->validate($request, [
            'name' => 'required',
            'description' => 'nullable'
        ]);

        $collection->update($attributes);

        return ['status' => Message::get("R002", 'Collection')];
    }

    public function assignProducts($collectionId, Request $request)
    {
        $this->validate($request, [
            'product_ids' => 'required|array'
        ]);

        $collection = Collection::findOrFail($collectionId);

        $productIds = $request->input('product_ids');

        $collection->products()->sync($productIds);

        return ['status' => Message::get("R002", 'Collection')];
    }
}