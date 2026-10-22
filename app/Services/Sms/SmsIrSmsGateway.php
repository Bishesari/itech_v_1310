<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\Exceptions\sms\SmsDeliveryException;
use Illuminate\Support\Facades\Http;
use Throwable;
final class SmsIrSmsGateway implements SmsGateway
{
    public function sendOtp(
        string $mobile,
        string $verificationCode,
    ): void {
        $templateId = config('services.sms_ir.otp_template_id');

        if (! $templateId) {
            throw new SmsDeliveryException(
                'شناسه قالب پیامک OTP تنظیم نشده است.'
            );
        }

        try {
            $response = Http::baseUrl(
                config('services.sms_ir.url')
            )
                ->withHeaders([
                    'X-API-KEY' => config('services.sms_ir.api_key'),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/send/verify', [
                    'mobile' => $mobile,
                    'templateId' => (int) $templateId,
                    'parameters' => [
                        [
                            'name' => config(
                                'services.sms_ir.otp_parameter_name',
                                'Code'
                            ),
                            'value' => $verificationCode,
                        ],
                    ],
                ]);

            $result = $response->json();

            if (
                ! $response->successful()
                || ($result['status'] ?? 0) !== 1
            ) {
                throw new SmsDeliveryException(
                    $result['message']
                    ?? 'ارسال پیامک با خطا مواجه شد.'
                );
            }
        } catch (SmsDeliveryException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SmsDeliveryException(
                'ارتباط با سرویس پیامک برقرار نشد.',
                previous: $e,
            );
        }
    }

    public function sendPassword(
        string $mobile,
        string $username,
        string $password,
    ): void {
        $templateId = config('services.sms_ir.password_template_id');

        if (! $templateId) {
            throw new SmsDeliveryException(
                'شناسه قالب پیامک کلمه عبور تنظیم نشده است.'
            );
        }

        try {
            $response = Http::baseUrl(
                config('services.sms_ir.url')
            )
                ->withHeaders([
                    'X-API-KEY' => config('services.sms_ir.api_key'),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post('/send/verify', [
                    'mobile' => $mobile,
                    'templateId' => (int) $templateId,
                    'parameters' => [
                        [
                            'name' => config(
                                'services.sms_ir.password_username_parameter_name',
                                'USERNAME'
                            ),
                            'value' => $username,
                        ],
                        [
                            'name' => config(
                                'services.sms_ir.password_parameter_name',
                                'PASSWORD'
                            ),
                            'value' => $password,
                        ],
                    ],
                ]);

            $result = $response->json();

            if (
                ! $response->successful()
                || ($result['status'] ?? 0) !== 1
            ) {
                throw new SmsDeliveryException(
                    $result['message']
                    ?? 'ارسال پیامک کلمه عبور با خطا مواجه شد.'
                );
            }
        } catch (SmsDeliveryException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SmsDeliveryException(
                'ارتباط با سرویس پیامک برقرار نشد.',
                previous: $e,
            );
        }
    }
}
