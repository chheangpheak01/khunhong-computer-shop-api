<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       return [
            'id' => $this->id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'brand' => $this->brand,
            'slug' => $this->slug,
            'price' => (float) $this->price,
            'stock' => (int) $this->stock,
            'description' => $this->description,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'is_active' => $this->deleted_at ? false : (bool) $this->status,
            'dates' => [
                'created' => $this->created_at?->toISOString(),
                'updated' => $this->updated_at?->toISOString(),
            ],
        ];
    }
}
