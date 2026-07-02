<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médiathèque</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f8; margin: 0; color: #222; }
        header { background: #2c3e66; color: white; padding: 16px 30px; }
        header h1 { margin: 0; font-size: 22px; }
        nav { margin-top: 8px; }
        nav a { color: #c5d4f0; text-decoration: none; margin-right: 20px; font-size: 14px; }
        nav a:hover { text-decoration: underline; }
        main { max-width: 900px; margin: 30px auto; background: white;
               padding: 25px 30px; border-radius: 8px;
               box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        h2 { color: #2c3e66; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #e0e0e0;
                 font-size: 14px; text-align: left; }
        th { background: #eef1f8; color: #2c3e66; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 12px;
                 font-size: 12px; font-weight: bold; color: white; }
        .badge-livre { background: #2c6e9e; }
        .badge-dvd   { background: #8e3a2e; }
        .dispo-oui { color: #2e7d4f; font-weight: bold; }
        .dispo-non { color: #b85042; font-weight: bold; }
        .btn { display: inline-block; background: #2c3e66; color: white;
               padding: 7px 14px; border: none; border-radius: 5px;
               cursor: pointer; font-size: 13px; text-decoration: none; margin-right: 4px; }
        .btn:hover          { background: #3a4f8c; }
        .btn-danger         { background: #b85042; }
        .btn-danger:hover   { background: #922e22; }
        .btn-success        { background: #2e7d4f; }
        .btn-success:hover  { background: #245f3c; }
        .btn-warning        { background: #b07d1a; }
        .btn-warning:hover  { background: #8a6114; }
        .alerte { padding: 10px 14px; border-radius: 5px;
                  margin-bottom: 15px; font-size: 14px; }
        .alerte-succes { background: #e4f6e9; color: #1d7a3a; }
        .alerte-erreur { background: #fde8e7; color: #a32f2f; }
        form label { display: block; font-size: 13px; font-weight: bold;
                     color: #444; margin-top: 14px; }
        form input, form select { width: 100%; padding: 9px; margin-top: 4px;
                                  border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        form .btn { margin-top: 20px; }
        .lien-retour { display: inline-block; margin-top: 16px;
                       color: #1c7293; font-size: 14px; text-decoration: none; }
        .lien-retour:hover { text-decoration: underline; }
    </style>
</head>
<body>
<header>
    <h1>📚 Médiathèque</h1>
    <nav>
        <!-- Tous les liens pointent vers index.php avec un paramètre action -->
        <a href="index.php?action=liste">Liste des articles</a>
        <a href="index.php?action=formulaire">Ajouter un article</a>
    </nav>
</header>
<main>