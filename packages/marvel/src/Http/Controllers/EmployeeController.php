<?php

namespace Marvel\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Marvel\Enums\Permission;
use Marvel\Database\Models\Employee;
use Marvel\Database\Models\User;
use Illuminate\Http\JsonResponse;
use Marvel\Database\Models\Balance;
use Marvel\Database\Models\Product;
use Illuminate\Support\Facades\Hash;
use Marvel\Exceptions\MarvelException;
use Marvel\Http\Requests\EmployeeCreateRequest;
use Marvel\Http\Requests\EmployeeUpdateRequest;
use Marvel\Http\Requests\TransferShopOwnerShipRequest;
use Marvel\Http\Requests\UserCreateRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Marvel\Database\Models\Settings;
use Marvel\Database\Repositories\EmployeeRepository;
use Marvel\Enums\Role;
use Marvel\Traits\OrderStatusManagerWithPaymentTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;



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
        return $this->repository->with('shop')->with(['owner.profile'])->where('id', '!=', null);
    }
    public function filter(Request $request)
    {
        // Validate input
        $shopId = $request->input('shop_id');

        // Build the query
        $query = $this->repository
            ->with('shop')
            ->with(['owner.profile'])
            ->where('id', '!=', null);

        // Apply filters
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $query->whereHas('owner', function ($q) {
            $q->where('is_active', 1);
        });

        $query->whereHas('shop.owner', function ($q) {
            $q->where('is_active', 1);
        });

        // Execute and return the result
        $result = $query->get();
        return $result;
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
        if ($request->user()->hasPermissionTo(Permission::SUPER_ADMIN) || ($request->user()->hasPermissionTo(Permission::STORE_OWNER) && ($request->user()->shops->contains($id)))) {
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
            ->with(['owner']);

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
}

