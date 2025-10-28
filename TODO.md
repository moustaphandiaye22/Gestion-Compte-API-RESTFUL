# TODO: Implement Manual Account Archiving Endpoint

## Tasks
- [x] Add new route POST /ndiaye/v1/comptes/{compte}/archiver in routes/api.php
- [x] Add archiver method in CompteController with OA annotations
- [x] Implement logic to archive account (set status to 'Supprime') and transactions (set status to 'Archivee')
- [x] Test the endpoint in Swagger (regenerated docs)
