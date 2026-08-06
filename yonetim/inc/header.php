<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dernek Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-trabzon {
            background-color: #610012; /* Bordo */
        }
        .navbar-trabzon .navbar-brand, 
        .navbar-trabzon .nav-link {
            color: #ffffff !important;
        }
        .navbar-trabzon .nav-link:hover {
            color: #004d66 !important; /* Mavi tonu */
            background-color: rgba(255,255,255,0.1);
            border-radius: 5px;
        }
        .navbar-trabzon .active {
            background-color: #004d66 !important;
            border-radius: 5px;
        }
        .card-stat {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .card-stat:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>