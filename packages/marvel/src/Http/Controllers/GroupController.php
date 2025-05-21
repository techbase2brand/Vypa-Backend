<?php

namespace Marvel\Http\Controllers;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Marvel\Enums\Permission;
use Marvel\Database\Models\Shop;
use Marvel\Database\Models\Group;
use Marvel\Database\Models\Employee;
use Marvel\Database\Models\Wallet;
use Marvel\Database\Models\RequestBudget;
use Illuminate\Http\JsonResponse;
use Marvel\Exceptions\MarvelException;
use Marvel\Http\Requests\GroupCreateRequest;
use Marvel\Http\Requests\GroupUpdateRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Marvel\Database\Repositories\GroupRepository;
use Illuminate\Support\Facades\Auth;

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
            'groups.*' => 'exists:groups,id',
        ]);

        // Find the groups based on the provided IDs
        $groups = Group::find($request->groups);

        // Prepare an array to hold wallet data for batch insertion
        $walletData = [];

        foreach ($groups as $group) {
            // Decode selected employees
            if (!empty($group->selectedEmployees)) {
                $employees = $group->selectedEmployees;
                foreach ($employees as $employee) {
                    $employeeId = Employee::where('id', $employee['id'])->value('owner_id');
                    if ($employeeId) {
                        $walletData[] = [
                            'total_points' => $request->budget,
                            'customer_id' => $employeeId,
                            'expiry_date' => $request->date,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            // Decode selected tags
            if (!empty($group->selectedTags)) {
                $tags = $group->selectedTags;
                foreach ($tags as $tag) {
                    // Get employee IDs associated with the tag
                    $employeeIds = Employee::where('tag', $tag['name'])->pluck('owner_id');
                    foreach ($employeeIds as $employeeId) {
                        $walletData[] = [
                            'total_points' => $request->budget,
                            'customer_id' => $employeeId,
                            'expiry_date' => $request->date,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }

        // Insert or update wallet data
        if (!empty($walletData)) {
            // Get all customer IDs from the wallet data
            $customerIds = array_column($walletData, 'customer_id');

            // Fetch existing wallets to get current points_used
            $existingWallets = Wallet::whereIn('customer_id', $customerIds)
                ->get()
                ->keyBy('customer_id');

            foreach ($walletData as $data) {
                $customerId = $data['customer_id'];
                $totalPoints = $data['total_points'];
                $expiryDate = $data['expiry_date'];

                // Check if the wallet exists
                if (isset($existingWallets[$customerId])) {
                    // Existing wallet: calculate available_points based on current points_used
                    $currentPointsUsed = $existingWallets[$customerId]->points_used;
                    $availablePoints = $totalPoints - $currentPointsUsed;

                    // Update the existing wallet
                    Wallet::where('customer_id', $customerId)->update([
                        'total_points' => $totalPoints,
                        'available_points' => $availablePoints,
                        'expiry_date' => $expiryDate,
                        'updated_at' => now(),
                    ]);
                } else {
                    // New wallet: set points_used to 0 and available_points = total_points
                    Wallet::create([
                        'customer_id' => $customerId,
                        'total_points' => $totalPoints,
                        'points_used' => 0,
                        'available_points' => $totalPoints,
                        'expiry_date' => $expiryDate,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Wallets updated successfully.'], 200);
    }
    public function assignBudget(Request $request)
    {
        // Retrieve the JSON data from the request
        $data = $request->json()->all();
        $data = $data['payload']??$data;
        // Debugging: Dump the data to check its structure
        // dd($data);

        // Fetch existing wallets to get current points_used
        $existingWallets = Wallet::whereIn('customer_id', [$data['employee_id']])
            ->get()
            ->keyBy('customer_id');

        $customerId = $data['employee_id'];
        $totalPoints = str_replace('$', '', $data['assign_budget']);
        // $expiryDate = $data['expiry_date']; // Uncomment if you have this field

        // Check if the wallet exists
        if (isset($existingWallets[$customerId])) {
            // Existing wallet: calculate available_points based on current points_used
            $currentPoints = $existingWallets[$customerId]->available_points;
            $availablePoints = (int)$totalPoints + $currentPoints;

            // Update the existing wallet
            Wallet::where('customer_id', $customerId)->update([
                'total_points' => $availablePoints,
                'available_points' => $availablePoints,
                'updated_at' => now(),
            ]);
        } else {
            // New wallet: set points_used to 0 and available_points = total_points
            Wallet::create([
                'customer_id' => $customerId,
                'total_points' => $totalPoints,
                'points_used' => 0,
                'available_points' => $totalPoints,
                // 'expiry_date' => $expiryDate, // Uncomment if you have this field
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return Wallet::whereIn('customer_id', [$data['employee_id']])
            ->get();
    }
    public function requestBudget(Request $request)
    {

        // Retrieve the JSON data from the request
        $data = $request->json()->all();
        $data = $data['payload']??$data;
        // Debugging: Dump the data to check its structure

        $customerId = $data['employee_id'];
        $totalPoints = $data['assign_budget'];
        // $expiryDate = $data['expiry_date']; // Uncomment if you have this field
        $employeeData = Employee::whereIn('owner_id', [$data['employee_id']])->first();

            // New wallet: set points_used to 0 and available_points = total_points
            RequestBudget::create([
                'customer_id' => $customerId,
                'points_requested' => $totalPoints,
                'approved' => 0,
                'shop_id' => $employeeData->shop_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return RequestBudget::whereIn('customer_id', [$data['employee_id']])
            ->get();
    }
    public function showRequestBudget(Request $request)
    {
        $userId = auth()->id();

        // Get the shop(s) owned by this user
        $shops = Shop::where('owner_id', $userId)->get();

        // If you only need the IDs
        $shopIds = $shops->pluck('id')->toArray();

        // Then use these IDs to find request budgets
        return RequestBudget::whereIn('shop_id', $shopIds)->with('user')
            ->paginate(20);
    }
    public function approveRequestBudget(Request $request)
    {

        $id=$request->input('id');
        RequestBudget::where('id', $id)->update([
                'approved' => 1,
                'updated_at' => now(),
            ]);

        $finals= RequestBudget::where('id', $id)
            ->get();
        $final=$finals->toArray()[0];
            $existingWallets = Wallet::whereIn('customer_id', [$final['customer_id']])
            ->get()
            ->keyBy('customer_id');

        $customerId = $final['customer_id'];
        $points = $final['points_requested'];
        // $expiryDate = $data['expiry_date']; // Uncomment if you have this field

        // Check if the wallet exists
        if (isset($existingWallets[$customerId])) {
            // Existing wallet: calculate available_points based on current points_used
            $currentPoints = $existingWallets[$customerId]->available_points;
            $availablePoints = (int)$points + $currentPoints;

            // Update the existing wallet
            Wallet::where('customer_id', $customerId)->update([
                'total_points' => $currentPoints,
                'available_points' => $availablePoints,
                'updated_at' => now(),
            ]);
        } else {
            // New wallet: set points_used to 0 and available_points = total_points
            Wallet::create([
                'customer_id' => $customerId,
                'total_points' => 0,
                'points_used' => 0,
                'available_points' => $points,
                // 'expiry_date' => $expiryDate, // Uncomment if you have this field
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

            return $final;
    }
    public function disapproveRequestBudget(Request $request)
    {

        $id=$request->input('id');
        RequestBudget::where('id', $id)->update([
                'approved' => 0,
                'updated_at' => now(),
            ]);

        $finals= RequestBudget::where('id', $id)
            ->get();
        $finals= RequestBudget::where('id', $id)
            ->get();
        $final=$finals->toArray()[0];
            $existingWallets = Wallet::whereIn('customer_id', [$final['customer_id']])
            ->get()
            ->keyBy('customer_id');

        $customerId = $final['customer_id'];
        $points = $final['points_requested'];
        // $expiryDate = $data['expiry_date']; // Uncomment if you have this field

        // Check if the wallet exists
        if (isset($existingWallets[$customerId])) {
            // Existing wallet: calculate available_points based on current points_used
            $currentPoints = $existingWallets[$customerId]->available_points;
            $availablePoints =  $currentPoints-(int)$points;
            // Update the existing wallet
            Wallet::where('customer_id', $customerId)->update([
                'available_points' => $availablePoints,
                'updated_at' => now(),
            ]);
        }
        return $final;
    }
    public function deleteGroup(Request $request)
    {
        $id = $request->id;
        if ($request->user()->hasPermissionTo(Permission::SUPER_ADMIN) || ($request->user()->hasPermissionTo(Permission::STORE_OWNER) && ($request->user()->shops->contains($id)))) {
            try {
                $shop = $this->repository->findOrFail($id);
            } catch (Exception $e) {
                throw new ModelNotFoundException(NOT_FOUND);
            }
            $shop->delete();
            return $shop;
        }
        throw new AuthorizationException(NOT_AUTHORIZED);
    }

}
