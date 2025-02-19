<?php

namespace Marvel\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Marvel\Database\Models\Address;
use Marvel\Database\Repositories\AddressRepository;
use Marvel\Enums\Permission;
use Marvel\Exceptions\MarvelException;
use Marvel\Http\Requests\AddressRequest;
use Prettus\Validator\Exceptions\ValidatorException;

class AddressController extends CoreController
{
    public $repository;

    public function __construct(AddressRepository $repository)
    {
        $this->repository = $repository;
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Collection|Address[]
     */
    public function index(Request $request)
    {
        return $this->repository->with('customer')->all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AddressRequest $request
     * @return mixed
     * @throws ValidatorException
     */
    public function store(AddressRequest $request)
    {
        try {
            $validatedData = $request->all();
            return $this->repository->create($validatedData);
        } catch (MarvelException $e) {
            throw new MarvelException(COULD_NOT_CREATE_THE_RESOURCE);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        try {
            return $this->repository->with('customer')->findOrFail($id);
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param AddressRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(AddressRequest $request, $id)
    {
        try {
            $validatedData = $request->all();
            return $this->repository->findOrFail($id)->update($validatedData);
        } catch (MarvelException $e) {
            throw new MarvelException(COULD_NOT_UPDATE_THE_RESOURCE."_address-".$e);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id, Request $request)
    {
        try {
            $user = $request->user();
            if ($user && $user->hasPermissionTo(Permission::SUPER_ADMIN)) {
                return $this->repository->findOrFail($id)->delete();
            } else {
                $address = $this->repository->findOrFail($id);
                if ($address->customer_id == $user->id) {
                    return $address->delete();
                }
            }
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }
}
