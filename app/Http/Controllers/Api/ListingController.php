<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use function Psy\debug;

class ListingController extends Controller
{
    use ApiResponseTrait;
    public function userListings(Request $request)
    {

        $listings = Listing::with(['rentalOptions'])
            ->where('user_id', Auth::id())
            ->get();


        if ($request->has('status') && in_array($request->status, ['draft', 'published'])) {
            $listings = $listings->where('status', $request->status)->values();
        }


        $listings = $listings->map(function ($listing) {
            if ($listing->type == 'rent' && $listing->rentalOptions->isNotEmpty()) {
                $listing->rental_options = $listing->rentalOptions;
            } else {
                $listing->rental_options = null;
            }
            return $listing;
        });


        return response()->json([
            'message' => 'Listings retrieved successfully',
            'listings' => $listings
        ], 200);
    }


    public function getNearbyLocations(Request $request)
    {
        $listings = Listing::with(['rentalOptions', 'transactions'])
            ->withCount([
                'favoritedBy as is_favorite' => function ($q) {
                    $q->where('user_id', Auth::id());
                },
            ])
            ->where('status', 'published')
            ->where(function ($query) {
                $query->where('type', '!=', 'sell')
                    ->orWhereDoesntHave('transactions');
            })
            ->limit(6)
            ->get();

        $listings = $listings->map(function ($listing) {
            if ($listing->type === 'rent' && $listing->rentalOptions->isNotEmpty()) {
                $listing->rental_options = $listing->rentalOptions;
            } else {
                $listing->rental_options = null;
            }
            return $listing;
        });

        return response()->json($listings);
    }




    public function saveAsDraft(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|in:sell,rent',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
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

        $features = $request->features ? json_decode($request->features, true) : null;
        $rentalOptions = json_decode($request->rental_options, true);

        // Validate rental options when type is rent
        if ($request->type === 'rent' && is_array($rentalOptions) && count($rentalOptions) > 0) {
            // Group rental options by unit
            $optionsByUnit = [];
            foreach ($rentalOptions as $option) {
                $unit = $option['unit'];
                if (!isset($optionsByUnit[$unit])) {
                    $optionsByUnit[$unit] = [];
                }
                $optionsByUnit[$unit][] = $option;
            }

            // Check if each unit has at least one option with duration=1
            foreach ($optionsByUnit as $unit => $options) {
                $hasDurationOne = false;
                foreach ($options as $option) {
                    if ($option['duration'] == 1) {
                        $hasDurationOne = true;
                        break;
                    }
                }

                if (!$hasDurationOne) {
                    return response()->json([
                        'success' => false,
                        'message' => "Each unit type must have at least one rental option with duration of 1. Missing for {$unit}.",
                    ], 400);
                }
            }
        }

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
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
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

        if (is_array($rentalOptions) && count($rentalOptions)) {
            foreach ($rentalOptions as $option) {
                $listing->rentalOptions()->create([
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
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
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

        $features = $request->features ? json_decode($request->features, true) : null;
        $rentalOptions = json_decode($request->rental_options, true);

        // Validate rental options when type is rent
        if ($request->type === 'rent' && is_array($rentalOptions) && count($rentalOptions) > 0) {
            // Group rental options by unit
            $optionsByUnit = [];
            foreach ($rentalOptions as $option) {
                $unit = $option['unit'];
                if (!isset($optionsByUnit[$unit])) {
                    $optionsByUnit[$unit] = [];
                }
                $optionsByUnit[$unit][] = $option;
            }

            // Check if each unit has at least one option with duration=1
            foreach ($optionsByUnit as $unit => $options) {
                $hasDurationOne = false;
                foreach ($options as $option) {
                    if ($option['duration'] == 1) {
                        $hasDurationOne = true;
                        break;
                    }
                }

                if (!$hasDurationOne) {
                    return response()->json([
                        'success' => false,
                        'message' => "Each unit type must have at least one rental option with duration of 1. Missing for {$unit}.",
                    ], 400);
                }
            }
        }

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
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
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
                $listing->rentalOptions()->create([
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
        $listing->load(['rentalOptions', 'category', 'user', 'discounts']);

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
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
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

        if ($request->filled('type') && $request->type !== $listing->type) {
            if ($listing->transactions()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change type because this listing has transactions.',
                ], 422);
            }
        }
        $features = $request->features ? json_decode($request->features, true) : null;

        $imagePaths = $listing->images ?? [];
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
        $finalType = $request->type ?? $listing->type;
        $finalPrice = $finalType === 'rent' ? null : ($request->price ?? $listing->price);

        if ($finalType === 'sell') {
            $listing->rentalOptions()->delete();
        }
        $listing->update([
            'name' => $request->name ?? $listing->name,
            'type' => $finalType,
            'latitude' => $request->latitude ?? $listing->latitude,
            'longitude' => $request->longitude ?? $listing->longitude,
            'address' => $request->address ?? $listing->address,
            'price' => $finalPrice,
            'images' => $imagePaths,
            'features' => $features ?? $listing->features,
            'description' => $request->description ?? $listing->description,
            'status' => 'draft',
            'is_available' => $request->is_available ?? $listing->is_available,
            'category_id' => $request->category_id ?? $listing->category_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Listing updated successfully',
            'listing' => $request->all(),
        ], 200);
    }

    public function updateAsPublish(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:sell,rent',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'features' => 'required|json',
            'description' => 'required|string',
            'is_available' => 'nullable|boolean',
            'category_id' => 'required|exists:categories,id',
            'rental_options' => 'nullable|json',

        ]);
        $listing = Listing::findOrFail($id);

        if ($request->filled('type') && $request->type !== $listing->type) {
            if ($listing->transactions()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change type because this listing has transactions.',
                ], 422);
            }
        }
        $features = $request->features ? json_decode($request->features, true) : null;

        $imagePaths = $listing->images ?? [];
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
        $finalType = $request->type ?? $listing->type;
        $finalPrice = $finalType === 'rent' ? null : ($request->price ?? $listing->price);

        if ($finalType === 'sell' && $listing->transactions()->exists()) {
            $listing->rentalOptions()->delete();
        }
        $listing->update([
            'name' => $request->name ?? $listing->name,
            'type' => $finalType,
            'latitude' => $request->latitude ?? $listing->latitude,
            'longitude' => $request->longitude ?? $listing->longitude,
            'address' => $request->address ?? $listing->address,
            'price' => $finalPrice,
            'images' => $imagePaths,
            'features' => $features ?? $listing->features,
            'description' => $request->description ?? $listing->description,
            'status' => 'published',
            'is_available' => $request->is_available ?? $listing->is_available,
            'category_id' => $request->category_id ?? $listing->category_id,
        ]);

        return response()->json([
            'message' => 'Listing updated successfully',
            'listing' => $listing,

        ], 201);
    }

    public function destroy(Listing $listing)
    {
        if ($listing->transactions()->exists()) {
            return response()->json([
                'message' => 'Cannot delete listing. It has related transactions.',
            ], 400);
        }

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
        $params = $request->validate([
            'search'      => 'sometimes|string|max:255',
            'category_id' => 'sometimes|integer|exists:categories,id',
        ]);

        $query = Listing::withCount([
            'favoritedBy as is_favorite' => function ($q) {
                $q->where('user_id', Auth::id());
            },
        ])
            ->where('status', 'published')
            ->where(function ($q) {
                $q->where('type', '!=', 'sell')
                    ->orWhereDoesntHave('transactions');
            });

        if (!empty($params['search'])) {
            $term = $params['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        $listings = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'listings' => $listings,
        ], 200);
    }

    public function deleteListingImage(Request $request, Listing $listing)
    {
        if (count($listing->images) <= 1 && $listing->status == 'published') {
            return response()->json([
                'message' => 'At least one image is required for published listings.',
            ], 400);
        }
        foreach ($listing->images as $image) {
            if ($image == $request->url) {
                // dd($image, $request->url);
                $this->deleteImage($image);

                $listing->update([
                    'images' => array_values(array_filter($listing->images, fn($img) => $img !== $request->url))
                ]);
            }
        }

        return response()->json([
            'message' => 'Image deleted successfully',
        ], 200);
    }


    public function filter(Request $request)
    {
        $categoryIds = $request->input('category_ids');

        if (is_string($categoryIds)) {
            $categoryIds = explode(',', $categoryIds);
        } elseif (!is_array($categoryIds)) {
            $categoryIds = [];
        }

        $request->merge(['category_ids' => $categoryIds]);

        $params = $request->validate([
            'type'           => 'sometimes|in:sell,rent',
            'min_price'      => 'sometimes|numeric|min:0',
            'max_price'      => 'sometimes|numeric|min:0',
            'search'         => 'sometimes|string',
            'category_ids'   => 'sometimes|array',
            'category_ids.*' => 'exists:categories,id',
            'unit'           => 'sometimes|in:Day,Week,Month,Year', // Optional unit filter for rental options
            'duration'       => 'sometimes|integer|min:1',          // Optional duration filter
        ]);

        $query = Listing::with(['rentalOptions'])
            ->where('status', 'published');

        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        // Price filtering logic that handles both types
        if (isset($params['min_price']) || isset($params['max_price'])) {
            $query->where(function ($q) use ($params) {
                // For "sell" type listings, filter by the direct price
                $q->where(function ($sellQuery) use ($params) {
                    $sellQuery->where('type', 'sell');

                    if (isset($params['min_price'])) {
                        $sellQuery->where('price', '>=', $params['min_price']);
                    }

                    if (isset($params['max_price'])) {
                        $sellQuery->where('price', '<=', $params['max_price']);
                    }
                });

                // For "rent" type listings, filter by rental options prices
                $q->orWhere(function ($rentQuery) use ($params) {
                    $rentQuery->where('type', 'rent');

                    // Filter by rental option prices
                    $rentQuery->whereHas('rentalOptions', function ($optionQuery) use ($params) {
                        // Apply unit and duration filters if provided
                        if (!empty($params['unit'])) {
                            $optionQuery->where('unit', $params['unit']);
                        }

                        if (!empty($params['duration'])) {
                            $optionQuery->where('duration', $params['duration']);
                        } else {
                            // Default to duration=1 if no specific duration requested
                            $optionQuery->where('duration', 1);
                        }

                        // Apply price range filters to rental options
                        if (isset($params['min_price'])) {
                            $optionQuery->where('price', '>=', $params['min_price']);
                        }

                        if (isset($params['max_price'])) {
                            $optionQuery->where('price', '<=', $params['max_price']);
                        }
                    });
                });
            });
        }

        if (!empty($params['category_ids'])) {
            $query->whereIn('category_id', $params['category_ids']);
        }

        if (!empty($params['search'])) {
            $query->where(function ($q) use ($params) {
                $q->where('name', 'like', '%' . $params['search'] . '%')
                    ->orWhere('description', 'like', '%' . $params['search'] . '%');
            });
        }

        $listings = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message'  => 'Filtered listings retrieved',
            'listings' => $listings,
        ], 200);
    }
}
