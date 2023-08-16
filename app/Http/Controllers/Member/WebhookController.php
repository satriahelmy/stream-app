<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Package;
use App\Models\UserPremium;

class WebhookController extends Controller
{
    public function handler(Request $request)
    {
        \Midtrans\Config::$isProduction = (bool)env('MIDTRANS_IS_PRODUCTION');
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        $notif = new \Midtrans\Notification();

        $status = '';
        $transactionStatus = $notif->transaction_status;
        $transactionCode = $notif->order_id;
        $fraudStatus = $notif->fraud_status;

        if ($transactionStatus == 'capture')
        {
            if ($fraudStatus == 'accept')
            {
                    $status = 'success';
            }
            else if ($transactionStatus == 'settlement')
            {
                $status = 'success';
            }
            else if ($transactionStatus == 'cancel' ||
              transactionStatus == 'deny' ||
              transactionStatus == 'expire')
            {
              $status = 'failure';
            }
            else if ($transactionStatus == 'pending')
            {
              $status = 'pending';
            }
        }

        $transaction = Transaction::with('package')
            ->where('transaction_code',$transactionCode)
            ->first();

        if($status == 'success')
        {
            UserPremium::create([
                'package_id' => $transaction->package->id,
                'user_id' => $transaction->user_id,
                'end_of_subscription' => now()->addDays($transaction->package->max_days)
            ]);
        }

        $transaction->update(['status'=>$status]);
    }
}
