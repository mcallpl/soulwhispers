<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$lyrics = $input['lyrics'] ?? '';

if (empty($lyrics)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No lyrics provided']);
    exit;
}

try {
    // Load Gemini API key from vault or environment
    $geminiApiKey = null;
    $geminiModel = 'gemini-2.5-flash-lite';

    // Try vault file first
    $vaultPaths = [
        '/Users/chipmcallister/vault/secrets.php',
        '/var/www/.vault/secrets.php',
        dirname(__DIR__) . '/../.vault/secrets.php'
    ];

    foreach ($vaultPaths as $vaultPath) {
        if (file_exists($vaultPath)) {
            include $vaultPath;
            break;
        }
    }

    // Get from included variables or environment
    $geminiApiKey = $vault_gemini_api_key ?? $_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? null;
    $geminiModel = $vault_gemini_model ?? $_ENV['GEMINI_MODEL'] ?? $_SERVER['GEMINI_MODEL'] ?? 'gemini-2.5-flash-lite';

    if (!$geminiApiKey) {
        throw new Exception('Gemini API key not configured. Set GEMINI_API_KEY environment variable or include vault/secrets.php');
    }

    // Prepare prompt for Gemini
    $prompt = <<<PROMPT
You are a creative poet and song title expert. Based on the following song lyrics, generate a single, evocative title for this poem/song.

The title should be:
- Poetic and meaningful (2-6 words)
- Capture the essence of the lyrics
- Be memorable and elegant
- Not just a quote from the lyrics, but inspired by them

LYRICS:
{$lyrics}

Respond with ONLY the title, nothing else. No quotes, no explanation, just the title text.
PROMPT;

    // Call Gemini API
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($geminiModel) . ':generateContent?key=' . urlencode($geminiApiKey);

    $requestBody = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 100,
            'stopSequences' => []
        ],
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE']
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestBody),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Gemini API error: HTTP ' . $httpCode);
    }

    $result = json_decode($response, true);

    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception('Invalid Gemini response format');
    }

    $title = trim($result['candidates'][0]['content']['parts'][0]['text']);
    // Remove quotes if present
    $title = trim($title, '"\'');

    if (empty($title)) {
        throw new Exception('Generated empty title');
    }

    echo json_encode([
        'success' => true,
        'title' => $title
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Title generation failed: ' . $e->getMessage()
    ]);
    exit;
}
?>
