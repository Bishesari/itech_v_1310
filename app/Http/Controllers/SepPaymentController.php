<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\PaymentTransaction;
use App\Services\Payments\SepPaymentGateway;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class SepPaymentController extends Controller
{
    public function start(
        PaymentTransaction $paymentTransaction,
        PaymentService $paymentService,
    ): RedirectResponse {
        $payment = $paymentTransaction->payment;

        abort_unless(
            $payment->user_id === auth()->id(),
            Response::HTTP_NOT_FOUND
        );

        abort_unless(
            $payment->status === PaymentStatus::PENDING
            && $payment->expires_at !== null
            && $payment->expires_at->isFuture(),
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

        $redirectUrl = route('payments.sep.callback');

        $paymentUrl = $paymentService->startGatewayPayment(
            paymentTransactionId: $paymentTransaction->id,
            redirectUrl: $redirectUrl,
        );

        return redirect()->away($paymentUrl);
    }


    public function callback(
        Request $request,
        SepPaymentGateway $gateway,
        PaymentService $paymentService,
    ): View {
        $resNum = trim((string) $request->input('ResNum', ''));
        $refNum = trim((string) $request->input('RefNum', ''));
        $state = strtoupper(trim((string) $request->input('State', '')));
        $callbackStatus = (int) $request->input('Status', 0);

        // بررسی اولیه اطلاعات برگشتی از درگاه
        if (
            $resNum === ''
            || $refNum === ''
            || $state !== 'OK'
            || $callbackStatus !== 2
        ) {
            return view('payments.sep-result', [
                'callbackReceived' => true,
                'paymentStatus' => 'pending',
            ]);
        }

        try {
            // پیدا کردن تراکنش بر اساس شناسه درخواست SEP
            $transaction = PaymentTransaction::query()
                ->with('payment')
                ->where('gateway', 'sep')
                ->where('gateway_request_id', $resNum)
                ->first();

            if (! $transaction) {
                return view('payments.sep-result', [
                    'callbackReceived' => true,
                    'paymentStatus' => 'failed',
                ]);
            }

            // Callback تکراری برای تراکنش موفق
            if ($transaction->status === PaymentTransactionStatus::SUCCESSFUL) {
                if ($transaction->gateway_reference === $refNum) {
                    return view('payments.sep-result', [
                        'callbackReceived' => true,
                        'paymentStatus' => 'paid',
                    ]);
                }

                return view('payments.sep-result', [
                    'callbackReceived' => true,
                    'paymentStatus' => 'pending',
                ]);
            }

            if ($transaction->status !== PaymentTransactionStatus::PENDING) {
                return view('payments.sep-result', [
                    'callbackReceived' => true,
                    'paymentStatus' => 'failed',
                ]);
            }

            // درخواست Verify خارج از تراکنش دیتابیس
            $verification = $gateway->verifyTransaction($refNum);

            $detail = $verification['TransactionDetail'] ?? null;

            // تطبیق اطلاعات تراکنش با پاسخ تأییدشده SEP
            $valid = is_array($detail)
                && (string) ($detail['RefNum'] ?? '') === $refNum
                && (int) ($detail['OrginalAmount'] ?? 0) === $transaction->amount
                && (int) ($detail['AffectiveAmount'] ?? 0) === $transaction->amount
                && (int) ($detail['TerminalNumber'] ?? 0)
                === (int) config('services.sep.terminal_id');

            if (! $valid) {
                return view('payments.sep-result', [
                    'callbackReceived' => true,
                    'paymentStatus' => 'pending',
                ]);
            }

            // تکمیل پرداخت و ثبت گردش مالی
            $status = $paymentService->completeGatewayPayment(
                paymentTransactionId: (int) $transaction->id,
                refNum: $refNum,
            );

            return view('payments.sep-result', [
                'callbackReceived' => true,
                'paymentStatus' => $status === PaymentStatus::SUCCESSFUL
                    ? 'paid'
                    : 'pending',
            ]);

        } catch (\Throwable $exception) {
            report($exception);

            return view('payments.sep-result', [
                'callbackReceived' => true,
                'paymentStatus' => 'pending',
            ]);
        }
    }

}
