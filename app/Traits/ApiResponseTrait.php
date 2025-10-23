<?php

namespace App\Traits;

trait ApiResponseTrait
{
    /**
     * Format de réponse API standard pour succès
     */
    protected function successResponse($data = null, $message = 'Opération réussie', $statusCode = 200, $pagination = null)
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($pagination !== null) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Format de réponse API standard pour erreur
     */
    protected function errorResponse($message = 'Une erreur est survenue', $statusCode = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Format de réponse avec pagination
     */
    protected function paginatedResponse($resource, $message = 'Données récupérées avec succès')
    {
        return $this->successResponse(
            $resource->response()->getData(true)['data'],
            $message,
            200,
            [
                'currentPage' => $resource->currentPage(),
                'totalPages' => $resource->lastPage(),
                'totalItems' => $resource->total(),
                'itemsPerPage' => $resource->perPage(),
                'hasNext' => $resource->hasMorePages(),
                'hasPrevious' => $resource->currentPage() > 1,
            ]
        );
    }
}
