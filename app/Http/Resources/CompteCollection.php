<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CompteCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => CompteResource::collection($this->collection),
            'pagination' => [
                'currentPage' => $this->currentPage(),
                'totalPages' => $this->lastPage(),
                'totalItems' => $this->total(),
                'itemsPerPage' => $this->perPage(),
                'hasNext' => $this->hasMorePages(),
                'hasPrevious' => $this->currentPage() > 1,
            ],
            'links' => [
                'self' => $this->url($this->currentPage()),
                'next' => $this->nextPageUrl(),
                'previous' => $this->previousPageUrl(),
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
            ],
        ];
    }
}
