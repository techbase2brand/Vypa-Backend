<?php

namespace Marvel\Database\Repositories;

use Marvel\Database\Models\Contact;
use Marvel\Database\Repositories\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ContactRepository  extends BaseRepository
{
    protected $fieldSearchable = [
        'name'        => 'like',
        'subject',
        'question',
    ];

    protected $dataArray = [
        'name',
        'slug',
        'shop_id',
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
        return Contact::class;
    }

    public function storeContact($request)
    {
        try {
            $request['slug'] = $this->makeSlug($request);
            $contact = $this->create($request->only($this->dataArray));
            return $contact;
        } catch (Throwable $th) {
            throw new HttpException(400, COULD_NOT_CREATE_THE_RESOURCE."_contact");
        }
    }

    public function updateContact($request, $contact)
    {
        try {
            return $contact->update($request->only($this->dataArray));
        } catch (Throwable $th) {
            throw new HttpException(400, COULD_NOT_UPDATE_THE_RESOURCE."_Contact-".$th);
        }
    }
}
