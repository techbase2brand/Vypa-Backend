<?php

namespace Marvel\Database\Repositories;

use Illuminate\Support\Facades\Auth;
use Marvel\Database\Models\Contact;
use Marvel\Database\Repositories\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NotificationRepository  extends BaseRepository
{
    protected $fieldSearchable = [
        'name'        => 'like',
        'notification',
    ];

    protected $dataArray = [
        'name',
        'slug',
        'shop_id',
        'selectedfor',
        'notification',
        'employee_id'
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
        return Notification::class;
    }

    public function storeNotification($request)
    {
        $user=Auth::user();
        try {
            $request['slug'] = $this->makeSlug($request);
            $request['shop_id']=isset($user->shop_id)?$user->shop_id:$user->id;
            $request['employee_id']=$user->id??0;
            $contact = $this->create($request->only($this->dataArray));
            return $contact;
        } catch (Throwable $th) {
            throw new HttpException(400, COULD_NOT_CREATE_THE_RESOURCE."_contact");
        }
    }

    public function updateNotification($request, $notification)
    {
        try {
            return $notification->update($request->only($this->dataArray));
        } catch (Throwable $th) {
            throw new HttpException(400, COULD_NOT_UPDATE_THE_RESOURCE."_Contact-".$th);
        }
    }
}
