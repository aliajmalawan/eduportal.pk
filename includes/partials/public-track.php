<?php
$epTrackPage = $epTrackPage ?? basename($_SERVER['SCRIPT_NAME'] ?? 'unknown');

// Server-side AI-crawler logging. The fetch() below cannot see GPTBot,
// ClaudeBot and friends because crawlers do not run JavaScript, so this
// records them during render instead. Fail-soft: never breaks the page.
require_once __DIR__ . '/../ai-crawler-log.php';
ep_log_ai_crawler();
?>
<script>
  (function () {
    try {
      fetch('api/track-visit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ page: <?= json_encode($epTrackPage) ?> })
      });
    } catch (e) {}
  })();
</script>
