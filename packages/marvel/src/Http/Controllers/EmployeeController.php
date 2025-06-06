<?php

namespace Marvel\Http\Controllers;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Marvel\Enums\Permission;
use Marvel\Exceptions\MarvelException;
use Marvel\Http\Requests\EmployeeCreateRequest;
use Marvel\Http\Requests\EmployeeUpdateRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Marvel\Database\Repositories\EmployeeRepository;

class EmployeeController extends CoreController
{
    public $repository;

    public function __construct(EmployeeRepository $repository)
    {
        $this->repository = $repository;
    }
    public function index(Request $request)
    {
        $limit = $request->limit ? $request->limit : 15;
        return $this->fetchEmployee($request)->paginate($limit)->withQueryString();

    }
    public function fetchEmployee(Request $request)
    {
        // Start building the query
        $query = $this->repository->with(['shop','wallet','orders'])->with(['owner.profile']);

        // Apply filters from the request
        if ($request->has('Employee_status')) {
            $query->whereHas('owner', function ($q) use ($request) {
                $q->where('is_active', $request->Employee_status == 1 ? 1 : 0);
            });
        }

        if ($request->has('created_by')) {
            $query->whereHas('owner', function ($q) use ($request) {
                $q->where('created_by', $request->created_by);
            });
        }

        if ($request->has('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        if ($request->has('company_status')) {
            $query->whereHas('shop.owner', function ($q) use ($request) {
                $q->where('is_active', $request->company_status);
            });
        }

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }


        return $query;
    }

    public function checkEmail()
    {
        Mail::raw('This is a test email.', function ($message) {
            $message->to('sandeep@saasintegrator.com')->subject('Test Email');
        });
    }

    public function store(EmployeeCreateRequest $request)
    {
        return $this->repository->storeEmployee($request);

    }
    public function update(EmployeeUpdateRequest $request, $id)
    {
        try {
            $request->id = $id;
            return $this->updateEmployee($request);
        } catch (MarvelException $th) {
            throw new MarvelException(COULD_NOT_UPDATE_THE_RESOURCE);
        }
    }
    public function updateEmployee(Request $request)
    {
        $id = $request->id;
        if ($request->user()->hasPermissionTo(Permission::SUPER_ADMIN) || $request->user()->hasPermissionTo(Permission::STORE_OWNER)) {
            return $this->repository->updateEmployee($request, $id);
        }
        throw new AuthorizationException(NOT_AUTHORIZED);
    }
    public function destroy(Request $request, $id)
    {
        try {
            $request->id = $id;
            return $this->deleteEmployee($request);
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
                'message' => 'Employees deleted successfully.',
                'deleted_ids' => $ids,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not delete the resources.',
            ], 500);
        }
    }


    public function deleteEmployee(Request $request)
    {
        $id = $request->id;
        if ($request->user()->hasPermissionTo(Permission::SUPER_ADMIN) || $request->user()->hasPermissionTo(Permission::STORE_OWNER)) {
            try {
                $employee = $this->repository->findOrFail($id);


            } catch (Exception $e) {
                throw new ModelNotFoundException(NOT_FOUND);
            }
            $employee->delete();

            $userr = User::where('id',$employee->owner_id)->first();
            $userr->delete();
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
                is_numeric($slug) => $shop->with(['shop','wallet','orders'])->where('id', $slug)->firstOrFail(),
                is_string($slug)  => $shop->with(['shop','wallet','orders'])->where('slug', $slug)->firstOrFail(),
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
//            if (!$request->user()->hasPermissionTo(Permission::SUPER_ADMIN)) {
//                throw new MarvelException(NOT_AUTHORIZED);
//            }
            $id = $request->id;
            try {
                $shop = $this->repository->with('owner')->findOrFail($id);
            } catch (Exception $e) {
                throw new ModelNotFoundException(NOT_FOUND);
            }
            $shop->is_active = true;
            if ($shop->owner) {
                $shop->owner->is_active = true;
                $shop->owner->save(); // Save changes to the owner
            }
            $shop->save();

            return $shop;
        } catch (MarvelException $th) {
            throw new MarvelException(SOMETHING_WENT_WRONG."221");
        }
    }

    public function disApprove(Request $request)
    {
        try {
//            if (!$request->user()->hasPermissionTo(Permission::SUPER_ADMIN)) {
//                throw new MarvelException(NOT_AUTHORIZED);
//            }
            $id = $request->id;
            try {
                $shop = $this->repository->with('owner')->findOrFail($id);
            } catch (Exception $e) {
                throw new ModelNotFoundException(NOT_FOUND);
            }

            $shop->is_active = false;
            if ($shop->owner) {
                $shop->owner->is_active = false;
                $shop->owner->save(); // Save changes to the owner
            }
            $shop->save();


            return $shop;
        } catch (MarvelException $th) {
            throw new MarvelException(SOMETHING_WENT_WRONG."245");
        }
    }
}

