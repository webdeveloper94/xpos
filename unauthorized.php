<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruxsat yo'q - Fast Food</title>
    <link rel="stylesheet" href="/xpos/assets/css/style.css">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-lg);
        }
        
        .error-content {
            text-align: center;
            max-width: 500px;
        }
        
        .error-icon {
            font-size: 6rem;
            margin-bottom: var(--spacing-lg);
        }
        
        .error-title {
            font-size: 2rem;
            color: var(--danger);
            margin-bottom: var(--spacing-md);
        }
        
        .error-text {
            color: var(--gray-600);
            margin-bottom: var(--spacing-xl);
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-content">
            <div class="error-icon">🚫</div>
            <h1 class="error-title">Ruxsat yo'q</h1>
            <p class="error-text">Sizda bu sahifaga kirish huquqi yo'q.</p>
            <a href="/xpos/login.php" class="btn btn-primary">Login sahifasiga qaytish</a>
        </div>
    </div>
</body>
</html>
