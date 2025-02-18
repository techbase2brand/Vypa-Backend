<?php

namespace Marvel\Database\Repositories;

use Illuminate\Support\Facades\Auth;
use Marvel\Database\Models\CompanySetting;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CompanySettingRepository  extends BaseRepository
{
    protected $fieldSearchable = [
    ];

    protected $dataArray = [
        'shop_id',
        'rear_logo',
        'front_logo',
        'name',
        'image'
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
        return CompanySetting::class;
    }

    public function storeCompanySetting($request,$company_setting)
    {
        try {
            if(!isset($company_setting[0]->id)) {
                $user=Auth::user();
                $request['shop_id']=$user->id;
                $company_setting = $this->create($request->only($this->dataArray));
            }
            else{
                $company_setting = $this->update($request->only($this->dataArray),$company_setting[0]->id);
            }
            return $company_setting;
        } catch (Throwable $th) {
            throw new HttpException(400, COULD_NOT_CREATE_THE_RESOURCE."_contact");
        }
    }

}
