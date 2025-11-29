<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use Paginates;
    public function index()
    {
        $query = Payment::with('user')->orderBy('created_at', 'desc');
        $payments = $this->paginateQuery($query);

        return response()->json($payments);
    }

    public function show($id)
    {
        $payment = Payment::with('user')->findOrFail($id);

        return response()->json($payment);
    }

    public function byUser($userId)
    {
        $query = Payment::where('user_id', $userId)->orderBy('created_at', 'desc');
        $payments = $this->paginateQuery($query);

        return response()->json($payments);
    }

    public function byStatus($status)
    {
        $query = Payment::where('status', $status)->with('user')->orderBy('created_at', 'desc');
        $payments = $this->paginateQuery($query);

        return response()->json($payments);
    }

    /**
     * Create a Midtrans Snap transaction and return snap token / payment details.
     * Expects `amount` (numeric) and optional customer details.
     */
    public function createSnap(Request $request)
    {
        $amount = (int) $request->input('amount', 0);
        if ($amount <= 0) {
            return response()->json(['message' => 'Invalid amount'], 422);
        }

        $orderId = $request->input('order_id', 'order-' . Str::random(8) . '-' . time());

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
            ],
        ];

        $serverKey = env('MIDTRANS_SERVER_KEY');
        $isProd = env('MIDTRANS_IS_PRODUCTION', false);
        $base = $isProd ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
        $url = $base . '/snap/v1/transactions';

        if (! $serverKey) {
            return response()->json(['message' => 'Missing MIDTRANS_SERVER_KEY'], 500);
        }

        // Midtrans Snap expects basic auth with server key as username
        $response = Http::withBasicAuth($serverKey, '')->post($url, $payload);

        if (! $response->successful()) {
            return response()->json(['message' => 'Midtrans error', 'detail' => $response->body()], 502);
        }

        return response()->json($response->json());
    }

    /**
     * Midtrans webhook / notification endpoint.
     * Verifies `signature_key` then updates local payment/order record.
     */
    public function notification(Request $request)
    {
        $data = $request->all();

        $serverKey = env('MIDTRANS_SERVER_KEY');
        if (! $serverKey) {
            return response()->json(['message' => 'Missing MIDTRANS_SERVER_KEY'], 500);
        }

        // Midtrans provides a `signature_key` in payload which should equal
        // sha512(order_id + status_code + gross_amount + serverKey)
        $signatureProvided = $data['signature_key'] ?? null;
        $orderId = $data['order_id'] ?? null;
        $statusCode = $data['status_code'] ?? null;
        $grossAmount = $data['gross_amount'] ?? null;

        if (! $signatureProvided || ! $orderId) {
            return response()->json(['message' => 'Invalid notification payload'], 400);
        }

        $toHash = $orderId . ($statusCode ?? '') . ($grossAmount ?? '') . $serverKey;
        $computed = hash('sha512', $toHash);

        if (! hash_equals($computed, $signatureProvided)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // Find the payment by order_id and update status (adjust to your schema)
        $payment = Payment::where('order_id', $orderId)->first();
        if ($payment) {
            $payment->status = $data['transaction_status'] ?? ($data['status_code'] ?? $payment->status);
            $payment->raw_notification = json_encode($data);
            $payment->save();
        }

        // Return 200 to acknowledge
        return response()->json(['message' => 'ok']);
    }
}
