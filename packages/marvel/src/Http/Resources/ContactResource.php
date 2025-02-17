<?php

namespace Marvel\Http\Resources;

use Illuminate\Http\Request;

class ContactResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'shop_id'              => $this->shop_id,
            'language'             => $this->language,
            'translated_languages' => $this->translated_languages,
            'slug'                 => $this->slug
        ];
    }
}
