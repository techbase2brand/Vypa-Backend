<?php


namespace Marvel\Database\Repositories;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Marvel\Database\Models\Balance;
use Marvel\Database\Models\OwnershipTransfer;
use Marvel\Database\Models\Product;
use Marvel\Database\Models\Employee;
use Marvel\Database\Models\Shop;
use Marvel\Database\Models\Wallet;
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
        'logo',
        'is_active',
        'address',
        'notifications',
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
        $user = Auth::user();
        if($user!=null && $user->roles()->whereIn('name', ['super_admin'])->exists())
        {
            $created_by="admin";
        }else{
            $created_by="company";
        }
        $data['is_active']=($user!=null)?1:0;
        try {
                // $data = $request->only($this->dataArray);

                $data['slug'] = $this->makeSlug($request);
                //$data['owner_id'] = $request->user()->id??6;


                if ($request->has('name')) {
                    $data['name'] = ($request->input('name'));
                }
                if ($request->has('last_name')) {
                    $data['last_name'] = ($request->input('last_name'));
                }
                if ($request->has('web')) {
                    $data['web'] = ($request->input('web'));
                }
                if ($request->has('address')) {
                    $data['address'] = ($request->input('address'));
                }
                if ($request->has('contact_info')) {
                    $data['contact_info'] = ($request->input('contact_info'));
                }
                if ($request->has('tag')) {
                    $data['tag'] = ($request->input('tag'));
                }
                if ($request->has('Employee_email')) {
                    $data['email'] = ($request->input('Employee_email'));
                }
                if ($request->has('logo')) {
                    $data['logo'] = ($request->input('logo'));
                }

                if ($request->has('contact_no')) {
                    $data['contact_no'] = $request->input('contact_no');
                }

                if ($request->has('job_title')) {
                    $data['job_title'] = $request->input('job_title');
                }
                if ($request->has('joining_date')) {
                    $data['joining_date'] = $request->input('joining_date');
                }
                if ($request->has('date_of_birth')) {
                $data['date_of_birth'] = $request->input('date_of_birth');
                }
                if ($request->has('gender')) {
                    $data['gender'] = $request->input('gender');
                }
            if ($request->has('shop_id')) {
                $shopId = $request->input('shop_id');
                if (!Shop::where('id', $shopId)->exists()) {
                    throw new HttpException(400, 'The specified shop does not exist.');
                }
                $data['shop_id'] = $shopId; // Assign the valid shop_id
            }

                $shop = $this->create($data);

            if ($request->has('shop_id')) {
                $shopId = $request->input('shop_id');
                if (!Shop::where('id', $shopId)->exists()) {
                    throw new HttpException(400, 'The specified shop does not exist.');
                }
                $shop->shop_id = $shopId; // Assign the valid shop_id
                $shop->save();
            }




            if (isset($request['categories'])) {
                    $shop->categories()->attach($request['categories']);
                }

                if (isset($request['balance']['payment_info'])) {
                    $shop->balance()->create($request['balance']);
                }




                    $user = User::create([
                        'name' => $request->input('name'),
                        'email' => $request->input('Employee_email'),
                        'password' => Hash::make($request->input('password')),
                        'shop_id' => $request->input('shop_id'),
                        'created_by' =>$created_by,
                        'is_active' =>($user!=null)?1:0
                    ]);
                $user->givePermissionTo(Permission::CUSTOMER);
                $user->assignRole(Permission::CUSTOMER);
                    $shop->owner_id = $user->id;
                    $shop->save();


                    try {
                        Mail::raw('Congratulations, you are successfully registered as an Employee.', function ($message) use ($user) {
                            $message->to($user->email)->subject('Employee Registration');
                        });
                    } catch (\Exception $e) {
                        \Log::error('Mail sending failed: ' . $e->getMessage());
                    }


            if ($request->has('assign_budget')) {
                $dataWallet['total_points'] = $request->input('assign_budget');
                $dataWallet['points_used'] = 0;
                $dataWallet['available_points'] = $request->input('assign_budget');
                $dataWallet['expiry_date'] = $request->input('expiry_date') ?? null;
                $dataWallet['customer_id'] = $user->id;
                Wallet::insert($dataWallet);
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

        $employee = $this->findOrFail($id);
        try {
            $employee->shop_id = $request->input('shop_id');

            $employee->slug = $this->makeSlug($request);

            if ($request->has('name')) {
                $employee->name = $request->input('name');
            }
            if ($request->has('last_name')) {
                $employee->last_name = ($request->input('last_name'));
            }
            if($request->has('shipping_address')){
                $employee->shipping_address= $request->input('shipping_address');
            }
            if ($request->has('web')) {
                $employee->web = ($request->input('web'));
            }
            if ($request->has('address')) {
                $employee->address = ($request->input('address'));
            }
            if ($request->has('contact_info')) {
                $employee->contact_info = ($request->input('contact_info'));
            }

            if ($request->has('tag')) {
                $employee->tag = ($request->input('tag'));
            }
            if ($request->has('Employee_email')) {
                $employee->email = ($request->input('Employee_email'));
            }
            if ($request->has('logo')) {
                $employee->logo = ($request->input('logo'));
            }

            if ($request->has('contact_no')) {
                $employee->contact_no = $request->input('contact_no');
            }

            if ($request->has('job_title')) {
                $employee->job_title = $request->input('job_title');
            }
            if ($request->has('joining_date')) {
                $employee->joining_date = $request->input('joining_date');
            }
            if ($request->has('gender')) {
                $employee->gender = $request->input('gender');
            }
            ////////////
            if ($request->has('time_duration')) {
                $employee->time_duration = $request->input('time_duration');
            }
            if ($request->has('category')) {
                $employee->category = $request->input('category');
            }
            if ($request->has('purchase_limit_updates')) {
                $employee->purchase_limit_updates = $request->input('purchase_limit_updates')??false;
            }
            if ($request->has('orders_update')) {
                $employee->orders_update = $request->input('orders_update')??false;
            }
            if ($request->has('announcements')) {
                $employee->announcements = $request->input('announcements')??false;
            }
            if ($request->has('credit_card_option')) {
                $employee->credit_card_option = $request->input('credit_card_option')??false;
            }

            // Save shop updates
            $employee->save();
            // Handle password change
            if ($request->has('password')) {

                // Find the owner user
                $owner = User::find($employee->owner_id);

                if ($owner) {
                    // Update the user's password
                    $owner->password = bcrypt($request->input('password'));
                    $owner->save();
                }
            }
            // Fetch the current wallet record (if it exists)
            $wallet = Wallet::where('customer_id', $employee->owner_id)->first();

// Calculate available_points dynamically
            $pointsUsed = $wallet ? $wallet->points_used : 0;
            $availablePoints = $request->input('assign_budget') - $pointsUsed;

// Update or insert the wallet record
            Wallet::updateOrInsert(
                ['customer_id' => $employee->owner_id], // Condition to check if the record exists
                [
                    'total_points'      => $request->input('assign_budget'),
                    'points_used'       => $pointsUsed,
                    'available_points'  => $availablePoints, // Use the calculated value
                    'expiry_date'       => $request->input('expiry_date') ?? null,
                    'updated_at'        => now(), // Ensure timestamps are updated
                ]
            );


            return $employee;
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
