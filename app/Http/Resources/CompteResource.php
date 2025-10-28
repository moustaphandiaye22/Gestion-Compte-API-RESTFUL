<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
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
             'numeroCompte' => $this->numeroCompte,
             'titulaire' => $this->titulaire,
             'type' => $this->type,
             'solde' => $this->solde,
             'devise' => $this->devise,
             'dateCreation' => $this->dateCreation?->toISOString(),
             'statut' => $this->statut,
             'motifBlocage' => $this->motifBlocage,
             'dateBlocage' => $this->date_debut_blocage?->toISOString(),
             'dateDeblocagePrevue' => $this->date_fin_blocage?->toISOString(),
             'dateFermeture' => $this->dateFermeture?->toISOString(),
             'client' => $this->whenLoaded('client', function () {
                 return [
                     'id' => $this->client->id,
                     'titulaire' => $this->client->titulaire,
                     'nci' => $this->client->cni,
                     'email' => $this->client->email,
                     'telephone' => $this->client->telephone,
                     'adresse' => $this->client->adresse,
                 ];
             }),
             'nombreTransactions' => $this->transactions()->count(),
             'metadata' => [
                 'derniereModification' => $this->updated_at?->toISOString(),
                 'version' => 1,
             ],
         ];
    }
}
