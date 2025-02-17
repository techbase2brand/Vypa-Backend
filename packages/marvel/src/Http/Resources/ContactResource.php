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
            'subject'              => $this->subject,
            'question'             => $this->question,
            'slug'                 => $this->slug
        ];
    }
}
