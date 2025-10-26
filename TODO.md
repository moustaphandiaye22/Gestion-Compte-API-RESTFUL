# TODO: Fix POST /api/v1/comptes Endpoint Bugs

## Issues to Fix:
1. Validation requires client.id to exist if provided, but logic doesn't use it correctly.
2. Validation has unique constraints on email/telephone, preventing finding existing clients.
3. NCI validation requires starting with 19/20, but example uses 123....

## Steps:
- [x] Modify StoreCompteRequest to remove 'exists' from client.id and 'unique' from email/telephone.
- [x] Update SenegalesePhoneAndNci rule to accept any 13 digits for NCI.
- [x] Update CompteController to handle client.id properly in findOrCreateClient and createClient.
