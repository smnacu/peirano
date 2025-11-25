<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Peirano Logística'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@300;400;500&family=Red+Hat+Display:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #E30613; /* Peirano Red */
            --bg-main: #ffffff;
            --card-bg: #ffffff;
            --text-main: #333333;
            --border-color: #e5e7eb;
        }

        body {
            font-family: 'Libre Franklin', sans-serif;
            background-color: #f3f4f6; /* Light grey background for contrast */
            color: var(--text-main);
        }

        h1, h2, h3, h4, h5, h6, .navbar-brand {
            font-family: 'Red Hat Display', sans-serif;
            color: #000000;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .table-dark {
            --bs-table-bg: #1e293b; /* Keep tables dark for contrast or switch? Let's keep standard bootstrap table */
            background-color: white;
            color: var(--text-main);
        }
        
        /* Override Bootstrap Table for Light Theme */
        .table {
            --bs-table-color: var(--text-main);
            --bs-table-bg: transparent;
            --bs-table-border-color: var(--border-color);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(227, 6, 19, 0.05);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background-color: #b90510;
            border-color: #b90510;
        }
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .form-control, .form-select {
            background-color: #ffffff;
            border-color: #d1d5db;
            color: var(--text-main);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(227, 6, 19, 0.25);
        }
    </style>
</head>

<body>