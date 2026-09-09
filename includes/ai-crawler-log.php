<?php
/**
 * Server-side logging of AI/LLM crawler visits.
 *
 * Why this exists separately from the normal visitor tracking: that runs as
 * a fetch() from JavaScript (includes/partials/public-track.php), and
 * crawlers do not execute JavaScript — so ep_visitor_hits recorded 4,016
 * visits and exactly zero AI-bot visits. Not because the bots never came,
 * but because they could never be seen. This runs server-side during page
 * render, so it counts them.
 *
 * Only the four agents named in the robots.txt allow-list are recorded,
 * plus the closely-related OAI-SearchBot / ChatGPT-User, so the table stays
 * a measurement of AI reach rather than a general bot log.
 */

/** UA substring => canonical bot name. Matched case-insensitively, first hit wins. */
const EP_AI_CRAWLERS = [
    'GPTBot'          => 'GPTBot',            // OpenAI training crawler
    'OAI-SearchBot'   => 'OAI-SearchBot',     // OpenAI search index
    'ChatGPT-User'    => 'ChatGPT-User',      // ChatGPT browsing on a user's behalf
    'ClaudeBot'       => 'ClaudeBot',         // Anthropic
    'Claude-Web'      => 'Claude-Web',
    'anthropic-ai'    => 'ClaudeBot',
    'PerplexityBot'   => 'PerplexityBot',
    'Perplexity-User' => 'PerplexityBot',
    'Google-Extended' => 'Google-Extended',   // Google's AI (Gemini/Vertex) opt-in agent
];

/**
 * Returns the canonical bot name for a user-agent string, or null when it
 * is not one of the AI crawlers being measured.
 */
function ep_identify_ai_crawler(?string $userAgent): ?string
{
    $ua = trim((string) $userAgent);
    if ($ua === '') {
        return null;
    }
    foreach (EP_AI_CRAWLERS as $needle => $name) {
        if (stripos($ua, $needle) !== false) {
            return $name;
        }
    }
    return null;
}

/**
 * Records the current request if it came from an AI crawler.
 *
 * Deliberately fail-soft: a logging problem must never surface on a public
 * page or break the response, so every database error is swallowed and
 * written to the error log instead. Returns the bot name if logged.
 */
function ep_log_ai_crawler(): ?string
{
    $bot = ep_identify_ai_crawler($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($bot === null) {
        return null;
    }

    try {
        global $m;
        if (!isset($m) || !($m instanceof MysqliDb)) {
            return null;
        }
        $path = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $m->insert('ep_ai_crawler_hits', [
            'bot' => $bot,
            'page_path' => mb_substr($path, 0, 255),
            'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'ip_address' => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            'hit_date' => date('Y-m-d'),
        ]);
    } catch (Throwable $e) {
        error_log('[EduPortal] AI crawler logging failed: ' . $e->getMessage());
        return null;
    }
    return $bot;
}

/**
 * Per-bot totals for the last N days — used by the admin dashboard so the
 * effect of the llms.txt / robots.txt work is measurable rather than assumed.
 *
 * @return array<int, array{bot:string, hits:int, pages:int, last_seen:string}>
 */
function ep_ai_crawler_summary(int $days = 30): array
{
    global $m;
    try {
        return $m->rawQuery(
            'SELECT bot,
                    COUNT(*)                  AS hits,
                    COUNT(DISTINCT page_path) AS pages,
                    MAX(created_at)           AS last_seen
             FROM ep_ai_crawler_hits
             WHERE hit_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY bot
             ORDER BY hits DESC',
            [$days]
        ) ?: [];
    } catch (Throwable $e) {
        error_log('[EduPortal] AI crawler summary failed: ' . $e->getMessage());
        return [];
    }
}
