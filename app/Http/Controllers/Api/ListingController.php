<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ListingController extends Controller
{
    use ApiResponseTrait;
    public function userListings(Request $request)
    {
        // Eager load rentalOptions relationship
        $listings = Listing::with(['rentalOptions']) // Corrected to eager load rentalOptions
            ->where('user_id', Auth::id()) // Filter listings by user_id
            ->get();

        // If status filter is applied, filter the listings by status (draft or published)
        if ($request->has('status') && in_array($request->status, ['draft', 'published'])) {
            $listings = $listings->where('status', $request->status)->values();
        }

        // Map over listings and attach rental options if the type is 'rent'
        $listings = $listings->map(function ($listing) {
            if ($listing->type == 'rent' && $listing->rentalOptions->isNotEmpty()) {
                $listing->rental_options = $listing->rentalOptions; // Attach rental options for rent listings
            } else {
                $listing->rental_options = null; // Set to null if not a rent listing or no rental options
            }
            return $listing;
        });

        // Return the listings as JSON
        return response()->json([
            'message' => 'Listings retrieved successfully',
            'listings' => $listings
        ], 200);
    }

    public function getNearbyLocations(Request $request)
    {
        // Eager load rentalOptions relationship
        $listings = Listing::with(['rentalOptions'])
            ->withCount([
                // this will add an `is_favorite` integer 0/1 column
                'favoritedBy as is_favorite' => function ($q) {
                    $q->where('user_id', Auth::id());
                },
            ])
            ->where('status', 'published')
            ->limit(6)
            ->get();

        // Map over listings and attach rental options if the type is 'rent'
        $listings = $listings->map(function ($listing) {
            if ($listing->type == 'rent' && $listing->rentalOptions->isNotEmpty()) {
                $listing->rental_options = $listing->rentalOptions; // Attach rental options for rent listings
            } else {
                $listing->rental_options = null; // Set to null if not a rent listing or no rental options
            }
            return $listing;
        });

        // Return the listings as JSON
        return response()->json([
            'message' => 'Listings retrieved successfully',
            'listings' => $listings
        ], 200);
    }


    public function saveAsDraft(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|in:sell,rent',
            'location' => 'nullable',
            'address' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'features' => 'nullable',
            'description' => 'nullable|string',
            'is_available' => 'nullable|boolean',
            'category_id' => 'nullable|exists:categories,id',
            'rental_options' => 'nullable|json',
        ]);

        $location = json_decode($request->location, true);
        $features = $request->features ? json_decode($request->features, true) : null;

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                try {
                    $path = $this->storeImage($image);
                    $imagePaths[] = $path;
                } catch (\Exception $e) {
                    Log::error("Image upload failed: " . $e->getMessage());
                }
            }
        }
        $listing = Listing::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'type' => $request->type,
            'location' => $location,
            'address' => $request->address,
            'price' => $request->price,
            'images' => $imagePaths,
            'features' => $features,
            'description' => $request->description,
            'status' => 'draft',
            'average_reviews' => 0,
            'is_available' => 1,
            'category_id' => $request->category_id,

        ]);
        //handle rental options
        $rentalOptions = json_decode($request->rental_options, true);

        if (is_array($rentalOptions) && count($rentalOptions)) {
            foreach ($rentalOptions as $option) {
                $listing->rentalOption()->create([
                    'duration' => $option['duration'],
                    'unit' => $option['unit'],
                    'price' => $option['price'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Listing created successfully',
            'listing' => $listing,
            'rental_options' => $rentalOptions,
        ], 201);
    }


    public function saveAsPublished(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:sell,rent',
            'location' => 'nullable|json',
            'address' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'features' => 'required|json',
            'description' => 'required|string',
            'is_available' => 'nullable|boolean',
            'category_id' => 'required|exists:categories,id',
            'rental_options' => 'nullable|json',

        ]);

        $location = json_decode($request->location, true);
        $features = $request->features ? json_decode($request->features, true) : null;

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                try {
                    $path = $this->storeImage($image);
                    $imagePaths[] = $path;
                } catch (\Exception $e) {
                    Log::error("Image upload failed: " . $e->getMessage());
                }
            }
        }

        $listing = Listing::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'type' => $request->type,
            'location' => $location,
            'address' => $request->address,
            'price' => $request->price,
            'images' => $imagePaths,
            'features' => $features,
            'description' => $request->description,
            'status' => 'published',
            'average_reviews' => 0,
            'is_available' => 1,
            'category_id' => $request->category_id,
        ]);
        $rentalOptions = json_decode($request->rental_options, true);

        if (is_array($rentalOptions) && count($rentalOptions)) {
            foreach ($rentalOptions as $option) {
                $listing->rentalOption()->create([
                    'duration' => $option['duration'],
                    'unit' => $option['unit'],
                    'price' => $option['price'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Listing created successfully',
            'listing' => $listing
        ], 201);
    }

    public function showSingleListing(Listing $listing)
    {
        return response()->json([
            'message' => 'Listing retrieved successfully',
            'listing' => $listing
        ], 200);
    }

    public function updateAsDraft(Request $request, string $id)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|in:sell,rent',
            'location' => 'nullable',
            'address' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'features' => 'nullable',
            'description' => 'nullable|string',
            'is_available' => 'nullable|boolean',
            'category_id' => 'nullable|exists:categories,id',
            'rental_options' => 'nullable|json',
        ]);
        $listing = Listing::findOrFail($id);
        $location = json_decode($request->location, true);
        $features = $request->features ? json_decode($request->features, true) : null;

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                try {
                    $path = $this->storeImage($image);
                    $imagePaths[] = $path;
                } catch (\Exception $e) {
                    Log::error("Image upload failed: " . $e->getMessage());
                }
            }
        }
        $listing->update([
            'name' => $request->name ?? $listing->name,
            'type' => $request->type ?? $listing->type,
            'location' => $location ?? $listing->location,
            'address' => $request->address ?? $listing->address,
            'price' => $request->price ?? $listing->price,
            'images' => $imagePaths,
            'features' => $features ?? $listing->features,
            'description' => $request->description ?? $listing->description,
            'status' => 'draft',
            'is_available' => $request->is_available ?? $listing->is_available,
            'category_id' => $request->category_id ?? $listing->category_id,
        ]);

        $rentalOptions = json_decode($request->rental_options, true);

        if (is_array($rentalOptions) && count($rentalOptions)) {
            foreach ($rentalOptions as $option) {
                $listing->rentalOption()->create([
                    'duration' => $option['duration'],
                    'unit' => $option['unit'],
                    'price' => $option['price'],
                ]);
            }
        }
        $listing->rentalOption()->delete();

        $rentalOptions = json_decode($request->rental_options, true);
        if (is_array($rentalOptions) && count($rentalOptions)) {
            foreach ($rentalOptions as $option) {
                $listing->rentalOption()->create([
                    'duration' => $option['duration'],
                    'unit' => $option['unit'],
                    'price' => $option['price'],
                ]);
            }
        }
        return response()->json([
            'message' => 'Listing updated successfully',
            'listing' => $listing,
            'rental_options' => $rentalOptions,
        ], 201);
    }

    public function updateAsPublish(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:sell,rent',
            'location' => 'nullable|json',
            'address' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'images' => 'required|array',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'features' => 'required|json',
            'description' => 'required|string',
            'is_available' => 'nullable|boolean',
            'category_id' => 'required|exists:categories,id',
            'rental_options' => 'nullable|json',

        ]);
        $listing = Listing::findOrFail($id);
        $location = json_decode($request->location, true);
        $features = $request->features ? json_decode($request->features, true) : null;

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                try {
                    $path = $this->storeImage($image);
                    $imagePaths[] = $path;
                } catch (\Exception $e) {
                    Log::error("Image upload failed: " . $e->getMessage());
                }
            }
        }
        $listing->update([
            'name' => $request->name ?? $listing->name,
            'type' => $request->type ?? $listing->type,
            'location' => $location ?? $listing->location,
            'address' => $request->address ?? $listing->address,
            'price' => $request->price ?? $listing->price,
            'images' => $imagePaths,
            'features' => $features ?? $listing->features,
            'description' => $request->description ?? $listing->description,
            'status' => 'draft',
            'is_available' => $request->is_available ?? $listing->is_available,
            'category_id' => $request->category_id ?? $listing->category_id,
        ]);

        $rentalOptions = json_decode($request->rental_options, true);

        if (is_array($rentalOptions) && count($rentalOptions)) {
            foreach ($rentalOptions as $option) {
                $listing->rentalOption()->create([
                    'duration' => $option['duration'],
                    'unit' => $option['unit'],
                    'price' => $option['price'],
                ]);
            }
        }
        $listing->rentalOption()->delete();

        $rentalOptions = json_decode($request->rental_options, true);
        if (is_array($rentalOptions) && count($rentalOptions)) {
            foreach ($rentalOptions as $option) {
                $listing->rentalOption()->create([
                    'duration' => $option['duration'],
                    'unit' => $option['unit'],
                    'price' => $option['price'],
                ]);
            }
        }
        return response()->json([
            'message' => 'Listing updated successfully',
            'listing' => $listing,
            'rental_options' => $rentalOptions,
        ], 201);
    }
    public function destroy(Listing $listing)
    {
        foreach ($listing->images as $image) {
            $this->deleteImage($image);
        }
        $listing->delete();
        return response()->json([
            'message' => 'Listing deleted successfully',
        ], 200);
    }

    public function search(Request $request)
    {
        // 1) Validate incoming parameters
        $params = $request->validate([
            'search'       => 'sometimes|string|max:255',
            'category_id'  => 'sometimes|integer|exists:categories,id',
        ]);

        // 2) Build the base query
        $query = Listing::withCount([
                // this will add an `is_favorite` integer 0/1 column
                'favoritedBy as is_favorite' => function ($q) {
                    $q->where('user_id', Auth::id());
                },
            ])
            ->where('status', 'published');

        // 3) Apply text search if provided
        if (! empty($params['search'])) {
            $term = $params['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
            });
        }

        // 4) Filter by category if provided
        if (! empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        // 5) Execute and return results
        $listings = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'listings' => $listings,
        ], 200);
    }
}
