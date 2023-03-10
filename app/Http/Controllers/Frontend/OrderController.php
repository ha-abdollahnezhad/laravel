<?php

namespace App\Http\Controllers\Frontend;

use App\Order;
use App\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;


class OrderController extends Controller
{
    public function verify(){
      $cart = Session::has('cart') ? Session::get('cart') : null;
      if(!$cart){
        Session::flash('warning', 'سبد خرید شما خالی است');
        return redirect('/');
      }

      $productsId= [];

      foreach ($cart->items as $product){
        $productsId[$product['item']->id] = ['qty' => $product['qty']];
      }

      $order = new Order();
      $order->amount = $cart->totalPrice;
      $order->user_id = Auth::user()->id;
      $order->status = 0;
      $order->save();

      $order->products()->sync($productsId);

      $payment = new Payment($order->amount, $order->id);
      $result = $payment->doPayment();

      if ($result->Status == 100) {
        return redirect()->to('https://sandbox.zarinpal.com/pg/StartPay/'.$result->Authority);
      } else {
        echo'ERR: '.$result->Status;
      }

    }
    public function index()
    {
      $orders = Order::paginate(20);
      return view('frontend.profile.orders', compact(['orders']));
    }
    public function getOrderLists($id){
      $order = Order::with('user', 'products.photos')->whereId($id)->first();

      return view('frontend.profile.lists', compact(['order']));
    }
}
