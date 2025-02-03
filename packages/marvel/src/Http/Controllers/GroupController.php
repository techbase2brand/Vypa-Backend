<?php

namespace Marvel\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Marvel\Enums\Permission;
use Marvel\Database\Models\Shop;
use Marvel\Database\Models\Group;
use Marvel\Database\Models\Employee;
use Marvel\Database\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Marvel\Exceptions\MarvelException;
use Marvel\Http\Requests\GroupCreateRequest;
use Marvel\Http\Requests\GroupUpdateRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Marvel\Database\Repositories\GroupRepository;

class GroupController extends CoreController
{
    public $repository;

    public function __construct(GroupRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Collection|Shop[]
     */
    public function index(Request $request)
    {
        $limit = $request->limit ? $request->limit : 15;
        return $this->fetchGroups($request)->paginate($limit)->withQueryString();
    }

    public function fetchGroups(Request $request)
    {
        $query = $this->repository->where('id', '!=', null);

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        return $query;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param GroupCreateRequest $request
     * @return mixed
     */
    public function store(GroupCreateRequest $request)
    {
        try {
            if ($request->user()->hasPermissionTo(Permission::STORE_OWNER)) {
                return $this->repository->storeGroup($request);
            }
            throw new AuthorizationException(NOT_AUTHORIZED);
        } catch (MarvelException $th) {
            throw new MarvelException(COULD_NOT_CREATE_THE_RESOURCE);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $slug
     * @return JsonResponse
     */
    public function show($slug, Request $request)
    {
        $shop = $this->repository;

        try {
            return match (true) {
                is_numeric($slug) => $shop->where('id', $slug)->firstOrFail(),
                is_string($slug)  => $shop->where('slug', $slug)->firstOrFail(),
            };
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ShopUpdateRequest $request
     * @param int $id
     * @return array
     */
    public function update(GroupUpdateRequest $request, $id)
    {
        try {
            $request->id = $id;
            return $this->updateGroup($request);
        } catch (MarvelException $th) {
            throw new MarvelException(COULD_NOT_UPDATE_THE_RESOURCE);
        }
    }

    public function updateGroup(Request $request)
    {
        $id = $request->id;
        if ($request->user()->hasPermissionTo(Permission::SUPER_ADMIN) || ($request->user()->hasPermissionTo(Permission::STORE_OWNER) && ($request->user()->shops->contains($id)))) {
            return $this->repository->updateGroup($request, $id);
        }
        throw new AuthorizationException(NOT_AUTHORIZED);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $request->id = $id;
            return $this->deleteGroup($request);
        } catch (MarvelException $th) {
            throw new MarvelException(COULD_NOT_DELETE_THE_RESOURCE);
        }
    }
    public function budget(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'budget' => 'required|numeric|min:0',
            'date' => 'required|date',
            'groups' => 'required|array',
            'groups.*' => 'exists:groups,id', // Ensure each group ID exists
        ]);

        // Find the groups based on the provided IDs
        $groups = Group::find($request->groups);

        // Prepare an array to hold wallet data for batch insertion
        $walletData = [];

        foreach ($groups as $group) {
            // Decode selected employees
            if(!empty($group->selectedEmployees)){
                $employees = $group->selectedEmployees;
                foreach ($employees as $employee) {
                    $employeeId = Employee::find($employee['id'])->pluck('owner_id');
                    $walletData[] = [
                        'total_points' => $request->budget,
                        'points_used' => 0,
                        'available_points' => $request->budget,
                        'customer_id' => $employeeId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Decode selected tags
            if(!empty($group->selectedTags)) {
                $tags = $group->selectedTags;
                foreach ($tags as $tag) {
                    // Get employee IDs associated with the tag
                    $employeeIds = Employee::where('tag', $tag['name'])->pluck('owner_id');
                    foreach ($employeeIds as $employeeId) {
                        $walletData[] = [
                            'total_points' => $request->budget,
                            'points_used' => 0,
                            'available_points' => $request->budget,
                            'customer_id' => $employeeId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }
        dd($walletData);
        // Insert all wallet data in one go
        if (!empty($walletData)) {
            // Insert or update wallet data
            foreach ($walletData as $data) {
                Wallet::updateOrInsert(
                    [
                        'customer_id' => $data['customer_id'],
                        'total_points' => $data['total_points'],
                        'available_points' => $data['available_points'],
                    ],
                    [
                        'points_used' => $data['points_used'],
                        'available_points' => $data['available_points'],
                        'updated_at' => now(),
                    ]
                );
            }
        }

        return response()->json(['message' => 'Wallets updated successfully.'], 200);
    }
    public function deleteGroup(Request $request)
    {
        $id = $request->id;
        if ($request->user()->hasPermissionTo(Permission::SUPER_ADMIN) || ($request->user()->hasPermissionTo(Permission::STORE_OWNER) && ($request->user()->shops->contains($id)))) {
            try {
                $shop = $this->repository->findOrFail($id);
            } catch (\Exception $e) {
                throw new ModelNotFoundException(NOT_FOUND);
            }
            $shop->delete();
            return $shop;
        }
        throw new AuthorizationException(NOT_AUTHORIZED);
    }

}
