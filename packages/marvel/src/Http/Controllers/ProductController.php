<?php

namespace Marvel\Http\Controllers;

use Exception;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Marvel\Database\Models\Type;
use Illuminate\Http\JsonResponse;
use Marvel\Database\Models\Product;
use Marvel\Database\Models\Wishlist;
use Marvel\Database\Models\Variation;
use Marvel\Exceptions\MarvelException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Marvel\Database\Models\Author;
use Marvel\Database\Models\Category;
use Marvel\Database\Models\Manufacturer;
use Marvel\Http\Requests\ProductCreateRequest;
use Marvel\Http\Requests\ProductUpdateRequest;
use Marvel\Database\Repositories\ProductRepository;
use Marvel\Database\Repositories\SettingsRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Marvel\Database\Models\Settings;
use Marvel\Database\Models\Tag;
use Marvel\Exceptions\MarvelNotFoundException;
use OpenAI;
use Marvel\Enums\Permission;
use Marvel\Http\Resources\GetSingleProductResource;
use Marvel\Http\Resources\ProductResource;

class ProductController extends CoreController
{
    public $repository;

    public $settings;

    public function __construct(ProductRepository $repository, SettingsRepository $settings)
    {
        $this->repository = $repository;
        $this->settings = $settings;
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Collection|Product[]
     */
    public function index(Request $request)
    {

        $limit = $request->limit ?   $request->limit : 15;
        $products = $this->fetchProducts($request)->paginate($limit)->withQueryString();
        return $products;
//        $data = ProductResource::collection($products)->response()->getData(true);
//        return formatAPIResourcePaginate($data);
    }



    /**
     * fetchProducts
     *
     * @param  mixed $request
     * @return object
     */
    public function fetchProducts(Request $request)
    {
        $unavailableProducts = [];
        $language = $request->language ? $request->language : DEFAULT_LANGUAGE;

        $products_query = $this->repository->where('language', $language);

        if (isset($request->date_range)) {
            $dateRange = explode('//', $request->date_range);
            $unavailableProducts = $this->repository->getUnavailableProducts($dateRange[0], $dateRange[1]);
        }
        if (in_array('variation_options.digital_files', explode(';', $request->with)) || in_array('digital_files', explode(';', $request->with))) {
            throw new AuthorizationException(NOT_AUTHORIZED);
        }
        $products_query = $products_query->whereNotIn('id', $unavailableProducts);

        if ($request->flash_sale_builder) {
            $products_query = $this->repository->processFlashSaleProducts($request, $products_query);
        }

        // Filter by Sizes
        if ($request->has('sizes') && $request->input('sizes')!='false') {
            // Get the sizes from the request
            $sizes = explode(",", $request->input('sizes'));

            // Apply the filter for sizes
            $products_query->whereHas('variation_options', function ($q) use ($sizes) {
                // Apply an OR condition for each color inside a single WHERE
                $q->where(function ($q) use ($sizes) {
                    foreach ($sizes as $size) {
                        $q->orWhere('title', 'like', '%' . $size . '%');
                    }
                });
            });
        }


        // Filter by Colors
        if ($request->has('colors') && $request->input('colors')!='false') {
            // Get colors from the request (split by commas)
            $colors = explode(",", $request->input('colors'));

            // Apply color filter in the main query
            $products_query->whereHas('variation_options', function ($q) use ($colors) {
                // Apply an OR condition for each color inside a single WHERE
                $q->where(function ($q) use ($colors) {
                    foreach ($colors as $color) {
                        $q->orWhere('title', 'like', '%' . $color . '%');
                    }
                });
            });
        }


        // Filter by Brands (Manufacturer)
        if ($request->has('brands') && $request->input('brands')!='false') {
            $brands = explode(",",$request->input('brands'));
                $products_query->whereHas('manufacturer', function ($q) use ($brands) {
                    $q->whereIn('name', $brands);
                });

        }

        $products_query->with(['categories', 'manufacturer','variation_options']);
        return $products_query;
    }



    /**
     * Store a newly created resource in storage by rest.
     *
     * @param ProductCreateRequest $request
     * @return mixed
     */
    public function store(ProductCreateRequest $request)
    {
        return $this->ProductStore($request);
    }



    /**
     * Store a newly created resource in storage by GQL.
     *
     * @param Request $request
     * @return mixed
     */
    public function ProductStore(Request $request)
    {
        try {
            // inform_purchased_customer
            $setting = $this->settings->first();
            if ($this->repository->hasPermission($request->user(), $request->shop_id)) {
                return $this->repository->storeProduct($request, $setting);
            } else {
                throw new AuthorizationException(NOT_AUTHORIZED);
            }
        } catch (MarvelException $e) {
            throw new MarvelException(SOMETHING_WENT_WRONG, $e->getMessage());
        }
    }



    /**
     * Display the specified resource.
     *
     * @param $slug
     * @return JsonResponse
     */
    public function show(Request $request, $slug)
    {
        $request->merge(['slug' => $slug]);
        try {
            $product = $this->fetchSingleProduct($request);
            return new GetSingleProductResource($product);
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }



    /**
     * Display the specified resource.
     *
     * @param $slug
     * @return JsonResponse
     */
    public function fetchSingleProduct(Request $request)
    {
        try {
            $slug = $request->slug;
            $language = $request->language ?? DEFAULT_LANGUAGE;
            $user = $request->user();
            $limit = isset($request->limit) ? $request->limit : 10;
            $product = $this->repository->where('language', $language)->where('slug', $slug)->orWhere('id', $slug)->firstOrFail();
            if (
                in_array('variation_options.digital_file', explode(';', $request->with)) || in_array('digital_file', explode(';', $request->with))
            ) {
                if (!$this->repository->hasPermission($user, $product->shop_id)) {
                    throw new AuthorizationException(NOT_AUTHORIZED);
                }
            }
            $related_products = $this->repository->fetchRelated($slug, $limit, $language);
            $product->setRelation('related_products', $related_products);

            return $product;
        } catch (Exception $e) {
            throw new MarvelNotFoundException();
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param ProductUpdateRequest $request
     * @param int $id
     * @return array
     */
    public function update(ProductUpdateRequest $request, $id)
    {
        try {
            $request->id = $id;
            return $this->updateProduct($request);
        } catch (MarvelException $e) {
            throw new MarvelException(COULD_NOT_UPDATE_THE_RESOURCE);
        }
    }


    /**
     * updateProduct
     *
     * @param  Request $request
     * @return array
     */
    public function updateProduct(Request $request)
    {
        $setting = $this->settings->first();
        if ($this->repository->hasPermission($request->user(), $request->shop_id)) {
            $id = $request->id;
            return $this->repository->updateProduct($request, $id, $setting);
        } else {
            throw new AuthorizationException(NOT_AUTHORIZED);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $request->id = $id;
        return $this->destroyProduct($request);
    }


    /**
     * destroyProduct
     *
     * @param  Request $request
     * @return void
     */
    public function destroyProduct(Request $request)
    {
        try {
            $product = $this->repository->findOrFail($request->id);
            if ($this->repository->hasPermission($request->user(), $product->shop_id)) {
                $product->delete();
                return $product;
            }
            throw new AuthorizationException(NOT_AUTHORIZED);
        } catch (MarvelException $e) {
            throw new MarvelException($e->getMessage());
        }
    }

    /**
         * relatedProducts
         *
         * @param  Request $request
         * @return void
         */
        public function destroyMany(Request $request)
        {
            $ids = $request->input('ids');

            if (!is_array($ids)) {
                return response()->json(['error' => 'Invalid input. Expected array of IDs.'], 422);
            }

            $deleted = [];

            foreach ($ids as $id) {
                try {
                    $req = clone $request;
                    $req->id = $id;
                    $this->destroyProduct($req);
                    $deleted[] = $id;
                } catch (\Throwable $e) {
                    // Log error or continue
                }
            }

            return response()->json([
                'message' => 'Requested products deleted.',
                'deleted_ids' => $deleted
            ]);
        }


    /**
     * relatedProducts
     *
     * @param  Request $request
     * @return void
     */
    public function relatedProducts(Request $request)
    {
        $limit = isset($request->limit) ? $request->limit : 10;
        $slug =  $request->slug;
        $language = $request->language ?? DEFAULT_LANGUAGE;
        return $this->repository->fetchRelated($slug, $limit, $language);
    }



    /**
     * exportProducts
     *
     * @param  Request $request
     * @param  mixed $shop_id
     * @return void
     */
    public function exportProducts(Request $request):mixed
    {

        $filename = 'products.csv';
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Expires'             => '0',
            'Pragma'              => 'public'
        ];

        $list = $this->repository->with([
            'categories',
            'tags',
        ])->get()->toArray();

        if (!count($list)) {
            return response()->stream(function () {
                //
            }, 200, $headers);
        }
        # add headers for each column in the CSV download
        array_unshift($list, array_keys($list[0]));

        $callback = function () use ($list) {
            $FH = fopen('php://output', 'w');
            foreach ($list as $key => $row) {
                if ($key === 0) {
                    $exclude = ['id', 'slug', 'deleted_at', 'created_at', 'updated_at', 'shipping_class_id', 'ratings', 'total_reviews', 'my_review', 'in_wishlist', 'rating_count', 'translated_languages'];
                    $row = array_diff($row, $exclude);
                }
                unset($row['id']);
                unset($row['deleted_at']);
                unset($row['shipping_class_id']);
                unset($row['updated_at']);
                unset($row['created_at']);
                unset($row['slug']);
                unset($row['ratings']);
                unset($row['total_reviews']);
                unset($row['my_review']);
                unset($row['in_wishlist']);
                unset($row['rating_count']);
                unset($row['translated_languages']);
                if (isset($row['image'])) {
                    $row['image'] = json_encode($row['image']);
                }
                if (isset($row['gallery'])) {
                    $row['gallery'] = json_encode($row['gallery']);
                }
                if (isset($row['blocked_dates'])) {
                    $row['blocked_dates'] = json_encode($row['blocked_dates']);
                }
                if (isset($row['video'])) {
                    $row['video'] = json_encode($row['video']);
                }
                if (isset($row['categories'])) {
                    $categories = collect($row['categories'])->pluck('id')->toArray();
                    $row['categories'] = json_encode($categories);
                }
                if (isset($row['tags'])) {
                    $tagIds = collect($row['tags'])->pluck('pivot.tag_id')->toArray();
                    $row['tags'] = json_encode($tagIds);
                }
                fputcsv($FH, $row);
            }
            fclose($FH);
        };

        return response()->stream($callback, 200, $headers);
    }



    /**
     * exportVariableOptions
     *
     * @param  Request $request
     * @param  mixed $shop_id
     * @return void
     */
    public function exportVariableOptions(Request $request, $shop_id)
    {
        $filename = 'variable-options-' . Str::random(5) . '.csv';
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Expires'             => '0',
            'Pragma'              => 'public'
        ];

        $products = $this->repository->where('shop_id', $shop_id)->get();

        $list = Variation::WhereIn('product_id', $products->pluck('id'))->get()->toArray();

        if (!count($list)) {
            return response()->stream(function () {
                //
            }, 200, $headers);
        }
        # add headers for each column in the CSV download
        array_unshift($list, array_keys($list[0]));

        $callback = function () use ($list) {
            $FH = fopen('php://output', 'w');
            foreach ($list as $key => $row) {
                if ($key === 0) {
                    $exclude = ['id', 'created_at', 'updated_at', 'translated_languages'];
                    $row = array_diff($row, $exclude);
                }
                unset($row['id']);
                unset($row['updated_at']);
                unset($row['created_at']);
                unset($row['translated_languages']);
                if (isset($row['options'])) {
                    $row['options'] = json_encode($row['options']);
                }
                if (isset($row['blocked_dates'])) {
                    $row['blocked_dates'] = json_encode($row['blocked_dates']);
                }
                fputcsv($FH, $row);
            }
            fclose($FH);
        };

        return response()->stream($callback, 200, $headers);
    }




    /**
     * importProducts
     *
     * @param  Request $request
     * @return bool
     */
    public function importProducts(Request $request)
    {
        $requestFile = $request->file();
        $duplicateProducts = [];
        $importedProducts = [];

        if (count($requestFile)) {
            if (isset($requestFile['csv'])) {
                $uploadedCsv = $requestFile['csv'];
            } else {
                $uploadedCsv = current($requestFile);
            }
        }

        $file = $uploadedCsv->storePubliclyAs('csv-files', 'products.' . $uploadedCsv->getClientOriginalExtension(), 'public');

        $products = $this->repository->csvToArray(storage_path() . '/app/public/' . $file);

        foreach ($products as $key => $product) {
            if (!isset($product['type_id'])) {
                continue;
            }
            unset($product['id']);
            $product['image'] = json_decode($product['image'], true);
            $product['gallery'] = json_decode($product['gallery'], true);
            $product['video'] = json_decode($product['video'], true);
            $categoriesId = json_decode($product['categories'], true);
            $tagsId = json_decode($product['tags'], true);
            try {
                $type = Type::findOrFail($product['type_id']);
                $product['author_id'] = Author::find($product['author_id'])?->id;
                $product['manufacturer_id'] = Manufacturer::find($product['manufacturer_id'])?->id;
                $dataArray = $this->repository->getProductDataArray();
                $productArray = array_intersect_key($product, array_flip($dataArray));

                if (isset($type->id)) {
                    // Check if product already exists
                    $existingProduct = Product::where('name', $productArray['name'])
                        ->where('type_id', $productArray['type_id'])
                        ->where('author_id', $productArray['author_id'])
                        ->where('manufacturer_id', $productArray['manufacturer_id'])
                        ->first();

                    if ($existingProduct) {
                        $duplicateProducts[] = $productArray['name'];
                        continue;
                    }

                    $newProduct = Product::create($productArray);
                    $importedProducts[] = $productArray['name'];

                    $categories = Category::whereIn('id', $categoriesId)->get();
                    $tags = Tag::whereIn('id', $tagsId)->get();

                    if (!empty($categories)) {
                        $newProduct->categories()->attach($categories);
                    }
                    if (!empty($tags)) {
                        $newProduct->tags()->attach($tags);
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

        $response = [
            'success' => true,
            'message' => 'Products import completed',
            'imported_count' => count($importedProducts),
            'duplicate_count' => count($duplicateProducts),
            'imported_products' => $importedProducts,
            'duplicate_products' => $duplicateProducts
        ];

        return response()->json($response);
    }

    /**
     * importVariationOptions
     *
     * @param  Request $request
     * @return bool
     */
    public function importVariationOptions(Request $request)
    {
        $requestFile = $request->file();
        $user = $request->user();
        $shop_id = $request->shop_id;

        if (count($requestFile)) {
            if (isset($requestFile['csv'])) {
                $uploadedCsv = $requestFile['csv'];
            } else {
                $uploadedCsv = current($requestFile);
            }
        } else {
            throw new MarvelException(CSV_NOT_FOUND);
        }

        if (!$this->repository->hasPermission($user, $shop_id)) {
            throw new AuthorizationException(NOT_AUTHORIZED);
        }
        if (isset($user->id)) {
            $file = $uploadedCsv->storePubliclyAs('csv-files', 'variation-options-' . Str::random(5) . '.' . $uploadedCsv->getClientOriginalExtension(), 'public');

            $attributes = $this->repository->csvToArray(storage_path() . '/app/public/' . $file);

            foreach ($attributes as $key => $attribute) {
                if (!isset($attribute['title']) || !isset($attribute['price'])) {
                    throw new MarvelException("MARVEL_ERROR.WRONG_CSV");
                }
                unset($attribute['id']);
                $attribute['options'] = json_decode($attribute['options'], true);
                try {
                    $product = Type::findOrFail($attribute['product_id']);
                    if (isset($product->id)) {
                        Variation::firstOrCreate($attribute);
                    }
                } catch (Exception $e) {
                    //
                }
            }
            return true;
        }
    }



    /**
     * fetchDigitalFilesForProduct
     *
     * @param  Request $request
     * @return void
     */
    public function fetchDigitalFilesForProduct(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $product = $this->repository->with(['digital_file'])->findOrFail($request->parent_id);
            if ($this->repository->hasPermission($user, $product->shop_id)) {
                return $product->digital_file;
            }
        }
    }



    /**
     * fetchDigitalFilesForVariation
     *
     * @param  Request $request
     * @return void
     */
    public function fetchDigitalFilesForVariation(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $variation_option = Variation::with(['digital_file', 'product'])->findOrFail($request->parent_id);
            if ($this->repository->hasPermission($user, $variation_option->product->shop_id)) {
                return $variation_option->digital_file;
            }
        }
    }



    /**
     * bestSellingProducts
     *
     * @param  Request $request
     * @return void
     */

    public function bestSellingProducts(Request $request)
    {
        return $this->repository->getBestSellingProducts($request);
    }



    /**
     * popularProducts
     *
     * @param  Request $request
     * @return object
     */
    public function popularProducts(Request $request)
    {
        $limit = $request->limit ? $request->limit : 10;
        $language = $request->language ?? DEFAULT_LANGUAGE;
        $range = !empty($request->range) && $request->range !== 'undefined'  ? $request->range : '';
        $type_id = $request->type_id ? $request->type_id : '';
        if (isset($request->type_slug) && empty($type_id)) {
            try {
                $type = Type::where('slug', $request->type_slug)->where('language', $language)->firstOrFail();
                $type_id = $type->id;
            } catch (MarvelException $e) {
                throw new MarvelException(NOT_FOUND);
            }
        }
        $products_query = $this->repository->withCount('orders')->with(['type', 'shop'])->orderBy('orders_count', 'desc')->where('language', $language);
        if (isset($request->shop_id)) {
            $products_query = $products_query->where('shop_id', "=", $request->shop_id);
        }
        if ($range) {
            $products_query = $products_query->whereDate('created_at', '>', Carbon::now()->subDays($range));
        }
        if ($type_id) {
            $products_query = $products_query->where('type_id', '=', $type_id);
        }
        return $products_query->take($limit)->get();
    }



    /**
     * calculateRentalPrice
     *
     * @param  Request $request
     * @return void
     */
    public function calculateRentalPrice(Request $request)
    {
        $isAvailable = true;
        $product_id = $request->product_id;
        try {
            $product = Product::findOrFail($product_id);
        } catch (MarvelException $th) {
            throw new MarvelException(NOT_FOUND);
        }
        if (!$product->is_rental) {
            throw new MarvelException(NOT_A_RENTAL_PRODUCT);
        }
        $variation_id = $request->variation_id;
        $quantity = $request->quantity;
        $persons = $request->persons;
        $dropoff_location_id = $request->dropoff_location_id;
        $pickup_location_id = $request->pickup_location_id;
        $deposits = $request->deposits;
        $features = $request->features;
        $from = $request->from;
        $to = $request->to;
        if ($variation_id) {
            $blockedDates = $this->repository->fetchBlockedDatesForAVariationInRange($from, $to, $variation_id);
            $isAvailable = $this->repository->isVariationAvailableAt($from, $to, $variation_id, $blockedDates, $quantity);
            if (!$isAvailable) {
                throw new marvelException(NOT_AVAILABLE_FOR_BOOKING);
            }
        } else {
            $blockedDates = $this->repository->fetchBlockedDatesForAProductInRange($from, $to, $product_id);
            $isAvailable = $this->repository->isProductAvailableAt($from, $to, $product_id, $blockedDates, $quantity);
            if (!$isAvailable) {
                throw new marvelException(NOT_AVAILABLE_FOR_BOOKING);
            }
        }

        $from = Carbon::parse($from);
        $to = Carbon::parse($to);

        $bookedDay = $from->diffInDays($to);

        return $this->repository->calculatePrice($bookedDay, $product_id, $variation_id, $quantity, $persons, $dropoff_location_id, $pickup_location_id, $deposits, $features);
    }



    /**
     * myWishlists
     *
     * @param  Request $request
     * @return void
     */
    public function myWishlists(Request $request)
    {
        $limit = $request->limit ? $request->limit : 10;
        return $this->fetchWishlists($request)->paginate($limit);
    }



    /**
     * fetchWishlists
     *
     * @param  Request $request
     * @return object
     */
    public function fetchWishlists(Request $request)
    {
        $user = $request->user();
        $wishlist = Wishlist::where('user_id', $user->id)->pluck('product_id');
        return $this->repository->whereIn('id', $wishlist);
    }


    /**
     * draftedProducts
     *
     * @param  Request $request
     * @return void
     */
    public function draftedProducts(Request $request)
    {
        $limit = $request->limit ? $request->limit : 15;

        return $this->fetchDraftedProducts($request)->paginate($limit);
    }

    /**
     * fetchDraftedProducts
     *
     * @param  Request $request
     * @return mixed
     */
    public function fetchDraftedProducts(Request $request)
    {
        $user = $request->user() ?? null;
        $language = $request->language ? $request->language : DEFAULT_LANGUAGE;

        $products_query = $this->repository->with(['type', 'shop'])->where('language', $language);

        switch ($user) {
            case $user->hasPermissionTo(Permission::SUPER_ADMIN):
                return $products_query->whereIn('shop_id', $user->shops->pluck('id'));
                break;

            case $user->hasPermissionTo(Permission::STORE_OWNER):
                if (isset($request->shop_id)) {
                    return $products_query->where('shop_id', '=', $request->shop_id);
                } else {
                    return $products_query->whereIn('shop_id', $user->shops->pluck('id'));
                }
                break;

            case $user->hasPermissionTo(Permission::STAFF):
                if (isset($request->shop_id)) {
                    return $products_query->where('shop_id', '=', $request->shop_id);
                } else {
                    return $products_query->where('shop_id', $user->managed_shop->id);
                }
                break;
        }

        return $products_query;
    }

    /**
     * productStock
     *
     * @param  Request $request
     * @return void
     */
    public function productStock(Request $request)
    {
        $limit = $request->limit ? $request->limit : 15;

        return $this->fetchProductStock($request)->paginate($limit);
    }

    /**
     * productStock
     *
     * @param  Request $request
     * @return mixed
     */
    public function fetchProductStock(Request $request)
    {
        $user = $request->user();
        $language = $request->language ? $request->language : DEFAULT_LANGUAGE;

        $products_query = $this->repository->with(['type', 'shop'])->where('language', $language)->where('quantity', '<', 10);

        switch ($user) {
            case $user->hasPermissionTo(Permission::SUPER_ADMIN):
                if (isset($request->shop_id)) {
                    return $products_query->where('shop_id', '=', $request->shop_id);
                } else {
                    return $products_query;
                }
                break;

            case $user->hasPermissionTo(Permission::STORE_OWNER):
                if (isset($request->shop_id)) {
                    // shop specific
                    return $products_query->where('shop_id', '=', $request->shop_id);
                } else {
                    // overall shops
                    return $products_query->whereIn('shop_id', $user->shops->pluck('id'));
                }
                break;

            case $user->hasPermissionTo(Permission::STAFF):
                if (isset($request->shop_id)) {
                    return $products_query->where('shop_id', '=', $request->shop_id);
                } else {
                    return $products_query->where('shop_id', '=', null);
                }
                break;

            default:
                return $products_query->where('shop_id', '=', null);

                break;
        }

        return $products_query;
    }
    public function approve(Request $request)
    {

        try {

            $id = $request->id;
            try {
                $product = $this->repository->findOrFail($id);
            } catch (Exception $e) {
                throw new ModelNotFoundException(NOT_FOUND);
            }
            $product->status = 'publish';

            $product->save();


            return $product;
        } catch (MarvelException $th) {
            throw new MarvelException(SOMETHING_WENT_WRONG."221");
        }
    }

    public function disApprove(Request $request)
    {
        try {

            $id = $request->id;
            try {
                $product = $this->repository->findOrFail($id);
            } catch (Exception $e) {
                throw new ModelNotFoundException(NOT_FOUND);
            }
            $product->status = 'unpublish';

            $product->save();


            return $product;
        } catch (MarvelException $th) {
            throw new MarvelException(SOMETHING_WENT_WRONG."221");
        }
    }

}
