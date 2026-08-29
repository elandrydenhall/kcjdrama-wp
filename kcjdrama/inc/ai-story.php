<?php
/**
 * Grok precheck for Soft desk stories. Server-side only. Fail closed.
 */
if (!defined('ABSPATH')) {
    exit;
}

function kcj_xai_api_key() {
    if (defined('XAI_API_KEY') && is_string(XAI_API_KEY) && XAI_API_KEY !== '') {
        return XAI_API_KEY;
    }
    $env = getenv('XAI_API_KEY');
    if (is_string($env) && $env !== '') {
        return $env;
    }
    static $file_key = null;
    if ($file_key !== null) {
        return $file_key;
    }
    $file_key = '';
    $candidates = [];
    if (defined('ABSPATH')) {
        $candidates[] = dirname(untrailingslashit(ABSPATH)) . DIRECTORY_SEPARATOR . '.env';
    }
    if (defined('KCJ_PATH')) {
        $candidates[] = dirname(KCJ_PATH) . DIRECTORY_SEPARATOR . '.env';
    }
    $path = '';
    foreach ($candidates as $try) {
        if (is_readable($try)) {
            $path = $try;
            break;
        }
    }
    if ($path === '') {
        return $file_key;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) {
        return $file_key;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        if (trim($k) !== 'XAI_API_KEY') {
            continue;
        }
        $file_key = trim($v, " \t\"'");
        break;
    }
    return $file_key;
}

function kcj_story_policy_prompt() {
    return <<<'PROMPT'
You are the precheck for kcjdrama Soft desk original fiction.
Return JSON only: {"pass": true|false, "reasons": ["short reason", ...]}

PASS only if ALL are true:
- Original pattern-fiction (romance-drama tropes as craft), written as a short story or scene in prose — not a shooting script.
- No episode-by-episode plot, no long quoted dialogue from existing IP.
- FAIL shooting-script format: EPISODE/SCENE numbers, EXT./INT. sluglines, CHARACTER: blocks, (CONT'D), camera directions, OST swell, "over N episodes." That is a script dump, not a Soft short.
- No stills, piracy, or scraped character art (text describing those as attachments also fails).
- Does not punch down at real people (actors' bodies, private lives).
- Not empty, not spam, on-brief as Soft fiction.
- Sexual content that MAY pass: kissing, massage, holding, fade-to-black (a door closes, the lights go out). Rain, neon, and umbrellas do not excuse a sex act.
- FAIL oral sex and coitus, including a single small line, euphemism, or innuendo. Not only graphic porn. FAIL examples: "her head bobbed up and down", "went down on", mouth on genitals, thrusting/penetration on the page. Soft heat is not a sex act written out.
- FAIL crude genital slang even when no sex act is shown — including typos and "joke" compounds. FAIL examples: "pussy", "pussy dorms", "cock", "dick", "cunt", "clit" used as body/insult slang. Soft may imply desire; it does not name genitals in porn vocabulary.

If pass is true, reasons may be empty. Do not use the word "clear" as a reason.
If pass is false, reasons must name the policy miss in plain English (one line each).
PROMPT;
}

/**
 * Local veto so crude heat / sex-act lines cannot pass even if Grok is generous.
 *
 * @return string Hold reason, or empty if the local screen does not fire.
 */
function kcj_ai_sex_act_hold($plain) {
    $text = strtolower(preg_replace('/\s+/u', ' ', (string) $plain));
    if ($text === '') {
        return '';
    }

    // Genital slang / porn vocabulary — Soft fade-to-black never needs these words.
    $slang = [
        '/\bpuss(?:y|ies)\b/u',
        '/\bcunt\b/u',
        '/\bclit(?:oris)?\b/u',
        '/\bcock\b/u',
        '/\bdick\b/u',
        '/\bpenis\b/u',
        '/\bvagina\b/u',
        '/\bballs?\b.{0,12}\bdeep\b/u',
    ];
    foreach ($slang as $re) {
        if (preg_match($re, $text)) {
            return 'Crude genital slang is on the page (even as a joke or typo). Soft can imply desire; rewrite without porn vocabulary.';
        }
    }

    $patterns = [
        '/\bheads?\s+bobb(?:ed|ing)\b.{0,32}\bup\s+and\s+down\b/u',
        '/\bbobb(?:ed|ing)\s+.{0,20}\bheads?\b.{0,32}\bup\s+and\s+down\b/u',
        '/\bwent\s+down\s+on\b/u',
        '/\bgoing\s+down\s+on\b/u',
        '/\bgave?\s+(?:him|her|them)\s+head\b/u',
        '/\b(?:blow\s*job|blowjob|fellatio|cunnilingus)\b/u',
        '/\bsuck(?:ed|ing|s)\s+(?:his|her|their|the)\s+(?:cock|dick|penis|clit)/u',
        '/\b(?:cock|dick|penis)\b.{0,48}\b(?:mouth|throat|lips)\b/u',
        '/\b(?:mouth|throat|lips)\b.{0,48}\b(?:cock|dick|penis)\b/u',
        '/\bthrust(?:ed|ing)?\s+(?:into|inside|in)\b/u',
    ];
    foreach ($patterns as $re) {
        if (preg_match($re, $text)) {
            return 'Oral sex or coitus is on the page, even as a small line. Kissing and massage can stay; this has to come out.';
        }
    }
    return '';
}

/**
 * @return array{ok:bool,pass:bool,reasons:list<string>,error:string}
 */
function kcj_ai_review_story($title, $body) {
    $title = wp_strip_all_tags((string) $title);
    $plain = trim(wp_strip_all_tags((string) $body));
    if ($title === '' || $plain === '') {
        return [
            'ok'      => true,
            'pass'    => false,
            'reasons' => ['The piece needs a title and a story.'],
            'error'   => '',
        ];
    }
    $sex_hold = kcj_ai_sex_act_hold($plain);
    if ($sex_hold !== '') {
        return [
            'ok'      => true,
            'pass'    => false,
            'reasons' => [$sex_hold],
            'error'   => '',
        ];
    }
    $key = kcj_xai_api_key();
    if ($key === '') {
        return [
            'ok'      => false,
            'pass'    => false,
            'reasons' => ['Grok is not configured, so a human has to read this.'],
            'error'   => 'missing_key',
        ];
    }
    $excerpt = $plain;
    if (function_exists('mb_substr')) {
        $excerpt = mb_substr($plain, 0, 12000);
    } elseif (strlen($plain) > 12000) {
        $excerpt = substr($plain, 0, 12000);
    }
    $payload = [
        'model'           => 'grok-4.5',
        'temperature'     => 0,
        'response_format' => ['type' => 'json_object'],
        'messages'        => [
            ['role' => 'system', 'content' => kcj_story_policy_prompt()],
            [
                'role'    => 'user',
                'content' => "Title: {$title}\n\nStory:\n{$excerpt}",
            ],
        ],
    ];
    $res = wp_remote_post('https://api.x.ai/v1/chat/completions', [
        'timeout' => 90,
        'headers' => [
            'Authorization' => 'Bearer ' . $key,
            'Content-Type'  => 'application/json',
        ],
        'body'    => wp_json_encode($payload),
    ]);
    if (is_wp_error($res)) {
        return [
            'ok'      => false,
            'pass'    => false,
            'reasons' => ['Grok could not be reached. Held for a human.'],
            'error'   => 'http',
        ];
    }
    $code = (int) wp_remote_retrieve_response_code($res);
    $raw = (string) wp_remote_retrieve_body($res);
    if ($code < 200 || $code >= 300) {
        return [
            'ok'      => false,
            'pass'    => false,
            'reasons' => ['Grok returned an error. Held for a human.'],
            'error'   => 'status',
        ];
    }
    $json = json_decode($raw, true);
    $text = '';
    if (is_array($json)) {
        $text = (string) ($json['choices'][0]['message']['content'] ?? '');
    }
    $parsed = json_decode($text, true);
    if (!is_array($parsed) && is_string($text) && preg_match('/\{.*\}/s', $text, $m)) {
        $parsed = json_decode($m[0], true);
    }
    if (!is_array($parsed) || !array_key_exists('pass', $parsed)) {
        return [
            'ok'      => false,
            'pass'    => false,
            'reasons' => ['Grok did not return a clear pass/fail. Held for a human.'],
            'error'   => 'parse',
        ];
    }
    $pass = !empty($parsed['pass']);
    $reasons = [];
    if (!empty($parsed['reasons']) && is_array($parsed['reasons'])) {
        foreach ($parsed['reasons'] as $r) {
            $r = trim(wp_strip_all_tags((string) $r));
            if ($r !== '') {
                $reasons[] = $r;
            }
        }
    }
    if (!$pass && !$reasons) {
        $reasons[] = 'Did not pass the Soft desk policy.';
    }
    if ($pass) {
        $sex_hold = kcj_ai_sex_act_hold($plain);
        if ($sex_hold !== '') {
            $pass = false;
            array_unshift($reasons, $sex_hold);
        }
    }
    return [
        'ok'      => true,
        'pass'    => $pass,
        'reasons' => $reasons,
        'error'   => '',
    ];
}

function kcj_ai_store_review($post_id, array $review) {
    $post_id = (int) $post_id;
    update_post_meta($post_id, '_kcj_ai_pass', !empty($review['pass']) ? '1' : '0');
    update_post_meta($post_id, '_kcj_ai_notes', $review['reasons']);
    update_post_meta($post_id, '_kcj_ai_at', time());
    $uid = get_current_user_id();
    if ($uid) {
        set_transient('kcj_ai_notes_' . $uid, $review, 15 * MINUTE_IN_SECONDS);
    }
}

function kcj_ai_last_notes_for_user() {
    $uid = get_current_user_id();
    if (!$uid) {
        return null;
    }
    $notes = get_transient('kcj_ai_notes_' . $uid);
    return is_array($notes) ? $notes : null;
}

function kcj_ai_clear_notes_for_user() {
    $uid = get_current_user_id();
    if (!$uid) {
        return;
    }
    delete_transient('kcj_ai_notes_' . $uid);
}

add_action('rest_api_init', function () {
    register_rest_route('kcj/v1', '/story-check', [
        'methods'             => 'POST',
        'permission_callback' => function () {
            return is_user_logged_in() && kcj_is_verified_contributor();
        },
        'callback'            => 'kcj_rest_story_check',
    ]);
});

function kcj_rest_story_check(WP_REST_Request $request) {
    if (!kcj_desk_rate_ok('ai-check')) {
        return new WP_Error('kcj_rate', 'Give it a few seconds, then check again.', ['status' => 429]);
    }
    $params = $request->get_json_params();
    if (!is_array($params)) {
        $params = $request->get_params();
    }
    $title = sanitize_text_field((string) ($params['title'] ?? ''));
    $body = wp_kses_post((string) ($params['body'] ?? ''));
    $review = kcj_ai_review_story($title, $body);
    $uid = get_current_user_id();
    if ($uid) {
        set_transient('kcj_ai_notes_' . $uid, $review, 15 * MINUTE_IN_SECONDS);
    }
    return rest_ensure_response([
        'ok'      => !empty($review['ok']),
        'pass'    => !empty($review['pass']),
        'reasons' => $review['reasons'],
        'error'   => (string) ($review['error'] ?? ''),
    ]);
}

add_filter('manage_kcj_story_posts_columns', function ($cols) {
    $cols['kcj_ai'] = 'Grok';
    return $cols;
});

add_action('manage_kcj_story_posts_custom_column', function ($col, $post_id) {
    if ($col !== 'kcj_ai') {
        return;
    }
    $pass = (string) get_post_meta($post_id, '_kcj_ai_pass', true);
    if ($pass === '1') {
        echo 'Preapproved';
        return;
    }
    if ($pass === '0') {
        echo 'Held';
        return;
    }
    echo '—';
}, 10, 2);
