<?php

namespace App\Http\Controllers\Foodfleet;

use App\Actions\CreateDocument;
use App\Actions\UpdateDocument;
use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Foodfleet\Document as DocumentResource;
use App\Models\Foodfleet\Document;
use FreshinUp\FreshBusForms\Filters\GreaterThanOrEqualTo as FilterGreaterThanOrEqualTo;
use FreshinUp\FreshBusForms\Filters\LessThanOrEqualTo as FilterLessThanOrEqualTo;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Documents extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $documents = QueryBuilder::for(Document::class, $request)
            ->with('assigned')
            ->allowedSorts([
                'title',
                'type_id',
                'status_id',
                'created_at',
                'expiration_at',
                'created_by',
                'signed_at'
            ])
            ->allowedFilters([
                'title',
                Filter::exact('type_id'),
                Filter::exact('status_id'),
                Filter::exact('assigned_uuid'),
                Filter::exact('event_store_uuid'),
                Filter::custom('expiration_from', FilterGreaterThanOrEqualTo::class, 'expiration_at'),
                Filter::custom('expiration_to', FilterLessThanOrEqualTo::class, 'expiration_at'),
                Filter::custom('signed_from', FilterGreaterThanOrEqualTo::class, 'signed_at'),
                Filter::custom('signed_to', FilterLessThanOrEqualTo::class, 'signed_at')
            ])
            ->jsonPaginate();

        return DocumentResource::collection($documents);
    }

    /**
     * Generates an empty document.
     *
     * @return DocumentResource
     * @throws \Illuminate\Validation\ValidationException
     */
    public function showNewRecommendation()
    {
        return new DocumentResource(
            Document::make()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return DocumentResource
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, CreateDocument $action)
    {
        $user = $request->user();

        $this->validate($request, [
            'title' => 'required',
            'type_id' => 'integer|required',
            'status_id' => 'integer|required',
            'description' => 'required',
            'expiration_at' => 'date'
        ]);

        $inputs = $request->only(Document::FILLABLES);
        $inputs['created_by_uuid'] = $user->uuid;

        $document = $action->execute($inputs);

        return new DocumentResource($document);
    }

    /**
     * Display the specified resource.
     *
     * @param  Request  $request
     * @param $uuid
     * @return DocumentResource
     */
    public function show(Request $request, $uuid)
    {
        $document = QueryBuilder::for(Document::class, $request)
            ->allowedIncludes([
                'template'
            ])
            ->with('assigned')
            ->with('owner')
            ->where('uuid', $uuid)
            ->first();

        return new DocumentResource($document);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param $uuid
     * @return DocumentResource
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $uuid, UpdateDocument $action)
    {
        $this->validate($request, [
            'title' => 'string',
            'type_id' => 'integer',
            'status_id' => 'integer',
            'description' => 'string',
            'expiration_at' => 'date',
            'template_uuid' => 'exists:document_templates,uuid'
        ]);

        $inputs = $request->only(Document::FILLABLES);
        $inputs['uuid'] = $uuid;

        $document = $action->execute($inputs);

        return new DocumentResource($document);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $uuid
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy($uuid)
    {
        $document = Document::where('uuid', $uuid)->firstOrFail();
        $document->delete();
        return response()->json(null, SymfonyResponse::HTTP_NO_CONTENT);
    }

    /**
     * @param $uuid
     * @return DocumentResource|\Illuminate\Http\JsonResponse
     */
    public function accept($uuid)
    {
        $document = Document::whereUuid($uuid)->firstOrFail();
        if ($document->signed_at) {
            return response()->json([
                "message" => "Document [{$document->title}] already signed."
            ], 422);
        }
        $document->update([
            'signed_at' => now(),
            'status_id' => DocumentStatus::APPROVED
        ]);
        $document->load('template', 'owner', 'assigned');
        return new DocumentResource($document);
    }
}
