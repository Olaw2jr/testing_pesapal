<?php

namespace App\Http\Controllers;

use App\payment;
use Pesapal;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function payment()
    {

        //initiates payment
        $payments = new Payment;
        // $payments->businessid = Auth::guard('web')->id(); //Business ID
        $payments->transactionid = Pesapal::random_reference();
        $payments->status = 'NEW'; //if user gets to iframe then exits, i prefer to have that as a new/lost transaction, not pending
        $payments->amount = 200;
        $payments->save();

        $details = array(
            'amount' => $payments->amount,
            'description' => 'Test Transaction',
            'type' => 'MERCHANT',
            'first_name' => 'Oscar',
            'last_name' => 'Olotu',
            'email' => 'oscar.eugine@gmail.com',
            'phonenumber' => '255-714667787',
            'reference' => $payments->transactionid,
            'height' => '600px',
            //'currency' => 'USD'
        );
        $iframe = Pesapal::makePayment($details);

        return view('payments.business.pesapal', compact('iframe'));
    }

    public function paymentsuccess(Request $request)//just tells u payment has gone thru..but not confirmed
    {
        $trackingid = $request->input('tracking_id');
        $ref = $request->input('merchant_reference');

        $payments = Payment::where('transactionid', $ref)->first();
        $payments->trackingid = $trackingid;
        $payments->status = 'PENDING';
        $payments->save();
        //go back home
        $payments = Payment::all();
        return view('payments.business.home', compact('payments'));
    }

    //This method just tells u that there is a change in pesapal for your transaction..
    //u need to now query status..retrieve the change...CANCELLED? CONFIRMED?
    public function paymentconfirmation(Request $request)
    {
        $trackingid = $request->input('pesapal_transaction_tracking_id');
        $merchant_reference = $request->input('pesapal_merchant_reference');
        $pesapal_notification_type = $request->input('pesapal_notification_type');

        //use the above to retrieve payment status now..
        $this->checkpaymentstatus($trackingid, $merchant_reference, $pesapal_notification_type);
    }

    //Confirm status of transaction and update the DB
    public function checkpaymentstatus($trackingid, $merchant_reference, $pesapal_notification_type)
    {
        $status = Pesapal::getMerchantStatus($merchant_reference);
        $payments = Payment::where('trackingid', $trackingid)->first();
        $payments->status = $status;
        $payments->payment_method = "PESAPAL";//use the actual method though...
        $payments->save();
        return "success";
    }
}
