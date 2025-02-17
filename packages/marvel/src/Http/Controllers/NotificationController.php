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
use Marvel\Database\Repositories\NotificationRepository;
use Marvel\Http\Requests\NotificationRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NotificationController extends CoreController
{
    public $repository;

    public function __construct(NotificationRepository $repository)
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
    public function store(NotificationRequest $request)
    {
        try {
            return $this->repository->storeNotification($request);
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
                $notification = $this->repository->where('id', $params)->firstOrFail();
                return  $notification;
            }
            $notification = $this->repository->where('slug', $params)->where('language', $language)->firstOrFail();
            return $notification;
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
    public function update(NotificationRequest $request, $id)
    {
        try {
            $request->id = $id;
            return $this->updateNotification($request);
        } catch (MarvelException $e) {
            throw new MarvelException(COULD_NOT_DELETE_THE_RESOURCE);
        }
    }

    public function updateNotification(NotificationRequest $request)
    {


        try {
            $contact = $this->repository->findOrFail($request->id);
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
            return $this->deleteNotification($request);
        } catch (MarvelException $e) {
            throw new MarvelException(COULD_NOT_DELETE_THE_RESOURCE);
        }
    }

    public function deleteNotification(Request $request)
    {
        try {
            $notification = $this->repository->findOrFail($request->id);
        } catch (Exception $e) {
            throw new HttpException(404, NOT_FOUND);
        }

        $notification->delete();
            return $notification;

    }


}
