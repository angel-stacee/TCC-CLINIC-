<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'include_summary' => ['nullable', 'boolean'],
        ]);
        $search = trim($validated['search'] ?? '');

        $medicines = Medicine::query()
            ->select([
                'id',
                'medicine_name',
                'treatment_type',
                'unit',
                'stock',
                'minimum_stock',
                'description',
                'created_at',
                'updated_at',
            ])
            ->when($search !== '', function (Builder $query) use ($search) {
                $contains = '%'.$search.'%';
                $tokens = array_values(array_filter(
                    preg_split('/[\s,]+/', $search),
                    fn ($t) => $t !== '' && $t !== '-'
                ));

                $query->where(function (Builder $query) use ($contains, $tokens) {
                    $query->where('medicine_name', 'like', $contains)
                        ->orWhere('treatment_type', 'like', $contains)
                        ->orWhere('unit', 'like', $contains)
                        ->orWhere('description', 'like', $contains);

                    if (count($tokens) > 1) {
                        $query->orWhere(function (Builder $tokenQuery) use ($tokens) {
                            foreach ($tokens as $token) {
                                $tokenContains = '%'.$token.'%';
                                $tokenQuery->where(function (Builder $tq) use ($tokenContains) {
                                    $tq->where('medicine_name', 'like', $tokenContains)
                                        ->orWhere('treatment_type', 'like', $tokenContains)
                                        ->orWhere('unit', 'like', $tokenContains)
                                        ->orWhere('description', 'like', $tokenContains);
                                });
                            }
                        });
                    }
                });
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate($validated['per_page'] ?? 50)
            ->withQueryString();

        if (! ($validated['include_summary'] ?? false)) {
            return response()->json($medicines);
        }

        $summaryQuery = Medicine::query()
            ->selectRaw('COALESCE(SUM(stock), 0) as total_stock')
            ->selectRaw('SUM(CASE WHEN stock > 0 AND stock <= minimum_stock THEN 1 ELSE 0 END) as low_stock_count')
            ->selectRaw('SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as out_of_stock_count');

        $summary = $summaryQuery->first();

        $payload = $medicines->toArray();
        $payload['summary'] = [
            'total_medicines' => $medicines->total(),
            'total_stock' => (int) $summary->total_stock,
            'low_stock_count' => (int) $summary->low_stock_count,
            'out_of_stock_count' => (int) $summary->out_of_stock_count,
        ];

        return response()->json($payload);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'medicine_name' => 'required|string|max:255',
            'treatment_type' => 'nullable|string|max:255',
            'unit' => 'required|string|max:100',
            'stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $medicine = Medicine::create($validated);

        return response()->json([
            'message' => 'Medicine added successfully.',
            'medicine' => $medicine,
        ], 201);
    }

    public function show(Medicine $medicine)
    {
        return response()->json($medicine);
    }

    public function update(Request $request, Medicine $medicine)
    {
        $validated = $request->validate([
            'medicine_name' => 'required|string|max:255',
            'treatment_type' => 'nullable|string|max:255',
            'unit' => 'required|string|max:100',
            'stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $medicine->update($validated);

        return response()->json([
            'message' => 'Medicine updated successfully.',
            'medicine' => $medicine,
        ]);
    }

    public function destroy(Medicine $medicine)
    {
        $medicine->delete();

        return response()->json([
            'message' => 'Medicine deleted successfully.',
        ]);
    }
}
