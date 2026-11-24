<?php

declare(strict_types=1);

namespace CommuniQate\Workspaces\Http;

use CommuniQate\Workspaces\Exception\NetworkException;
use CURLFile;

final class CurlTransport implements Transport
{
    public function send(string $method, string $url, #[\SensitiveParameter] array $headers, #[\SensitiveParameter] string|Upload|null $body): Response
    {
        $request = curl_init($url);
        if ($request === false) {
            throw new NetworkException('Could not initialize cURL.');
        }
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = "$name: $value";
        }
        $retryAfter = '';
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$retryAfter): int {
                if (str_starts_with(strtolower($line), 'retry-after:')) {
                    $retryAfter = trim(substr($line, strlen('retry-after:')));
                }
                return strlen($line);
            },
        ];
        if ($body instanceof Upload) {
            $options[CURLOPT_POSTFIELDS] = ['file' => new CURLFile($body->filePath, 'application/octet-stream', $body->filename)];
        } elseif ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($request, $options);
        $result = curl_exec($request);
        if ($result === false) {
            throw new NetworkException(curl_error($request), curl_errno($request));
        }
        return new Response(curl_getinfo($request, CURLINFO_RESPONSE_CODE), $result, $retryAfter);
    }
}
