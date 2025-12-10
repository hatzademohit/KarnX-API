<?php
namespace App\Http\Controllers\Api\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;
use DB;
use Auth;

class RazorpayController extends Controller
{
    public function createOrder(Request $request)
    {
        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
        
        $order = $api->order->create([
            'receipt' => uniqid(),
            'amount' => $request->amount * 100, // INR to paisa
            'currency' => 'INR'
        ])->toArray();
        
        return response()->json($order);
    }

    public function verifyPayment(Request $request)
    {
        return response()->json(['status' => 'ddd', 'data' => $request->all()]);
        $signature = $request->razorpay_signature;

        $generated_signature = hash_hmac(
            "sha256",
            $request->razorpay_order_id . "|" . $request->razorpay_payment_id,
            env('RAZORPAY_SECRET')
        );

        if ($generated_signature === $signature) {
            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
            $payment = $api->payment->fetch($request->razorpay_payment_id);
            //dd($payment['email']);
            \DB::table('payments')->insert([
                'user_id' => auth()->id(),
                'client_id' => Auth::user()->client_id,
                'booking_inquiries_id' => $request->booking_inquiries_id,
                'quote_id' => $request->quote_id,
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
                'amount' => $payment['amount'] / 100,
                'currency' => $payment['currency'],
                'payment_method' => $payment['method'],
                //'email' => $payment['email'],
                //'contact' => $payment['contact'],
                'bank' => $payment['bank'] ?? null,
                'wallet' => $payment['wallet'] ?? null,
                'card_last4' => $payment['card']['last4'] ?? null,
                'upi_id' => $payment['vpa'] ?? null,
                'status' => 'success',
                'created_at' => now()
            ]);
            return response()->json(['status' => 'success']);
        } else {
            \DB::table('payments')->insert([
                'user_id' => auth()->id(),
                'client_id' => Auth::user()->client_id,
                'booking_inquiries_id' => $request->booking_inquiries_id,
                'quote_id' => $request->quote_id,
                'razorpay_order_id' => $request->razorpay_order_id,
                'status' => 'failed',
                'error_code' => 'signature_failed'
            ]);
            return response()->json(['status' => 'failed']);
        }
    }
}
