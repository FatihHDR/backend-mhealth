<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($payments);
    }

    public function show($id)
    {
        $payment = Payment::with('user')->findOrFail($id);

        return response()->json($payment);
    }

    public function byUser($userId)
    {
        $payments = Payment::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($payments);
    }

    public function byStatus($status)
    {
        $payments = Payment::where('status', $status)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($payments);
    }
}
