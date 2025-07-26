<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class AppResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'logo' => $this->logo,
            'banner' => $this->banner,
            'nama' => $this->nama,
            'title' => $this->title,
            'alamat' => $this->alamat,
            'desc' => $this->desc,
            'phone' => $this->phone,
            'email' => $this->email,
            'link_fb' => $this->link_fb,
            'link_map' => $this->link_map,
            'link_instagram' => $this->link_instagram,
            'link_youtube' => $this->link_youtube,
            'section_one'=>$this->section_one,
            'section_two'=>$this->section_two,
            'themes'=>$this->themes,
            'staf'=> $this->whenLoaded('staf'),
        ];
    }
}
