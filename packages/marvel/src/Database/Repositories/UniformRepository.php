<?php


namespace Marvel\Database\Repositories;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
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

class UniformRepository extends BaseRepository
{

    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name'        => 'like',
        'users.name'
    ];

    /**
     * @var array
     */
    protected $dataArray = [
        'name',
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

    public function storeUniform($request)
    {
        $user = Auth::user();
        if($user!=null && $user->roles()->whereIn('name', ['super_admin'])->exists())
        {
            $created_by="admin";
        }else{
            $created_by="company";
        }
        try {
            // $data = $request->only($this->dataArray);


            $data['user_id'] = $request->user()->id;


            if ($request->has('name')) {
                $data['name'] = ($request->input('name'));
            }


            $uniform = $this->create($data);

            return $uniform;
        } catch (Exception $e) {
            throw new HttpException(400, COULD_NOT_CREATE_THE_RESOURCE."_EMPLOYEE-".$e,);
        }
    }

    public function updateUniform($request, $id)
    {

        $uniform = $this->findOrFail($id);
        try {


            if ($request->has('name')) {
                $uniform->name = $request->input('name');
            }

            // Save shop updates
            $uniform->save();
            // Handle password change

            return $uniform;
        } catch (Exception $e) {
            throw new HttpException(400, COULD_NOT_UPDATE_THE_RESOURCE."_Shop-".$e);
        }
    }
}
