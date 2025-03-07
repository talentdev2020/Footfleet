<?php


namespace App\Http\Controllers\Foodfleet;

use App\Enums\StoreStatus as StoreStatusEnum;
use App\Filters\BelongsToWhereInIdEquals;
use App\Filters\BelongsToWhereInUuidEquals;
use App\Http\Controllers\Controller;
use App\Http\Resources\Foodfleet\Store\Statistic;
use App\Http\Resources\Foodfleet\Store\Store as StoreResource;
use App\Http\Resources\Foodfleet\Event as EventResource;
use App\Http\Resources\Foodfleet\Store\StoreServiceSummary as StoreServiceSummaryResource;
use App\Http\Resources\Foodfleet\Store\StoreSummary as StoreSummaryResource;
use App\Models\Foodfleet\Event;
use App\Models\Foodfleet\Store as StoreModel;
use App\Models\Foodfleet\StoreStatus;
use App\Sorts\Stores\OwnerNameSort;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Sort;
use Square\Exceptions\ApiException;
use Square\SquareClient;

class Store extends Controller
{
    public function index(Request $request)
    {
        $stores = QueryBuilder::for(StoreModel::class, $request)
            ->allowedIncludes([
                'tags',
                'addresses',
                'events',
                'supplier',
                'supplier.admin',
                'status',
                'owner',
                'type'
            ])
            ->allowedSorts([
                'name',
                'status_id',
                'created_at',
                'state_of_incorporation',
                Sort::custom('owner', new OwnerNameSort()),
            ])
            ->allowedFilters([
                'name',
                'state_of_incorporation',
                Filter::custom('status_id', BelongsToWhereInIdEquals::class, 'status'),
                Filter::custom('tag', BelongsToWhereInUuidEquals::class, 'tags'),
                Filter::custom('owner_uuid', BelongsToWhereInUuidEquals::class, 'owner'),
                Filter::custom('type_id', BelongsToWhereInIdEquals::class, 'type'),
                Filter::exact('uuid'),
                Filter::exact('supplier_uuid')
            ])
            ->jsonPaginate();

        return StoreResource::collection($stores);
    }

    public function update(Request $request, $uuid)
    {
        $this->validate($request, [
            'status_id' => 'integer',
            'commission_rate' => 'integer',
            'commission_type' => 'integer',
            'event_uuid' => 'string|exists:events,uuid',
            'tags' => 'array'
        ]);

        /** @var StoreModel $store */
        $store = StoreModel::where('uuid', $uuid)->firstOrFail();
        $store->update($request->only(StoreModel::FILLABLES));

        // File upload in base 64
        $store->setImage($request->input('image'), $request->has('image'));

        // array of tag uuid
        if ($request->has('tags')) {
            // TODO: validate array of tag uuid
            $store->tags()->sync($request->input('tags'));
        }

        $event_uuid = $request->get('event_uuid');
        $commission_rate = $request->get('commission_rate');
        $commission_type = $request->get('commission_type');
        if (!empty($event_uuid) && !empty($commission_rate) && !empty($commission_type)) {
            $event = Event::where('uuid', $event_uuid)->first();
            $store->events()->updateExistingPivot(
                $event,
                compact('commission_rate', 'commission_type')
            );
        }

        $store->load('tags');
        return new StoreResource($store);
    }

    public function show(Request $request, $uuid)
    {
        $store = QueryBuilder::for(StoreModel::class, $request)
            ->where('uuid', $uuid)
            ->allowedIncludes([
                'menus',
                'tags',
                'documents',
                'events',
                'supplier',
                'supplier.admin',
                'status',
                'owner',
                'areas'
            ]);

        // Include eventsCount in the query if needed
        if ($request->has('provide') && $request->get('provide') == 'events-count') {
            $store->withCount('events');
        }

        return new StoreResource($store->firstOrFail());
    }

    public function summary(Request $request, $uuid)
    {
        $store = QueryBuilder::for(StoreModel::class, $request)
            ->with('tags')
            ->allowedIncludes([
                'owner'
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return new StoreSummaryResource($store);
    }

    public function serviceSummary(Request $request, $uuid)
    {
        $store = QueryBuilder::for(StoreModel::class, $request)
            ->with('events')
            ->where('uuid', $uuid)
            ->firstOrFail();

        return new StoreServiceSummaryResource($store);
    }

    public function destroy($uuid)
    {
        $store = StoreModel::where('uuid', $uuid)->firstOrFail();
        $events_count = $store->events->count();
        if ($events_count > 0) {
            return response()->json([
                'message' => 'This Fleet Member is currently assigned to an Event,
                please unassign it from the event first.'
            ], 405);
        }
        $store->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function showNewRecommendation()
    {
        return new StoreResource(
            StoreModel::make(
                ([
                    'status_id' => StoreStatusEnum::DRAFT
                ])
            )
        );
    }

    public function store(Request $request)
    {
        $authUser = $request->user();
        $rules = [
            'owner_uuid' => 'exists:users,uuid',
            'type_id' => 'exists:store_types,id',
            'status_id' => 'exists:store_statuses,id',
            'supplier_uuid' => 'exists:companies,uuid',
            'square_id' => 'string',
            'name' => 'required|string',
            'tags' => 'array',
            'size' => 'integer',
            'contact_phone' => 'string',
            'state_of_incorporation' => 'string',
            'website' => 'url',
            'twitter' => 'url',
            'facebook' => 'url',
            'instagram' => 'url',
            'staff_notes' => 'string',
        ];
        $this->validate($request, $rules);
        $data = $request->only(StoreModel::FILLABLES);
        $data['supplier_uuid'] = optional($authUser->company)->uuid;
        /** @var StoreModel $store */
        $store = StoreModel::create($data);

        // File upload in base 64
        $store->setImage($request->input('image'), $request->has('image'));

        // list of tag uuid
        $tags = $request->input('tags');
        if ($tags) {
            // TODO: validate tags
            $store->tags()->sync($tags);
        }
        $store->load('tags');
        return new StoreResource($store);
    }

    public function events(Request $request, $uuid)
    {
        /** @var StoreModel $store */
        $store = StoreModel::where('uuid', $uuid)->firstOrFail();
        $events = QueryBuilder::for(
            Event::whereIn(
                'uuid',
                $store->events()->pluck('events.uuid')->toArray()
            ),
            $request
        )
            ->allowedSorts([
                'status_id',
                'created_at',
                'start_at',
                'name',
                'type_id'
            ])
            ->allowedFilters([
                'name',
                Filter::exact('status_id'),
                Filter::exact('uuid'),
            ])
            ->jsonPaginate();

        return EventResource::collection($events);
    }

    public function stats()
    {
        $states = StoreStatus::withCount('stores')->get();

        return Statistic::collection($states);
    }

    /**
     * @param  Request  $request
     * @param  String $uuid
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResource
     * @throws \Exception
     */
    public function locations(Request $request, $uuid)
    {
        $store = StoreModel::where('uuid', $uuid)->firstOrFail();
        if (!$store->square_access_token) {
            // TODO: not returning error because front end has trouble catching it
            // this should be a 4xx
            return new JsonResource([]);
        }
        $client = new SquareClient([
            'accessToken' => $store->square_access_token,
            'environment' => config('services.square.environment'),
        ]);
        try {
            $locationsApi = $client->getLocationsApi();
            $apiResponse = $locationsApi->listLocations();
            if (!$apiResponse->isSuccess()) {
                return new JsonResource([]);
                // TODO: not returning error because front end has trouble catching it
                // this should be a 4xx
                // return (new JsonResource($apiResponse->getErrors()))
                //    ->toResponse($request)
                //    ->setStatusCode(400);
            }

            // expected output []{ square_id: string, name: string }
            $listLocationsResponse = $apiResponse->getResult();
            $locations = $listLocationsResponse->getLocations();

            // name: string, // business name
            // id: string, // location id
            return new JsonResource($locations);
        } catch (ApiException $e) {
            throw new \Exception("Received error while calling Square: " . $e->getMessage());
        }
    }
}
