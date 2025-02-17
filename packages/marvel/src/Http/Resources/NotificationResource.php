<?php

namespace Marvel\Http\Resources;

use Illuminate\Http\Request;

class NotificationResource
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
            'selectedfor'          => $this->selectedfor,
            'notification'         => $this->notification,
            'slug'                 => $this->slug
        ];
    }
}
