<?php

namespace Marvel\Http\Resources;

use Illuminate\Http\Request;

class CompanySettingResource
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
            'rear_logo'            => $this->rear_logo,
            'front_logo'           => $this->front_logo,
            'image'                => $this->image,
            'slug'                 => $this->slug
        ];
    }
}
