<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'parent'     => $this->whenLoaded('parent', fn() => new CategoryResource($this->parent)),
            'children'   => $this->whenLoaded('children', fn() => CategoryResource::collection($this->children)),
            'childrenRecursive' => $this->whenLoaded('childrenRecursive', fn() => CategoryResource::collection($this->childrenRecursive)),
        ];
    }
}
