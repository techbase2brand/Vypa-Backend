<?php

namespace Marvel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Marvel\Exceptions\MarvelException;
use Marvel\Http\Requests\CompanySettingRequest;
use Illuminate\Database\Eloquent\Collection;
use Marvel\Database\Repositories\CompanySettingRepository;


class CompanySettingController extends CoreController
{
    public $repository;

    public function __construct(CompanySettingRepository $repository)
    {
        $this->repository = $repository;
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Collection|Type[]
     */
    public function index(Request $request)
    {
        $user=Auth::user();
        $company_setting = $this->repository->where('shop_id',$user->id)->get();
        return $company_setting;
    }
    public function show(Request $request,$id)
    {
        $company_setting = $this->repository->where('shop_id',$id)->get();
        return $company_setting;
    }
    public function getResult(Request $request)
    {
        $company_setting = $this->repository->get();
        return $company_setting;
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param CompanySettingRequest $request
     * @return mixed
     * @throws ValidatorException
     */
    public function store(CompanySettingRequest $request)
    {
        try {
            $company_setting = $this->repository->where('shop_id',Auth::user()->id)->get();
            return $this->repository->storeCompanySetting($request,$company_setting);
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }





}
