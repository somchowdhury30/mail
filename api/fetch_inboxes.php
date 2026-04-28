<?php
// B:\Tools_And_Script\Python_project\mail_auto_gmail_inbox\php\api\fetch_inboxes.php

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Configuration
$CACHE_FILE = __DIR__ . '/../cache/inbox_data.json';
$CACHE_TTL = 30; // 30 seconds caching to prevent IMAP rate limit blocks
$FETCH_LIMIT = 10; // Number of emails to fetch per account

// Utility to decode IMAP MIME headers
function decode_imap_text($str) {
    if (!function_exists('imap_mime_header_decode')) return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    
    $decoded = '';
    $elements = imap_mime_header_decode($str);
    for ($i = 0; $i < count($elements); $i++) {
        $charset = $elements[$i]->charset;
        $text = $elements[$i]->text;
        if ($charset == 'default' || strtolower($charset) == 'utf-8') {
            $decoded .= $text;
        } else {
            $decoded .= mb_convert_encoding($text, 'UTF-8', $charset);
        }
    }
    return htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8');
}

// Utility to get body
function get_imap_body($imap_stream, $msg_num) {
    $body = @imap_fetchbody($imap_stream, $msg_num, 1);
    if (!$body) {
        $body = @imap_fetchbody($imap_stream, $msg_num, 2);
    }
    // Very basic decode attempt (IMAP bodies are often quoted-printable or base64)
    $struct = @imap_fetchstructure($imap_stream, $msg_num);
    if ($struct && isset($struct->parts) && count($struct->parts)) {
        $encoding = $struct->parts[0]->encoding;
        if ($encoding == 3) $body = base64_decode($body);
        elseif ($encoding == 4) $body = quoted_printable_decode($body);
    } else if ($struct) {
        if ($struct->encoding == 3) $body = base64_decode($body);
        elseif ($struct->encoding == 4) $body = quoted_printable_decode($body);
    }
    return mb_convert_encoding($body, 'UTF-8', 'auto');
}

// 1. Elite Caching Mechanism
if (file_exists($CACHE_FILE) && (time() - filemtime($CACHE_FILE)) < $CACHE_TTL) {
    $cached_data = file_get_contents($CACHE_FILE);
    echo $cached_data;
    exit;
}

// Ensure the cache dir exists
if (!is_dir(dirname($CACHE_FILE))) {
    mkdir(dirname($CACHE_FILE), 0755, true);
}

// ==========================================
// AUTO-FALLBACK ARCHITECTURE
// ==========================================
// If cPanel (has IMAP): Uses pure PHP.
// If Local PC (no IMAP): Falls back to Python.

if (!function_exists('imap_open')) {
    // 🚀 LOCAL PC FALLBACK (PYTHON WORKER)
    $python_script = __DIR__ . '/../imap_worker.py';
    $command = escapeshellcmd("python \"$python_script\"");
    $output = shell_exec($command);

    if (!$output) {
        echo json_encode(["success" => false, "error" => "Failed to execute Python fallback for local testing. Ensure 'python' is in PATH."]);
        exit;
    }

    $parsed = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($parsed['success'])) {
        $parsed['last_updated'] = date("Y-m-d H:i:s");
        $final_output = json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($CACHE_FILE, $final_output);
        echo $final_output;
    } else {
        echo json_encode(["success" => false, "error" => "Invalid response from Python worker", "raw" => $output]);
    }
    exit; // Stop execution, we got data from python!
}

// ==========================================
// 🚀 CPANEL PURE PHP IMAP LOGIC
// ==========================================

try {
    $accounts = Database::getActiveAccounts();
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "DB Error: " . $e->getMessage()]);
    exit;
}

$all_inboxes = [];

foreach ($accounts as $acc) {
    $email = $acc['email'];
    $password = $acc['app_password'];
    $hostname = '{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX';

    $inbox_data = [
        "email" => $email,
        "status" => "error",
        "messages" => []
    ];

    $imap_stream = @imap_open($hostname, $email, $password, 0, 1, ["DISABLE_AUTHENTICATOR" => "PLAIN"]);

    if ($imap_stream) {
        $inbox_data["status"] = "success";
        
        $emails = imap_search($imap_stream, 'ALL');
        if ($emails) {
            rsort($emails);
            $latest_emails = array_slice($emails, 0, $FETCH_LIMIT);
            
            $sequence = implode(',', $latest_emails);
            $overview = imap_fetch_overview($imap_stream, $sequence, 0);
            
            usort($overview, function($a, $b) {
                return $b->uid - $a->uid;
            });

            foreach ($overview as $msg) {
                $subject = isset($msg->subject) ? decode_imap_text($msg->subject) : '<No Subject>';
                $from = isset($msg->from) ? decode_imap_text($msg->from) : '<Unknown Sender>';
                $date = isset($msg->date) ? date("Y-m-d H:i:s", strtotime($msg->date)) : '';
                
                if (mb_strlen($from) > 40) {
                    $from = mb_substr($from, 0, 37) . '...';
                }

                $body = get_imap_body($imap_stream, $msg->msgno);

                $inbox_data["messages"][] = [
                    "id" => $msg->msgno,
                    "subject" => $subject,
                    "sender" => $from,
                    "date" => $date,
                    "body" => $body
                ];
            }
        }
        imap_close($imap_stream);
    } else {
        $inbox_data["error_msg"] = "IMAP Auth Failed: " . imap_last_error();
    }

    $all_inboxes[] = $inbox_data;
}

$response = [
    "success" => true,
    "last_updated" => date("Y-m-d H:i:s"),
    "data" => $all_inboxes
];

$json_response = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($CACHE_FILE, $json_response);
echo $json_response;
