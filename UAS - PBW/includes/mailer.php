<?php
require_once __DIR__ . '/../config/email.php';

function smtp_read_response($socket) {
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function smtp_command($socket, $command, array $expected_codes) {
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }
    $response = smtp_read_response($socket);
    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expected_codes, true)) {
        throw new Exception(trim($response));
    }
    return $response;
}

function build_email_message($to, $subject, $html, $text = '') {
    $from = MAIL_FROM_ADDRESS;
    $from_name = MAIL_FROM_NAME;
    $boundary = 'b' . bin2hex(random_bytes(12));
    $subject_encoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $headers = [];
    $headers[] = 'Date: ' . date('r');
    $headers[] = 'From: ' . '=?UTF-8?B?' . base64_encode($from_name) . '?=' . ' <' . $from . '>';
    $headers[] = 'To: <' . $to . '>';
    $headers[] = 'Subject: ' . $subject_encoded;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

    if ($text === '') {
        $text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
    }

    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $text . "\r\n\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $html . "\r\n\r\n";
    $body .= "--$boundary--\r\n";

    return implode("\r\n", $headers) . "\r\n\r\n" . $body;
}

function send_email_smtp($to, $subject, $html, $text = '') {
    if (SMTP_HOST === '' || SMTP_USERNAME === '' || SMTP_PASSWORD === '') {
        return ['success' => false, 'error' => 'Konfigurasi SMTP belum lengkap.'];
    }

    try {
        $host = SMTP_HOST;
        $port = (int) SMTP_PORT;
        $encryption = strtolower((string) SMTP_ENCRYPTION);
        $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host;
        $socket = fsockopen($remote, $port, $errno, $errstr, 20);
        if (!$socket) {
            return ['success' => false, 'error' => $errstr ?: ('SMTP error ' . $errno)];
        }
        stream_set_timeout($socket, 20);
        smtp_command($socket, null, [220]);
        smtp_command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);

        if ($encryption === 'tls') {
            smtp_command($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception('STARTTLS gagal.');
            }
            smtp_command($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), [250]);
        }

        smtp_command($socket, 'AUTH LOGIN', [334]);
        smtp_command($socket, base64_encode(SMTP_USERNAME), [334]);
        smtp_command($socket, base64_encode(SMTP_PASSWORD), [235]);
        smtp_command($socket, 'MAIL FROM:<' . MAIL_FROM_ADDRESS . '>', [250]);
        smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtp_command($socket, 'DATA', [354]);
        $message = build_email_message($to, $subject, $html, $text);
        fwrite($socket, $message . "\r\n.\r\n");
        smtp_command($socket, null, [250]);
        smtp_command($socket, 'QUIT', [221]);
        fclose($socket);
        return ['success' => true, 'error' => ''];
    } catch (Throwable $e) {
        if (isset($socket) && is_resource($socket)) {
            fclose($socket);
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function send_app_email($to, $subject, $html, $text = '') {
    if (MAIL_DRIVER === 'smtp') {
        return send_email_smtp($to, $subject, $html, $text);
    }

    $from = MAIL_FROM_ADDRESS;
    $from_name = '=?UTF-8?B?' . base64_encode(MAIL_FROM_NAME) . '?=';
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $from_name . ' <' . $from . '>';
    $headers[] = 'Reply-To: ' . $from;

    $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, implode("\r\n", $headers));
    return ['success' => (bool)$ok, 'error' => $ok ? '' : 'Fungsi mail() tidak berhasil mengirim email. Gunakan SMTP di config/email.php.'];
}
?>
