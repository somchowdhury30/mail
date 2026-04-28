<?php
// B:\Tools_And_Script\Python_project\mail_auto_gmail_inbox\php\index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aether | Global Inbox Feed</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Elite Styling -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="bg-glow bg-glow-1"></div>
    <div class="bg-glow bg-glow-2"></div>

    <div class="container">
        <header class="header">
            <div class="brand">
                <span class="brand-icon">⚡</span>
                <span class="brand-text">Aether <span class="highlight">Inbox Feed</span></span>
                <span class="badge badge-success" style="font-size: 0.6rem; margin-left: 10px;">v1.1 Elite</span>
            </div>
            <div class="status-indicator">
                <div class="pulse-dot"></div>
                <span id="last-updated">Live Sync Active</span>
            </div>
        </header>

        <main class="inbox-grid" id="inbox-container">
            <!-- Skeleton Loader -->
            <div class="skeleton-card">
                <div class="skeleton-header"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line" style="width: 60%;"></div>
            </div>
            <div class="skeleton-card">
                <div class="skeleton-header"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line" style="width: 60%;"></div>
            </div>
        </main>
    </div>

    <!-- Frontend Logic -->
    <script src="assets/js/app.js"></script>
</body>
</html>
