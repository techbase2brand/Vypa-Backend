<?php


namespace Marvel\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Marvel\Database\Repositories\RefundReasonRepository;
use Marvel\Exceptions\MarvelException;
use Marvel\Http\Requests\RefundReasonCreateRequest;
use Marvel\Http\Requests\RefundReasonUpdateRequest;
use Prettus\Validator\Exceptions\ValidatorException;


class RefundReasonController extends CoreController
{
    public $repository;

    public function __construct(RefundReasonRepository $repository)
    {
        $this->repository = $repository;
    }
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Collection|Tag[]
     */
    public function index(Request $request)
    {
        $limit = $request->limit ?   $request->limit : 15;
        return $this->fetchRefundReasons($request)->paginate($limit);
    }

    public function fetchRefundReasons(Request $request)
    {
        $language = $request->language ?? DEFAULT_LANGUAGE;
        return $this->repository->where('language', $language);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param RefundReasonCreateRequest $request
     * @return mixed
     * @throws ValidatorException
     */
    public function store(RefundReasonCreateRequest $request)
    {
        try {
                return $this->repository->storeRefundReason($request);

            throw new AuthorizationException(NOT_AUTHORIZED);
        } catch (MarvelException $e) {
            throw new MarvelException(COULD_NOT_CREATE_THE_RESOURCE);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, $params)
    {

        try {
            $language = $request->language ?? DEFAULT_LANGUAGE;
            if (is_numeric($params)) {
                $params = (int) $params;
                return $this->repository->where('id', $params)->firstOrFail();
            }
            return $this->repository->where('slug', $params)->firstOrFail();
        } catch (MarvelException $th) {
            throw new MarvelException(COULD_NOT_CREATE_THE_RESOURCE);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param RefundReasonUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(RefundReasonUpdateRequest $request, $id)
    {
        try {
            $request['id'] = $id;
            return $this->refundReasonUpdate($request);
        } catch (MarvelException $th) {
            throw new MarvelException(COULD_NOT_CREATE_THE_RESOURCE);
        }
    }

    public function refundReasonUpdate(Request $request)
    {
        try {
            $item = $this->repository->findOrFail($request->id);
            return $this->repository->updateRefundReason($request, $item);
        } catch (MarvelException $th) {
            throw new MarvelException(COULD_NOT_CREATE_THE_RESOURCE);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $request->merge(['id' => $id]);
        return $this->deleteRefundReason($request);
    }

    public function deleteRefundReason(Request $request)
    {
        try {
            $refundReason = $this->repository->findOrFail($request->id);
            $refundReason->delete();
            return $refundReason;
        } catch (MarvelException $th) {
            throw new MarvelException(COULD_NOT_CREATE_THE_RESOURCE);
        }
    }
    public function approve(Request $request)
    {

        try {

            $id = $request->id;

                $req = $this->repository->findOrFail($id);

            $req->is_active = true;

            $req->save();


            //Mail::to($shop->owner->email)->send(new CompanyApproved($shop->toArray()));
            return $req;
        } catch (MarvelException $th) {
            throw new MarvelException(SOMETHING_WENT_WRONG."221");
        }
    }

    public function disApprove(Request $request)
    {
        try {

            $id = $request->id;

            $req = $this->repository->findOrFail($id);


            $req->is_active = false;

            $req->save();

            //Mail::to($shop->owner->email)->send(new CompanyDisapproved($shop->toArray()));
            // Product::where('shop_id', '=', $id)->update(['status' => 'draft']);

            return $req;
        } catch (MarvelException $th) {
            throw new MarvelException(SOMETHING_WENT_WRONG."245");
        }
    }
}
