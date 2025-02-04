<?php

namespace Marvel\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Marvel\Database\Repositories\UniformRepository;
use Marvel\Enums\Permission;
use Marvel\Exceptions\MarvelException;
use Marvel\Http\Requests\EmployeeCreateRequest;
use Marvel\Http\Requests\EmployeeUpdateRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Marvel\Http\Requests\UniformCreateRequest;
use Marvel\Http\Requests\UniformUpdateRequest;

class UniformController extends CoreController
{
    public $repository;

    public function __construct(UniformRepository $repository)
    {
        $this->repository = $repository;
    }
    public function index(Request $request)
    {
        $limit = $request->limit ? $request->limit : 15;
        return $this->fetchUniform($request)->paginate($limit)->withQueryString();

    }
    public function fetchUniform(Request $request)
    {
        // Start building the query
        $query = $this->repository->with(['user'])->with(['user']);
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }


        return $query;
    }



    public function store(UniformCreateRequest $request)
    {
        return $this->repository->storeUniform($request);

    }
    public function update(UniformUpdateRequest $request, $id)
    {
        try {
            $request->id = $id;
            return $this->updateUniform($request);
        } catch (MarvelException $th) {
            throw new MarvelException(COULD_NOT_UPDATE_THE_RESOURCE);
        }
    }
    public function updateUniform(Request $request)
    {
        $id = $request->id;
            return $this->repository->updateUniform($request, $id);
    }
    public function destroy(Request $request, $id)
    {
        try {
            $request->id = $id;
            return $this->deleteUniform($request);
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
                'message' => 'Uniforms deleted successfully.',
                'deleted_ids' => $ids,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Could not delete the resources.',
            ], 500);
        }
    }


    public function deleteEmployee(Request $request)
    {
        $id = $request->id;
        if ($request->user()->hasPermissionTo(Permission::SUPER_ADMIN) || ($request->user()->hasPermissionTo(Permission::STORE_OWNER) && ($request->user()->shops->contains($id)))) {
            try {
                $employee = $this->repository->findOrFail($id);
            } catch (\Exception $e) {
                throw new ModelNotFoundException(NOT_FOUND);
            }
            $employee->delete();
            return $employee;
        }
        throw new AuthorizationException(NOT_AUTHORIZED);
    }
    public function show($slug, Request $request)
    {
        $shop = $this->repository
            ->with(['owner','wallet','shop']);

        try {
            $shopData = match (true) {
                is_numeric($slug) => $shop->where('id', $slug)->firstOrFail(),
                is_string($slug)  => $shop->where('slug', $slug)->firstOrFail(),
            };
            // Convert the shop data to an array
            $shopArray = $shopData->toArray();

            // Replace the 'email' key with 'Employee_email'
            if (isset($shopArray['owner']['email'])) {
                $shopArray['Employee_email'] = $shopArray['owner']['email'];
                unset($shopArray['email']); // Optionally remove the original email key
            }

            return response()->json($shopArray);
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }
    public function approve(Request $request)
    {

        try {
            if (!$request->user()->hasPermissionTo(Permission::SUPER_ADMIN)) {
                throw new MarvelException(NOT_AUTHORIZED);
            }
            $id = $request->id;
            try {
                $shop = $this->repository->with('owner')->findOrFail($id);
            } catch (\Exception $e) {
                throw new ModelNotFoundException(NOT_FOUND);
            }
            $shop->is_active = true;
            if ($shop->owner) {
                $shop->owner->is_active = true;
                $shop->owner->save(); // Save changes to the owner
            }
            $shop->save();

//            if (Product::count() > 0) {
//                Product::where('shop_id', '=', $id)->update(['status' => 'publish']);
//            }
//
//            $balance = Balance::firstOrNew(['shop_id' => $id]);
//
//            if (!$request->isCustomCommission) {
//                $adminCommissionDefaultRate = $this->getCommissionRate($balance->total_earnings);
//                $balance->admin_commission_rate = $adminCommissionDefaultRate;
//            }else{
//                $balance->admin_commission_rate = $admin_commission_rate;
//            }
//            $balance->is_custom_commission = $request->isCustomCommission;
//            $balance->save();
            return $shop;
        } catch (MarvelException $th) {
            throw new MarvelException(SOMETHING_WENT_WRONG."221");
        }
    }

    public function disApprove(Request $request)
    {
        try {
            if (!$request->user()->hasPermissionTo(Permission::SUPER_ADMIN)) {
                throw new MarvelException(NOT_AUTHORIZED);
            }
            $id = $request->id;
            try {
                $shop = $this->repository->with('owner')->findOrFail($id);
            } catch (\Exception $e) {
                throw new ModelNotFoundException(NOT_FOUND);
            }

            $shop->is_active = false;
            if ($shop->owner) {
                $shop->owner->is_active = false;
                $shop->owner->save(); // Save changes to the owner
            }
            $shop->save();

            // Product::where('shop_id', '=', $id)->update(['status' => 'draft']);

            return $shop;
        } catch (MarvelException $th) {
            throw new MarvelException(SOMETHING_WENT_WRONG."245");
        }
    }
}

