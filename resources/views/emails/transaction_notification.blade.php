<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification de Transaction</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
        }
        .transaction-details {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }
        .footer {
            background-color: #6c757d;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 5px 5px;
            font-size: 12px;
        }
        .amount {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔔 Notification de Transaction</h1>
        <p>Gestion Compte - Confirmation de transaction</p>
    </div>

    <div class="content">
        <p>Bonjour <strong>{{ $client->prenom }} {{ $client->nom }}</strong>,</p>

        <p>Une transaction a été effectuée sur votre compte bancaire :</p>

        <div class="transaction-details">
            <h3>Détails de la transaction</h3>
            <p><strong>Type :</strong> {{ $typeLabel }}</p>
            <p><strong>Montant :</strong> <span class="amount">{{ number_format($transaction->montant, 0, ',', ' ') }} FCFA</span></p>
            <p><strong>Numéro de compte :</strong> {{ $compte->numeroCompte }}</p>
            <p><strong>Date :</strong> {{ $transaction->dateTransaction->format('d/m/Y à H:i') }}</p>
            <p><strong>Description :</strong> {{ $transaction->description }}</p>
            <p><strong>Nouveau solde :</strong> {{ number_format($compte->solde, 0, ',', ' ') }} FCFA</p>
        </div>

        <p>Si vous n'êtes pas à l'origine de cette transaction, veuillez contacter immédiatement notre service client.</p>

        <p>Cordialement,<br>
        <strong>L'équipe Gestion Compte</strong></p>
    </div>

    <div class="footer">
        <p>Cette notification a été générée automatiquement. Merci de ne pas y répondre.</p>
        <p>&copy; 2025 Gestion Compte - Tous droits réservés</p>
    </div>
</body>
</html>