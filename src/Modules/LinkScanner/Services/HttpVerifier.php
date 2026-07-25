<?php

namespace ONToolkit\Modules\LinkScanner\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resilient HTTP Verification Engine using HEAD -> GET Fallback -> Timeout Retry.
 */
class HttpVerifier
{
    /**
     * Verify HTTP URL status with fallback & retry flow.
     *
     * @return array<string, mixed>
     */
    public function checkUrl(string $url, int $timeout = 5): array
    {
        $start_time = microtime(true);

        // 1. Step 1: Perform HTTP HEAD request first (fast 0.1KB check)
        $response = wp_remote_head($url, [
            'timeout'     => $timeout,
            'redirection' => 5,
            'user-agent'  => 'ONToolkit-LinkScanner/1.0 (+https://ontoolkit.com)',
            'sslverify'   => false,
        ]);

        $code = is_wp_error($response) ? 0 : (int)wp_remote_retrieve_response_code($response);

        // 2. Step 2: Fallback to GET if HEAD failed, timed out, or returned 405/403 (blocked HEAD requests)
        if (is_wp_error($response) || $code === 405 || $code === 403 || $code === 0) {
            $response = wp_remote_get($url, [
                'timeout'     => $timeout,
                'redirection' => 5,
                'user-agent'  => 'ONToolkit-LinkScanner/1.0 (+https://ontoolkit.com)',
                'headers'     => ['Range' => 'bytes=0-1024'], // Fetch first 1KB only
                'sslverify'   => false,
            ]);
            $code = is_wp_error($response) ? 0 : (int)wp_remote_retrieve_response_code($response);
        }

        // 3. Step 3: Retry once if timeout or error occurred
        if (is_wp_error($response) || $code === 0) {
            $response = wp_remote_get($url, [
                'timeout'     => $timeout + 3, // Increased timeout on retry
                'redirection' => 5,
                'user-agent'  => 'ONToolkit-LinkScanner/1.0 (+https://ontoolkit.com)',
                'sslverify'   => false,
            ]);
            $code = is_wp_error($response) ? 0 : (int)wp_remote_retrieve_response_code($response);
        }

        $latency_ms = (int)(round((microtime(true) - $start_time) * 1000));
        $headers = is_wp_error($response) ? [] : wp_remote_retrieve_headers($response);
        $redirect_target = is_array($headers) && isset($headers['location']) ? (string)$headers['location'] : null;

        if (is_wp_error($response)) {
            $error_msg = $response->get_error_message();
            $is_timeout = (strpos(strtolower($error_msg), 'timed out') !== false);
            $status_type = $is_timeout ? 'timeout' : 'broken';

            return [
                'url' => $url,
                'status_code' => 0,
                'status_type' => $status_type,
                'redirect_url' => null,
                'response_time_ms' => $latency_ms,
                'diagnosis' => $is_timeout 
                    ? __('Target server timed out (failed to respond within 5s).', 'on-toolkit')
                    : sprintf(__('Connection error: %s', 'on-toolkit'), $error_msg),
                'why_text' => $is_timeout 
                    ? __('Timeout occurs when the target server is down or blocking requests.', 'on-toolkit')
                    : __('Network or DNS resolution failure.', 'on-toolkit'),
            ];
        }

        // Categorize final status and generate Explain Why context
        $diagnosis = '';
        $why_text = '';

        if ($code >= 200 && $code < 300) {
            $status_type = 'ok';
            $diagnosis = __('URL is healthy and reachable (200 OK).', 'on-toolkit');
        } elseif ($code >= 300 && $code < 400) {
            $status_type = 'redirect';
            $diagnosis = sprintf(__('URL redirects (%d Redirect) to another location.', 'on-toolkit'), $code);
            $why_text = sprintf(__('Redirect target: %s', 'on-toolkit'), $redirect_target ?? 'Unknown');
        } elseif ($code === 404) {
            $status_type = 'broken';
            $diagnosis = __('404 Not Found — Target page was deleted or renamed.', 'on-toolkit');
            $why_text = __('Web server confirmed the page does not exist at this URL.', 'on-toolkit');
        } elseif ($code === 403) {
            $status_type = 'broken';
            $diagnosis = __('403 Forbidden — WAF or Firewall blocked request.', 'on-toolkit');
            $why_text = __('Cloudflare or server security rules prohibited access.', 'on-toolkit');
        } elseif ($code >= 500) {
            $status_type = 'broken';
            $diagnosis = sprintf(__('%d Internal Server Error — Target server crashed.', 'on-toolkit'), $code);
            $why_text = __('Remote server encountered an unhandled PHP/database crash.', 'on-toolkit');
        } else {
            $status_type = 'unknown';
            $diagnosis = sprintf(__('Returned HTTP status code %d.', 'on-toolkit'), $code);
        }

        return [
            'url' => $url,
            'status_code' => $code,
            'status_type' => $status_type,
            'redirect_url' => $redirect_target,
            'response_time_ms' => $latency_ms,
            'diagnosis' => $diagnosis,
            'why_text' => $why_text,
        ];
    }
}
