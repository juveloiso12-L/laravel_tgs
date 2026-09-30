<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function search(Request $request)
    {
        $keyword = $request->get('q', '');

        $products = Product::active()
            ->when($keyword, function ($query) use ($keyword) {
                $query->search($keyword);
            })
            ->with('category')
            ->limit(20)
            ->get(['id', 'category_id', 'code', 'barcode', 'name', 'price', 'stock', 'unit'])
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'barcode' => $product->barcode,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                    'formatted_price' => 'Rp '.number_format($product->price, 0, ',', '.'),
                    'stock' => $product->stock,
                    'unit' => $product->unit,
                    'category' => $product->category?->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function getByBarcode(Request $request, $barcode)
    {
        $product = Product::active()
            ->where(function ($query) use ($barcode) {
                $query->where('barcode', $barcode)
                    ->orWhere('code', $barcode);
            })
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'code' => $product->code,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'price' => (float) $product->price,
                'formatted_price' => 'Rp '.number_format($product->price, 0, ',', '.'),
                'stock' => $product->stock,
                'unit' => $product->unit,
            ],
        ]);
    }
}
