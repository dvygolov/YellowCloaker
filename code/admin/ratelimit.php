<?php
require_once __DIR__ . '/../bases/ipcountry.php';

define('RL_MAX_ATTEMPTS', 5);
define('RL_LOCKOUT_SECONDS', 300);
define('RL_DIR', __DIR__ . '/../tmp/bruteforce');

function rl_get_file(string $ip): string
{
    if (!file_exists(RL_DIR))
        mkdir(RL_DIR, 0777, true);
    return RL_DIR . '/' . md5($ip) . '.json';
}

function rl_read(string $ip): array
{
    $file = rl_get_file($ip);
    if (!file_exists($file))
        return ['attempts' => 0, 'locked_until' => 0];
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data))
        return ['attempts' => 0, 'locked_until' => 0];
    return $data;
}

function rl_write(string $ip, array $data): void
{
    $file = rl_get_file($ip);
    file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * Check if IP is allowed to attempt login.
 * Returns ['allowed' => bool, 'retry_after' => int seconds remaining]
 */
function check_rate_limit(string $ip): array
{
    $data = rl_read($ip);

    if ($data['locked_until'] > 0) {
        $remaining = $data['locked_until'] - time();
        if ($remaining > 0) {
            return ['allowed' => false, 'retry_after' => $remaining];
        }
        // Lockout expired — reset
        rl_reset($ip);
    }

    return ['allowed' => true, 'retry_after' => 0];
}

/**
 * Record a failed login attempt. Locks after RL_MAX_ATTEMPTS.
 */
function record_failed_attempt(string $ip): void
{
    $data = rl_read($ip);
    $data['attempts']++;
    if ($data['attempts'] >= RL_MAX_ATTEMPTS) {
        $data['locked_until'] = time() + RL_LOCKOUT_SECONDS;
    }
    rl_write($ip, $data);
}

/**
 * Reset attempts on successful login.
 */
function rl_reset(string $ip): void
{
    $file = rl_get_file($ip);
    if (file_exists($file))
        @unlink($file);
}
