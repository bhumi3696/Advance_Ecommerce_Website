<?php

namespace App\Http\Controllers;
use App\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Cart;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

use App\Mail\OrderCreatedEmail;
use Illuminate\Support\Facades\Mail;


class ProductsController extends Controller
{
    //


    public function index(){

        // $products = [0=> ["name"=>"Iphone","category"=>"smart phones","price"=>1000],
        //     1=> ["name"=>"Galaxy","category"=>"tablets","price"=>2000],
        //     2=> ["name"=>"Sony","category"=>"TV","price"=>3000]];

        $products = Product::paginate(3);

        return view("allproducts",compact("products"));

    }


    public function menProducts(){

       $products = DB::table('products')->where('type', "Men")->get();
        return view("menProducts",compact("products"));
    }


    public function womenProducts(){
        $products = DB::table('products')->where('type', "Women")->get();
        return view("womenProducts",compact("products"));
    }



    public function search(Request $request){

        $searchText = $request->get('searchText');
        $products = Product::where('name',"Like",$searchText."%")->paginate(3);
        return view("allproducts",compact("products"));
    }





    public function addProductToCart(Request $request,$id){

//        $request->session()->forget("cart");
//        $request->session()->flush();

        $prevCart = $request->session()->get('cart');
        $cart = new Cart($prevCart);

        $product = Product::find($id);
        $cart->addItem($id,$product);
        $request->session()->put('cart', $cart);

        //dump($cart);

        return redirect()->route("allProducts");


    }



    public function showCart(){

        $cart = Session::get('cart');

        //cart is not empty
        if($cart){
            return view('cartproducts',['cartItems'=> $cart]);
         //cart is empty
        }else{
            return redirect()->route("allProducts");
        }

    }




    public function deleteItemFromCart(Request $request,$id){

        $cart = $request->session()->get("cart");

        if(array_key_exists($id,$cart->items)){
            unset($cart->items[$id]);

        }

        $prevCart = $request->session()->get("cart");
        $updatedCart = new Cart($prevCart);
        $updatedCart->updatePriceAndQunatity();

        $request->session()->put("cart",$updatedCart);

        return redirect()->route('cartproducts');



    }




     public function increaseSingleProduct(Request $request,$id){


        $prevCart = $request->session()->get('cart');
        $cart = new Cart($prevCart);

        $product = Product::find($id);
        $cart->addItem($id,$product);
        $request->session()->put('cart', $cart);

        //dump($cart);

        return redirect()->route("cartproducts");


    }








       public function decreaseSingleProduct(Request $request,$id){



        $prevCart = $request->session()->get('cart');
        $cart = new Cart($prevCart);

        if( $cart->items[$id]['quantity'] > 1){
                  $product = Product::find($id);
                  $cart->items[$id]['quantity'] = $cart->items[$id]['quantity']-1;
                  $cart->items[$id]['totalSinglePrice'] = $cart->items[$id]['quantity'] *  $product['price'];
                  $cart->updatePriceAndQunatity();

                  $request->session()->put('cart', $cart);

          }



        return redirect()->route("cartproducts");


    }











    public function checkoutProducts(){

       return view('checkoutproducts');

    }



   public function createNewOrder(Request $request){

       $cart = Session::get('cart');

       $first_name = $request->input('first_name');
       $address = $request->input('address');
       $last_name = $request->input('last_name');
       $zip = $request->input('zip');
       $phone = $request->input('phone');
       $email = $request->input('email');




    //check if user is logged in or not
       $isUserLoggedIn = Auth::check();

      if($isUserLoggedIn){
      	//get user id
         $user_id = Auth::id();  //OR $user_id = Auth:user()->id;

      }else{
      	//user is guest (not logged in OR Does not have account)
        $user_id = 0;

      }




        //cart is not empty
        if($cart) {
        // dump($cart);
            $date = date('Y-m-d H:i:s');
            $newOrderArray = array("user_id" => $user_id, "status"=>"on_hold","date"=>$date,"del_date"=>$date,"price"=>$cart->totalPrice,
            "first_name"=>$first_name, "address"=> $address, 'last_name'=>$last_name, 'zip'=>$zip,'email'=>$email,'phone'=>$phone);

            $created_order = DB::table("orders")->insert($newOrderArray);
            $order_id = DB::getPdo()->lastInsertId();;


            foreach ($cart->items as $cart_item){
                $item_id = $cart_item['data']['id'];
                $item_name = $cart_item['data']['name'];
                $item_price = $cart_item['data']['price'];
                $newItemsInCurrentOrder = array("item_id"=>$item_id,"order_id"=>$order_id,"item_name"=>$item_name,"item_price"=>$item_price);
                $created_order_items = DB::table("order_items")->insert($newItemsInCurrentOrder);
            }


            //send the email

            //delete cart
            Session::forget("cart");




            $payment_info =  $newOrderArray;
            $payment_info['order_id'] = $order_id;
            $request->session()->put('payment_info',$payment_info);

        //   print_r($newOrderArray);

         return redirect()->route("showPaymentPage");

        }else{

          return redirect()->route("allProducts");


        }



   }




    public function createOrder(){
        $cart = Session::get('cart');

        //cart is not empty
        if($cart) {
        // dump($cart);
            $date = date('Y-m-d H:i:s');
            $newOrderArray = array("status"=>"on_hold","date"=>$date,"del_date"=>$date,"price"=>$cart->totalPrice);
            $created_order = DB::table("orders")->insert($newOrderArray);
            $order_id = DB::getPdo()->lastInsertId();;


            foreach ($cart->items as $cart_item){
                $item_id = $cart_item['data']['id'];
                $item_name = $cart_item['data']['name'];
                $item_price = $cart_item['data']['price'];
                $newItemsInCurrentOrder = array("item_id"=>$item_id,"order_id"=>$order_id,"item_name"=>$item_name,"item_price"=>$item_price);
                $created_order_items = DB::table("order_items")->insert($newItemsInCurrentOrder);
            }

            //delete cart
            Session::forget("cart");
            Session::flush();
            return redirect()->route("allProducts")->withsuccess("Thanks For Choosing Us");

        }else{

            return redirect()->route("allProducts");

        }


    }







      private function sendMail(){

          $user = Auth::user();
          $cart = Session::get('cart');

          if($cart != null && $user != null ){
             Mail::to($user)->send(new OrderCreatedEmail($cart));
          }



    }


}
