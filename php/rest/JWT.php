<?php
declare(strict_types=1);
class JWT
{
    private static function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64url_decode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) $data .= str_repeat('=', 4 - $remainder);
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function sign(array $payload, string $secret, int $expSeconds = 3600): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload['iat'] = time();
        $payload['exp'] = time() + $expSeconds;
        $segments = [];
        $segments[] = self::base64url_encode(json_encode($header));
        $segments[] = self::base64url_encode(json_encode($payload));
        $signing_input = implode('.', $segments);
        $signature = hash_hmac('sha256', $signing_input, $secret, true);
        $segments[] = self::base64url_encode($signature);
        return implode('.', $segments);
    }

    public static function verify(string $jwt, string $secret)
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return false;
        [$headerB64, $payloadB64, $sigB64] = $parts;
        $signing_input = $headerB64 . '.' . $payloadB64;
        $expected_sig = hash_hmac('sha256', $signing_input, $secret, true);
        $provided_sig = self::base64url_decode($sigB64);
        if (!hash_equals($expected_sig, $provided_sig)) return false;
        $payloadJson = self::base64url_decode($payloadB64);
        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) return false;
        if (isset($payload['exp']) && time() > $payload['exp']) return false;
        return $payload;
    }
}
