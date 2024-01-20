<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;

class FrontController extends Controller
{
    public function index()
    {
        $produkfront = Produk::all();
        return view('front.content', compact('produkfront'));
    }

    public function cart()
    {
        return view('front.cart');
    }
}