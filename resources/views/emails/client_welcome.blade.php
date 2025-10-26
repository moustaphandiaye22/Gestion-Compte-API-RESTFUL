<!DOCTYPE html>
<html>
<head>
    <title>Bienvenue à la Banque</title>
</head>
<body>
    <h1>Bienvenue, {{ $client->titulaire }}!</h1>
    <p>Votre compte a été créé avec succès.</p>
    <p>Vos identifiants de connexion :</p>
    <ul>
        <li>Email: {{ $client->email }}</li>
        <li>Mot de passe: {{ $password }}</li>
        <li>Code SMS: {{ $client->user->code }}</li>
    </ul>
    <p>Merci d'utiliser nos services.</p>
</body>
</html>