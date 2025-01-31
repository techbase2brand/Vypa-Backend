<?php


namespace Marvel\Database\Repositories;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Mail;
use Marvel\Database\Models\Balance;
use Marvel\Database\Models\Product;
use Marvel\Database\Models\Group;
use Marvel\Database\Models\User;
use Marvel\Database\Models\Employee;
use Marvel\Enums\DefaultStatusType;
use Marvel\Enums\Permission;
use Marvel\Enums\ProductVisibilityStatus;
use Marvel\Events\ProcessOwnershipTransition;
use Marvel\Events\ShopMaintenance;
use Marvel\Http\Requests\TransferShopOwnerShipRequest;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Marvel\Mail\CompanyRegisteredMail;

class GroupRepository extends BaseRepository
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

    protected $dataArray = [
        'name',
        'slug',
        'description',
        'cover_image',
        'logo',
        'is_active',
        'address',
        'settings',
        'notifications',
        'businessContactdetail',
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
        return Group::class;
    }
    public function addUsers($group, $users)
    {

        try {
            // Attach users to the group
            $group->users()->syncWithoutDetaching($users);

            return true;
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json([
                'error' => 'Failed to add users to the group',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    public function storeGroup($request)
    {
        try {
            $data['slug'] = $this->makeSlug($request);
            if ($request->has('name')) {
                $data['name'] = ($request->input('name'));
            }

            if ($request->has('tag')) {
                $data['tag'] = ($request->input('tag'));
                switch ($data['tag']){
                    case 'Tag Based':
                        if ($request->has('selectedTags')) {
                            $data['selectedTags'] = $request->input('selectedTags');
                        }
                        break;
                    default:
                        if ($request->has('selectedEmployees')) {
                            $data['selectedEmployees'] = $request->input('selectedEmployees');
                        }
                        $users=[];
                        $array=$data['selectedEmployees'];
                        foreach($array as $a) {
                            $users[] =$a['id'];
                        }
                }
            }

            $group = $this->create($data);
            if(isset($users)) {
                $this->addUsers($group, $users);
            }
            return $group;

        } catch (Exception $e) {
            throw new HttpException(400, COULD_NOT_CREATE_THE_RESOURCE."_SHOP-".$e,);
        }
    }



    public function updateGroup($request, $id)
    {
        try {
            $group = $this->findOrFail($id);

            // Update shop details
            if ($request->has('name')) {
                $group->name = $request->input('name');
            }

            if ($request->has('tag')) {
                $group->tag = ($request->input('tag'));
                switch ($group->tag){
                    case 'Tag Based':
                        if ($request->has('selectedTags')) {
                            $group->selectedTags = $request->input('selectedTags');
                        }
                        break;
                    default:
                        if ($request->has('selectedEmployees')) {
                            $group->selectedEmployees = $request->input('selectedEmployees');
                        }
                        $users=[];
                        $array=$group->selectedEmployees;
                        foreach($array as $a) {
                            $users[] =$a['id'];
                        }
                }
            }


            // Save shop updates
            $group->save();


            return $group;
        } catch (Exception $e) {
            throw new HttpException(400, COULD_NOT_UPDATE_THE_RESOURCE."_Group-".$e);
        }
    }

}
