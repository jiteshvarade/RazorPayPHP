<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;

class RazorpayController extends Controller
{
    private $razorpayId;
    private $razorpaySecret;
    private $webhookSecret;

    public function __construct()
    {
        $this->razorpayId = env('RAZORPAY_ID_KEY');
        $this->razorpaySecret = env('RAZORPAY_SECRET_KEY');
        $this->webhookSecret = env('WEBHOOK_SECRET');
    }

    public function createOrder(Request $request)
    {
        $api = new Api($this->razorpayId, $this->razorpaySecret);

        $amount = $request->input('amount') * 100; 
        $options = [
            'amount' => $amount,
            'currency' => 'INR',
            'receipt' => 'order_rcptid_11'
        ];

        try {
            $order = $api->order->create($options);
            return response()->json($order);
        } catch (\Exception $e) {
            Log::error("Error creating Razorpay order: " . $e->getMessage());
            return response()->json(['error' => 'Error creating Razorpay order'], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        $signature = $request->header('x-razorpay-signature');
        $payload = json_encode($request->all());

        $generatedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        if ($generatedSignature === $signature) {
            $email = $request->input('payload.payment.entity.email');
            $amount = $request->input('payload.payment.entity.amount');
            Log::info("Successful payment from: $email, amount: $amount");

            return response()->json(['status' => 'ok']);
        } else {
            Log::error("Invalid Razorpay signature. Payment verification failed.");
            return response()->json(['error' => 'Invalid signature'], 500);
        }
    }
}
