<?php
require_once 'config.php';

$result = $conn->query("SELECT * FROM poems ORDER BY sort_order ASC, created_at DESC");
$poems = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $poems[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soul Whispers - Poetry by Farid Tabrizy</title>
    <meta name="description" content="Poetry that breathes. Explore the spiritual and poetic works of Farid Tabrizy.">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="64x64" href="assets/favicon-64.png">
    <link rel="icon" type="image/png" sizes="128x128" href="assets/favicon-128.png">
    <link rel="apple-touch-icon" href="assets/favicon-128.png">

    <!-- Open Graph Meta Tags for Social Media -->
    <meta property="og:title" content="Soul Whispers">
    <meta property="og:description" content="Poetry that breathes. Experience the spiritual and poetic works of Farid Tabrizy.">
    <meta property="og:image" content="https://soulwhispers.peoplestar.com/assets/soul_whispers_og.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/png">
    <meta property="og:url" content="https://soulwhispers.peoplestar.com">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Soul Whispers">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Soul Whispers">
    <meta name="twitter:description" content="Poetry that breathes. Explore Farid Tabrizy's spiritual poetry.">
    <meta name="twitter:image" content="https://soulwhispers.peoplestar.com/assets/soul_whispers_og.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Star field background -->
    <div class="stars"></div>

    <!-- Glowing moon/orb with orbiting stars -->
    <div class="moon">
        <div class="moon-glow"></div>
        <div class="moon-core"></div>
        <div class="orbital-ring">
            <div class="orbiting-star star-1"></div>
            <div class="orbiting-star star-2"></div>
            <div class="orbiting-star star-3"></div>
            <div class="orbiting-star star-4"></div>
            <div class="orbiting-star star-5"></div>
        </div>
    </div>

    <!-- Hero Section -->
    <header class="hero">
        <div class="hero-glow"></div>
        <div class="hero-content">
            <h1 class="hero-title">Soul Whispers</h1>
            <p class="hero-subtitle">Farid Tabrizy</p>
            <p class="hero-tagline">Poetry that breathes</p>
            <a href="#poems-section" class="scroll-link">Explore Poems ↓</a>
        </div>
    </header>

    <!-- Poems Grid -->
    <main class="container">
        <section class="poems-grid" id="poems-section">
            <?php foreach ($poems as $poem): ?>
            <article class="poem-card"
                     data-poem-id="<?php echo $poem['id']; ?>"
                     data-title="<?php echo htmlspecialchars($poem['title']); ?>"
                     data-subtitle="<?php echo htmlspecialchars($poem['subtitle'] ?? ''); ?>"
                     data-audio="<?php echo htmlspecialchars($poem['audio_filename']); ?>"
                     data-lyrics="<?php echo htmlspecialchars(json_encode($poem['lyrics'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="card-image">
                    <?php if ($poem['cover_image']): ?>
                        <img src="uploads/covers/<?php echo htmlspecialchars($poem['cover_image']); ?>" alt="<?php echo htmlspecialchars($poem['title']); ?>">
                    <?php else: ?>
                        <div class="placeholder-image">♪</div>
                    <?php endif; ?>
                </div>
                <div class="card-content">
                    <h2 class="card-title"><?php echo htmlspecialchars($poem['title']); ?></h2>
                    <?php if ($poem['subtitle']): ?>
                        <p class="card-subtitle"><?php echo htmlspecialchars($poem['subtitle']); ?></p>
                    <?php endif; ?>
                </div>
                <a href="poem.php?id=<?php echo $poem['id']; ?>" class="play-button">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="5 3 19 12 5 21"></polygon>
                    </svg>
                </a>
            </article>
            <?php endforeach; ?>
        </section>
    </main>


    <!-- Footer -->
    <footer class="footer">
        <div class="footer-decoration"></div>
        <a href="admin/index.php" class="admin-link" title="Admin Dashboard">⚙</a>
    </footer>

</body>
</html>
