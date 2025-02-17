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

    public function exportAttributes(Request $request, $shop_id)
    {
        $filename = 'attributes-for-shop-id-' . $shop_id . '.csv';
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Expires'             => '0',
            'Pragma'              => 'public'
        ];

        $list = $this->repository->where('shop_id', $shop_id)->with(['values'])->get()->toArray();

        if (!count($list)) {
            return response()->stream(function () {
            }, 200, $headers);
        }
        # add headers for each column in the CSV download
        array_unshift($list, array_keys($list[0]));

        $callback = function () use ($list) {
            $FH = fopen('php://output', 'w');
            foreach ($list as $key => $row) {
                if ($key === 0) {
                    $exclude = ['id', 'created_at', 'updated_at', 'slug', 'translated_languages'];
                    $row = array_diff($row, $exclude);
                }
                unset($row['id']);
                unset($row['updated_at']);
                unset($row['slug']);
                unset($row['created_at']);
                unset($row['translated_languages']);
                if (isset($row['values'])) {
                    $row['values'] = implode(',', Arr::pluck($row['values'], 'value'));
                }

                fputcsv($FH, $row);
            }
            fclose($FH);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importAttributes(Request $request)
    {
        $requestFile = $request->file();
        $user = $request->user();
        $shop_id = $request->shop_id;

        if (count($requestFile)) {
            if (isset($requestFile['csv'])) {
                $uploadedCsv = $requestFile['csv'];
            } else {
                $uploadedCsv = current($requestFile);
            }
        }

        if (!$this->repository->hasPermission($user, $shop_id)) {
            throw new MarvelException(NOT_AUTHORIZED);
        }
        if (isset($shop_id)) {
            $file = $uploadedCsv->storePubliclyAs('csv-files', 'attributes-' . $shop_id . '.' . $uploadedCsv->getClientOriginalExtension(), 'public');

            $attributes = $this->repository->csvToArray(storage_path() . '/app/public/' . $file);

            foreach ($attributes as $key => $attribute) {
                if (!isset($attribute['name'])) {
                    throw new MarvelException("MARVEL_ERROR.WRONG_CSV");
                }
                unset($attribute['id']);
                $attribute['shop_id'] = $shop_id;
                $values = [];
                if (isset($attribute['values'])) {
                    $values = explode(',', $attribute['values']);
                }
                unset($attribute['values']);
                $newAttribute = $this->repository->firstOrCreate($attribute);
                foreach ($values as $key => $value) {
                    $newAttribute->values()->create(['value' => $value]);
                }
            }
            return true;
        }
    }
}
