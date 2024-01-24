<?php

namespace App\Http\Controllers;

use App\Models\AccPenjualan;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccPenjualanController extends Controller
{
    public function index(Request $request)
    {
        $pagination = 5;

        $userId = Auth::id();

        $data = AccPenjualan::whereHas('produk', function ($q) use ($request, $userId) {
            $q->where('nama_produk', 'LIKE', '%' . $request->search . '%')
                ->where('user_id', $userId); // Menambahkan kondisi untuk memastikan hanya produk milik user yang login
        })
            ->orderBy('id', 'asc')
            ->paginate($pagination);

        return view('accpenjualan.index', compact('data'));
    }
    public function Update(Request $request, $id)
    {
        $penjualan = AccPenjualan::find($id);

        if ($penjualan->jumlah > $penjualan->produk->stok) {
            return redirect()->route('accpenjualan')->with('error', 'Stok tidak cukup untuk memproses pesanan.');
        }

        $penjualan->status = 'Terkirim';
        $penjualan->save();

        $produk = $penjualan->produk;
        $produk->stok -= $penjualan->jumlah;
        $produk->save();

        return redirect()->route('accpenjualan')->with('message', 'Berhasil Memperbarui Data');
    }

    public function Delete($id)
    {
        $data = AccPenjualan::find($id);
        if ($data->items()->exists()) {
            return redirect()->route('accpenjualan')->with('error', 'kategori masih memiliki relasi');
        }
        ;
        $data->delete($id);
        return redirect()->route('accpenjualan')->with('message', 'Berhasil Menghapus Data');
    }
    public function Send(Request $request, $id)
    {
        $user_id = auth()->user()->id;

        $request->validate([
            'pesan' => 'nullable',
            'jumlah' => 'required',

        ]);

        $produk = Produk::findOrFail($id);

        $total_bayar = $request->jumlah * $produk->harga_jual;

        $accPenjualan = new AccPenjualan([
            'user_id' => $user_id,
            'produk_id' => $id,
            'status' => 'Menunggu',
            'pesan' => $request->pesan,
            'jumlah' => $request->jumlah,
            'total_bayar' => $total_bayar,
        ]);

        $accPenjualan->save();
        return redirect()->back()->with('message', 'Berhasil Checkout Barang');
    }
    public function add(Request $request)
    {
        $user = auth()->user();

        foreach ($user->keranjang as $cartItem) {
            AccPenjualan::create([
                'user_id' => $user->id,
                'produk_id' => $cartItem->produk_id,
                'status' => 'Menunggu',
                'jumlah' => $cartItem->stok,
                'pesan' => 'Belii',
                'total_bayar' => $cartItem->produk->harga_jual * $cartItem->stok,
            ]);

            $cartItem->delete();
        }
        return redirect()->back()->with('message', 'Berhasil Checkout Barang');
    }

}