<?php


namespace Marvel\Database\Repositories;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Marvel\Database\Models\Balance;
use Marvel\Database\Models\OwnershipTransfer;
use Marvel\Database\Models\Product;
use Marvel\Database\Models\Employee;
use Marvel\Database\Models\User;
use Marvel\Enums\DefaultStatusType;
use Marvel\Enums\Permission;
use Marvel\Enums\ProductVisibilityStatus;
use Marvel\Events\ProcessOwnershipTransition;
use Marvel\Events\ShopMaintenance;
use Marvel\Http\Requests\TransferShopOwnerShipRequest;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmployeeRepository extends BaseRepository
{

    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name'        => 'like',
        'is_active',
        'categories.slug',
        'users.name'
    ];

    /**
     * @var array
     */
    // protected $dataArray = [
    //     'name',
    //     'slug',
    //     'description',
    //     'cover_image',
    //     'logo',
    //     'is_active',
    //     'address',
    //     'settings',
    //     'notifications',
    // ];
    protected $dataArray = [
        'name',
        'slug',
        'description',
        'cover_image',
        'logo',
        'is_active',
        'address',
        'notifications',
        'primary_contact_detail',
        'loginDetails',
    ];


    public function boot()
    {
        try {
            $this->pushCriteria(app(RequestCriteria::class));
        } catch (RepositoryException $e) {
            //
        }
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Employee::class;
    }

    // public function storeShop($request)
    // {
    //     try {
    //         $data = $request->only($this->dataArray);
    //         $data['slug'] = $this->makeSlug($request);
    //         $data['owner_id'] = $request->user()->id;
    //         print_r($data);
    //         die;
    //         $shop = $this->create($data);
    //         if (isset($request['categories'])) {
    //             $shop->categories()->attach($request['categories']);
    //         }
    //         if (isset($request['balance']['payment_info'])) {
    //             $shop->balance()->create($request['balance']);
    //         }

    //         // TODO : why this code is needed
    //         // $shop->categories = $shop->categories;
    //         // $shop->staffs = $shop->staffs;
    //         return $shop;
    //     } catch (Exception $e) {
    //         throw new HttpException(400, COULD_NOT_CREATE_THE_RESOURCE);
    //     }
    // }
    public function storeEmployee($request)
    {
        try {
            // $data = $request->only($this->dataArray);

            $data['slug'] = $this->makeSlug($request);
            $data['owner_id'] = $request->user()->id??6;
            if ($request->has('name')) {
                $data['name'] = ($request->input('name'));
            }
            if ($request->has('cover_image')) {
                $data['cover_image'] = ($request->input('cover_image'));
            }
            if ($request->has('logo')) {
                $data['logo'] = ($request->input('logo'));
            }

            if ($request->has('address')) {
                $data['address'] = $request->input('address');
            }

            if ($request->has('primary_contact_detail')) {
                $data['primary_contact_detail'] = $request->input('primary_contact_detail');
            }
            if ($request->has('settings')) {
                $data['settings'] = $request->input('settings');
            }
            if ($request->has('description')) {
                $data['description'] = $request->input('description');
            }

            $shop = $this->create($data);

            if ($request->has('businessContactdetail')) {
                $shop->business_contact_detail = ($request->input('businessContactdetail'));
                $shop->save();
            }


            if (isset($request['categories'])) {
                $shop->categories()->attach($request['categories']);
            }

            if (isset($request['balance']['payment_info'])) {
                $shop->balance()->create($request['balance']);
            }


            if ($request->has('loginDetails')) {
                $loginDetails = $request->input('loginDetails');

                $user = User::create([
                    'name' => $request->input('name'),
                    'email' => $loginDetails['username or email'],
                    'password' => bcrypt($loginDetails['password']),
                ]);
                $shop->owner_id = $user->id;
                $shop->save();
            }

            return $shop;

        } catch (Exception $e) {
            throw new HttpException(400, COULD_NOT_CREATE_THE_RESOURCE."_EMPLOYEE-".$e,);
        }
    }



    // public function updateShop($request, $id)
    // {
    //     try {
    //         $shop = $this->findOrFail($id);
    //         if (isset($request['categories'])) {
    //             $shop->categories()->sync($request['categories']);
    //         }
    //         if (isset($request['balance'])) {
    //             if (isset($request['balance']['admin_commission_rate']) && $shop->balance->admin_commission_rate !== $request['balance']['admin_commission_rate']) {
    //                 if ($request->user()->hasPermissionTo(Permission::SUPER_ADMIN)) {
    //                     $this->updateBalance($request['balance'], $id);
    //                 }
    //             } else {
    //                 $this->updateBalance($request['balance'], $id);
    //             }
    //         }
    //         $data = $request->only($this->dataArray);
    //         if (!empty($request->slug) &&  $request->slug != $shop['slug']) {
    //             $data['slug'] = $this->makeSlug($request);
    //         }
    //         $shop->update($data);

    //         // TODO : why this code is needed
    //         // $shop->categories = $shop->categories;
    //         // $shop->staffs = $shop->staffs;
    //         // $shop->balance = $shop->balance;


    //         // 1. Shop owner maintenance time set korbe.. then ekta event fire hobe jeita shop notifications (email, sms) send korbe super-admin, vendor, staff, oi specific shop er front-end a ekta notice dekhabe with countdown.
    //         // 2. countDown start er 1 day ago or 6 hours ago ekta final email/sms dibe vendor, staff k
    //         // 3. countdown onStart a sob product private
    //         // 4. countdown onComplete a sob product public

    //         if (isset($request['settings']['isShopUnderMaintenance'])) {
    //             if ($request['settings']['isShopUnderMaintenance']) {
    //                 event(new ShopMaintenance($shop, 'enable'));
    //             } else {
    //                 event(new ShopMaintenance($shop, 'disable'));
    //             }
    //         }

    //         return $shop;
    //     } catch (Exception $e) {
    //         throw new HttpException(400, COULD_NOT_UPDATE_THE_RESOURCE);
    //     }
    // }
    public function updateEmployee($request, $id)
    {
        try {
            $shop = $this->findOrFail($id);

            // Update shop details
            if ($request->has('name')) {
                $shop->name = $request->input('name');
            }
            if ($request->has('cover_image')) {
                $shop->cover_image = $request->input('cover_image');
            }
            if ($request->has('logo')) {
                $shop->logo = $request->input('logo');
            }
            if ($request->has('address')) {
                $shop->address = $request->input('address');
            }
            if ($request->has('primary_contact_detail')) {
                $shop->primary_contact_detail = $request->input('primary_contact_detail');
            }

            if ($request->has('description')) {
                $shop->description = $request->input('description');
            }

            // Save shop updates
            $shop->save();

            // Handle categories
            if (isset($request['categories'])) {
                $shop->categories()->sync($request['categories']);
            }

            // Handle balance updates
            if (isset($request['balance'])) {
                if (isset($request['balance']['admin_commission_rate']) && $shop->balance->admin_commission_rate !== $request['balance']['admin_commission_rate']) {
                    if ($request->user()->hasPermissionTo(Permission::SUPER_ADMIN)) {
                        $this->updateBalance($request['balance'], $id);
                    }
                } else {
                    $this->updateBalance($request['balance'], $id);
                }
            }



            // Handle password change
            if ($request->has('loginDetails') && isset($request->loginDetails['password'])) {
                $loginDetails = $request->input('loginDetails');

                // Find the owner user
                $owner = User::find($shop->owner_id);

                if ($owner) {
                    // Update the user's password
                    $owner->password = bcrypt($loginDetails['password']);
                    $owner->save();
                }
            }


            return $shop;
        } catch (Exception $e) {
            throw new HttpException(400, COULD_NOT_UPDATE_THE_RESOURCE."_Shop-".$e);
        }
    }


    public function updateBalance($balance, $shop_id)
    {
        if (isset($balance['id'])) {
            Balance::findOrFail($balance['id'])->update($balance);
        } else {
            $balance['shop_id'] = $shop_id;
            Balance::create($balance);
        }
    }

    public function transferShopOwnership(TransferShopOwnerShipRequest $request)
    {
        $user = $request->user();
        $shopId = $request->shop_id ?? null;

        if (!$this->hasPermission($user, $shopId)) {
            throw new AuthorizationException(NOT_AUTHORIZED);
        }

        $shop = $this->findOrFail($shopId);
        $previousOwner = $shop->owner;

        $newOwnerId = $request->vendor_id;
        $newOwner = User::findOrFail($newOwnerId);

        OwnershipTransfer::updateOrCreate(
            [
                "shop_id"    => $shopId,
            ],
            [
                "from"       => $previousOwner->id,
                "message"    => $request?->message,
                "to"         => $newOwnerId,
                "created_by" => $user->id,
                "status"     => DefaultStatusType::PENDING,
            ]
        );

        $optional = [
            'message' =>  $request?->vendorMessage,
        ];

        event(new ProcessOwnershipTransition($shop, $previousOwner, $newOwner, $optional));

        return $shop;
    }
}
