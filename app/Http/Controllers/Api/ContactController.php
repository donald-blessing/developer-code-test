<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\User;
use App\Notifications\NewContactMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;
use Throwable;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('perPage', 15);
            $page = $request->get('page', null);
            $pageName = $request->get('pageName', 'pageName');
            $contacts = Contact::query()->orderBy('created_at')->paginate($perPage, ['*'], $pageName, $page);
            return response()->json([
                    'status' => 'success',
                    'message' => 'Contacts retrieved successfully',
                    'contacts' => ContactResource::collection($contacts),
                ]
            );
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public
    function store(
        Request $request
    ): JsonResponse {
        try {
            return DB::transaction(function () use ($request) {
                // Validate request
                $validator = Validator::make($request->all(), [
                    'name' => 'required|string',
                    'email' => 'required|email',
                    'message' => 'required|string',
                    'attachment' => 'required|file|mimes:csv,png,svg',
                ]);

                // If validation fails, return error response
                if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $validator->errors()->first(),
                    ], 422);
                }
                $validated = $validator->validated();

                //ensure that upload is not a duplicate
                $contact = Contact::query()
                    ->where('name', $validated['name'])
                    ->where('email', $validated['email'])
                    ->where('message', $validated['message'])
                    ->first();


                if ($contact) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Duplicate upload',
                    ], 409);
                }


                $contact = Contact::query()->create($validated);
                if ($request->hasFile('attachment')) {
                    $contact->addMediaFromRequest('attachment')->toMediaCollection(Contact::MEDIA_COLLECTION);
                }

                $user = User::query()->first();
                $user->notify(new NewContactMessage("A new user has visited on your application and sent a message."));

                return response()->json([
                    'status' => 'success',
                    'message' => 'Contact created successfully',
                    'contact' => new ContactResource($contact),
                ]);
            });
        } catch (FileUnacceptableForCollection  $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public
    function show(
        int $id
    ): JsonResponse {
        try {
            return DB::transaction(function () use ($id) {
                $contact = Contact::query()->findOrFail($id);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Contact retrieved successfully',
                    'contact' => new ContactResource($contact),
                ]);
            });
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contact not found',
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse
     */
    public
    function update(
        Request $request,
        int $id
    ): JsonResponse {
        try {
            return DB::transaction(function () use ($request, $id) {
                // Validate request
                $validator = Validator::make($request->all(), [
                    'name' => 'required|string',
                    'email' => 'required|email',
                    'message' => 'required|string',
                    'attachment' => 'sometimes|file|mimes:csv,png,svg',
                ]);

                // If validation fails, return error response
                if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $validator->errors()->first(),
                    ], 422);
                }

                $validated = $validator->validated();

                $contact = Contact::query()->findOrFail($id);

                $contact->update($validated);

                if ($request->hasFile('attachment')) {
                    $contact->clearMediaCollection(Contact::MEDIA_COLLECTION);
                    $contact->addMediaFromRequest('attachment')->toMediaCollection(Contact::MEDIA_COLLECTION);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Contact updated successfully',
                    'contact' => new ContactResource($contact),
                ]);
            });
        } catch (FileUnacceptableForCollection  $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'The contact does not exist',
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public
    function destroy(
        int $id
    ): JsonResponse {
        try {
            $contact = Contact::query()->findOrFail($id);
            $contact->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Contact deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
