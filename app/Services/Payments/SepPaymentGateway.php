<?php

declare(strict_types=1);

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SepPaymentGateway
{
    public function requestToken(
        int $amount,
        string $resNum,
        string $redirectUrl,
        ?string $cellNumber = null,
    ): string {
        $terminalId = config('services.sep.terminal_id');
        $tokenUrl = config('services.sep.token_url');

        if (blank($terminalId)) {
            throw new RuntimeException(
                'SEP terminal ID is not configured.'
            );
        }

        if (blank($tokenUrl)) {
            throw new RuntimeException(
                'SEP token URL is not configured.'
            );
        }

        if ($amount <= 0 || blank($resNum)) {
            throw new RuntimeException(
                'Invalid payment amount or reservation number.'
            );
        }

        if (
            parse_url($redirectUrl, PHP_URL_SCHEME) !== 'https'
            || ! parse_url($redirectUrl, PHP_URL_HOST)
        ) {
            throw new RuntimeException(
                'SEP callback URL must use HTTPS.'
            );
        }

        $payload = [
            'Action' => 'Token',
            'TerminalId' => $terminalId,
            'Amount' => $amount,
            'ResNum' => $resNum,
            'RedirectUrl' => $redirectUrl,
        ];

        if ($cellNumber !== null && $cellNumber !== '') {
            $payload['CellNumber'] = $cellNumber;
        }

        $response = Http::asJson()
            ->acceptJson()
            ->connectTimeout(config('services.sep.connect_timeout', 5))
            ->timeout(config('services.sep.timeout', 15))
            ->post($tokenUrl, $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                'SEP token request failed with HTTP status '
                .$response->status().'.'
            );
        }

        $result = $response->json();

        if (
            ! is_array($result)
            || (int) ($result['status'] ?? 0) !== 1
            || ! is_string($result['token'] ?? null)
            || trim($result['token']) === ''
        ) {
            throw new RuntimeException(
                'SEP did not return a valid payment token.'
            );
        }

        return $result['token'];
    }

    public function verifyTransaction(string $refNum): array
    {
        $terminalId = config('services.sep.terminal_id');
        $verifyUrl = config('services.sep.verify_url');

        if (blank($terminalId)) {
            throw new RuntimeException(
                'SEP terminal ID is not configured.'
            );
        }

        if (blank($verifyUrl)) {
            throw new RuntimeException(
                'SEP verification URL is not configured.'
            );
        }

        if (blank($refNum)) {
            throw new RuntimeException(
                'SEP reference number is missing.'
            );
        }

        $response = Http::asJson()
            ->acceptJson()
            ->connectTimeout(config('services.sep.connect_timeout', 5))
            ->timeout(config('services.sep.timeout', 15))
            ->post($verifyUrl, [
                'RefNum' => $refNum,
                'TerminalNumber' => (int) $terminalId,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'SEP verification request failed with HTTP status '
                .$response->status().'.'
            );
        }

        $result = $response->json();

        if (! is_array($result)) {
            throw new RuntimeException(
                'SEP returned an invalid verification response.'
            );
        }

        if (
            ($result['Success'] ?? false) !== true
            || (int) ($result['ResultCode'] ?? -1) !== 0
        ) {
            throw new RuntimeException(
                'SEP verification unsuccessful. ResultCode: '
                .($result['ResultCode'] ?? 'unknown').'.'
            );
        }

        if (
            ! isset($result['TransactionDetail'])
            || ! is_array($result['TransactionDetail'])
        ) {
            throw new RuntimeException(
                'SEP verification response is missing TransactionDetail.'
            );
        }

        return $result;
    }
    public function getPaymentUrl(string $token): string
    {
        $sendTokenUrl = config('services.sep.send_token_url');

        if (blank($sendTokenUrl)) {
            throw new RuntimeException(
                'SEP payment URL is not configured.'
            );
        }

        if (blank($token)) {
            throw new RuntimeException(
                'SEP payment token is missing.'
            );
        }

        return $sendTokenUrl.'?token='.urlencode($token);
    }
}
