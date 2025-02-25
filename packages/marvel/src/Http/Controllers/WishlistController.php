<?php


namespace Marvel\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Marvel\Database\Models\Product;
use Illuminate\Support\Facades\Auth;
use Marvel\Exceptions\MarvelException;
use Marvel\Database\Models\AbusiveReport;
use Illuminate\Database\Eloquent\Collection;
use Marvel\Http\Requests\WishlistCreateRequest;
use Marvel\Database\Repositories\WishlistRepository;
use Marvel\Http\Requests\AbusiveReportCreateRequest;
use Prettus\Validator\Exceptions\ValidatorException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WishlistController extends CoreController
{
    public $repository;

    public function __construct(WishlistRepository $repository)
    {
        $this->repository = $repository;
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Collection|AbusiveReport[]
     */
    public function index(Request $request)
    {
        $limit = $request->limit ?? 15; // Default to 15 if limit is not set

// Get wishlist entries for the given uniform_id
        $wishlist = $this->repository->where('uniform_id', $request->uniform_id)
            ->get(['product_id', 'variation_option_id']); // Fetch both product_id & variation_option_id

// Extract product IDs and variation_option_ids
        $productIds = $wishlist->pluck('product_id')->toArray();
        $variationOptionIds = $wishlist->pluck('variation_option_id')->toArray();

// Fetch products and filter by variation_option_id
        $products = Product::whereIn('id', $productIds)
            ->with(['variation_options' => function ($query) use ($variationOptionIds) {
                $query->whereIn('id', $variationOptionIds);
            }])
            ->paginate($limit);

        return $products;

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AbusiveReportCreateRequest $request
     * @return mixed
     * @throws ValidatorException
     */
    public function store(WishlistCreateRequest $request)
    {
        try {
            return $this->repository->storeWishlist($request);
        } catch (MarvelException $th) {
            throw new MarvelException(COULD_NOT_CREATE_THE_RESOURCE);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AbusiveReportCreateRequest $request
     * @return mixed
     * @throws ValidatorException
     */
    public function toggle(WishlistCreateRequest $request)
    {
        try {
            return $this->repository->toggleWishlist($request);
        } catch (MarvelException $th) {
            throw new MarvelException(SOMETHING_WENT_WRONG."wishlist");
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $request->id = $id;
            return $this->delete($request);
        } catch (MarvelException $th) {
            throw new MarvelException(COULD_NOT_DELETE_THE_RESOURCE);
        }
    }
    public function destroyAll(Request $request)
    {
        try {
            // Decode the raw JSON body to get the array of IDs
            $ids = $request->all();

            // Validate the input to ensure it's an array
            if (!is_array($ids) || empty($ids)) {
                return response()->json([
                    'error' => 'Invalid input. An array of IDs is required.',
                ], 400);
            }

            // Delete employees with the provided IDs
            $this->repository->whereIn('id', $ids)->delete();

            return response()->json([
                'message' => 'WhishList deleted successfully.',
                'deleted_ids' => $ids,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e,
            ], 500);
        }
    }
    public function delete(Request $request)
    {
        try {
            if (!$request->user()) {
                throw new AuthorizationException(NOT_AUTHORIZED);
            }
            $product = Product::where('id', $request->id)->first();
            $wishlist = $this->repository->where('product_id', $product->id)->where('user_id', auth()->user()->id)->first();
            if (!empty($wishlist)) {
                return $wishlist->delete();
            }
            throw new HttpException(404, NOT_FOUND);
        } catch (MarvelException $th) {
            throw new MarvelException(COULD_NOT_DELETE_THE_RESOURCE);
        }
    }

    /**
     * Check in wishlist product for authenticated user
     *
     * @param int $product_id
     * @return JsonResponse
     */
    public function in_wishlist(Request $request, $product_id)
    {
        $request->product_id = $product_id;
        return $this->inWishlist($request);
    }

    public function inWishlist(Request $request)
    {
        if (auth()->user() && !empty($this->repository->where('product_id', $request->product_id)->where('user_id', auth()->user()->id)->first())) {
            return true;
        }
        return false;
    }
}
