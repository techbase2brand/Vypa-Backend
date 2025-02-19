<?php

namespace Marvel\Http\Controllers;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Marvel\Exceptions\MarvelException;
use Marvel\Http\Requests\AttributeRequest;
use Illuminate\Database\Eloquent\Collection;
use Marvel\Database\Repositories\ContactRepository;
use Marvel\Http\Requests\ContactRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ContactController extends CoreController
{
    public $repository;

    public function __construct(ContactRepository $repository)
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
        //$language = $request->language ?? DEFAULT_LANGUAGE;
        $contacts = $this->repository->get();
        return $contacts;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AttributeRequest $request
     * @return mixed
     * @throws ValidatorException
     */
    public function store(ContactRequest $request)
    {

        try {

                return $this->repository->storeContact($request);
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show(Request $request, $params)
    {

        try {
            $language = $request->language ?? DEFAULT_LANGUAGE;
            if (is_numeric($params)) {
                $params = (int) $params;
                $contact = $this->repository->where('id', $params)->firstOrFail();
                return  $contact;
            }
            $contact = $this->repository->where('slug', $params)->where('language', $language)->firstOrFail();
            return $contact;
        } catch (MarvelException $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param AttributeRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(AttributeRequest $request, $id)
    {
        try {
            $request->id = $id;
            return $this->updateContact($request);
        } catch (MarvelException $e) {
            throw new MarvelException(COULD_NOT_DELETE_THE_RESOURCE);
        }
    }

    public function updateContact(ContactRequest $request)
    {


            try {
                $contact = $this->repository->with('values')->findOrFail($request->id);
            } catch (Exception $e) {
                throw new HttpException(404, NOT_FOUND);
            }
            return $this->repository->updateContact($request, $contact);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $request->id = $id;
            return $this->deleteContact($request);
        } catch (MarvelException $e) {
            throw new MarvelException(COULD_NOT_DELETE_THE_RESOURCE);
        }
    }

    public function deleteContact(Request $request)
    {
        try {
            $contact = $this->repository->findOrFail($request->id);
        } catch (Exception $e) {
            throw new HttpException(404, NOT_FOUND);
        }

        $contact->delete();
            return $contact;
    }

}
