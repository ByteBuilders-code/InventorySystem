<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\StockMovement;

class ProductController extends Controller
{
    public function index()
    {
        $productos = Product::all();
        return response()->json($productos);
    }

    public function create()
    {
        $productos = Product::all();
        return view('products.create', compact('productos'));
    }

    public function store(Request $request)
    {
        try {
            // Primero, veamos qué datos están llegando
            \Log::info('Datos recibidos:', $request->all());

            $validatedData = $request->validate([
                'name' => 'required',
                'description' => 'required',
                'price' => 'required|numeric',
                'barcode' => 'required|unique:products',
                'image' => 'required',  // Removí temporalmente las validaciones de imagen
                'stock' => 'required|integer',
            ]);

            \Log::info('Datos validados:', $validatedData);

            $producto = Product::create($validatedData);
            
            \Log::info('Producto creado:', $producto->toArray());

            return response()->json([
                'message' => 'Producto creado correctamente',
                'producto' => $producto,
                'datos_recibidos' => $request->all()
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error al crear producto: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'datos_recibidos' => $request->all()
            ], 500);
        }
    }

    public function show($id)
    {
        $producto = Product::findOrFail($id);
        return response()->json($producto);
    }

    public function edit($id)
    {
        $producto = Product::findOrFail($id);
        return view('products.edit', compact('producto'));
    }

    public function update(Request $request, $id)
    {
        $producto = Product::findOrFail($id);
        
        $request->validate([
            'name' => 'required',
            'description' => 'required', 
            'price' => 'required|numeric',
            'barcode' => 'required|unique:products,barcode,'.$id,
            'stock' => 'required|integer',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $producto->update($request->all());
        
        return response()->json([
            'message' => 'Producto actualizado correctamente',
            'producto' => $producto
        ]);
    }

    public function updateStock(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'barcode' => 'required|string',
                'cantidad' => 'required|integer',
                'tipo_movimiento' => 'required|in:entrada,salida,ajuste',
                'notas' => 'nullable|string'
            ]);

            $producto = Product::where('barcode', $request->barcode)->firstOrFail();
            $stockAnterior = $producto->stock;

            switch ($request->tipo_movimiento) {
                case 'entrada':
                    $producto->stock += $request->cantidad;
                    break;
                case 'salida':
                    $producto->stock -= $request->cantidad;
                    break;
                case 'ajuste':
                    $producto->stock = $request->cantidad;
                    break;
            }

            // Crear el movimiento sin requerir usuario_id
            StockMovement::create([
                'product_id' => $producto->id,
                'tipo_movimiento' => $request->tipo_movimiento,
                'cantidad' => $request->cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $producto->stock,
                'notas' => $request->notas
                // Removido usuario_id ya que es nullable
            ]);

            $producto->save();

            return response()->json([
                'message' => 'Stock actualizado correctamente',
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $producto->stock,
                'producto' => $producto
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $producto = Product::findOrFail($id);
        $producto->delete();
        
        return response()->json([
            'message' => 'Producto eliminado correctamente'
        ]);
    }
}
