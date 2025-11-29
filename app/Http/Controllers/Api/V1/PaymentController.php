<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use App\Models\Payment;

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
}
