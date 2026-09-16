<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $fromDate = $this->validDate($request->query('from_date'));
        $toDate = $this->validDate($request->query('to_date'));

        if ($fromDate && $toDate && $fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $allMain = Stock::whereIn('category', ['bag', 'bnd', 'sqf'])
            ->orderBy('category')
            ->orderBy('size')
            ->get();

        $bagGroups = $allMain->where('category', 'bag')->toBase()->groupBy('size');
        $bndGroups = $allMain->where('category', 'bnd')->toBase()->groupBy('size');
        $sqfGroups = $allMain->where('category', 'sqf')->toBase()->groupBy('size');

        $extras = Stock::whereNotIn('category', ['bag', 'bnd', 'sqf'])->get();
        $logsQuery = StockLog::with('stock')->orderByDesc('logged_at');

        if ($fromDate || $toDate) {
            if ($fromDate) {
                $logsQuery->whereDate('logged_at', '>=', $fromDate);
            }

            if ($toDate) {
                $logsQuery->whereDate('logged_at', '<=', $toDate);
            }
        } else {
            $logsQuery->take(50);
        }

        // Combine for summary cards
        $summaryGroups = $allMain->toBase()->groupBy(function($item) {
            return strtoupper($item->category) . ' - ' . $item->size;
        });

        $totals = $summaryGroups->map(fn ($items) => [
            'bags' => $items->sum('quantity'),
            'nwt' => $items->sum(fn ($item) => (float) $item->nwt * (int) $item->quantity),
        ]);

        $logs = $logsQuery->get();

        return view('stock.index', compact(
            'bagGroups', 'bndGroups', 'sqfGroups', 'summaryGroups',
            'extras', 'logs', 'totals', 'fromDate', 'toDate'
        ));
    }

    private function validDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        $selectedDate = \DateTime::createFromFormat('Y-m-d', $date);

        return $selectedDate && $selectedDate->format('Y-m-d') === $date ? $date : null;
    }

    public function update(Request $request, Stock $stock)
    {
        $request->validate([
            'action' => 'required|in:ADD,SUBTRACT',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:200',
        ]);

        $qty = (int) $request->quantity;
        $before = $stock->quantity;

        if ($request->action === 'ADD') {
            $after = $before + $qty;
        } else {
            if ($qty > $before) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot subtract more than available stock ('.$before.').',
                ], 422);
            }
            $after = $before - $qty;
        }

        DB::transaction(function () use ($stock, $request, $qty, $before, $after) {
            $stock->update(['quantity' => $after]);
            StockLog::create([
                'stock_id' => $stock->id,
                'bag_no' => $stock->bag_no,
                'category' => $stock->category,
                'size' => $stock->size,
                'nwt' => $stock->nwt,
                'action' => $request->action,
                'quantity_changed' => $qty,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'note' => $request->note,
                'logged_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Item #{$stock->bag_no} updated: {$request->action} {$qty}",
            'stock' => $stock,
            'quantity_after' => $after,
        ]);
    }

    public function updateNwt(Request $request, Stock $stock)
    {
        $request->validate([
            'nwt' => 'required|numeric|min:0',
        ]);

        $oldNwt = $stock->nwt;
        $newNwt = (float) $request->nwt;

        DB::transaction(function () use ($stock, $newNwt, $oldNwt) {
            $stock->update(['nwt' => $newNwt]);
            
            StockLog::create([
                'stock_id' => $stock->id,
                'bag_no' => $stock->bag_no,
                'category' => $stock->category,
                'size' => $stock->size,
                'nwt' => $newNwt,
                'action' => 'ADD',
                'quantity_changed' => 0,
                'quantity_before' => $stock->quantity,
                'quantity_after' => $stock->quantity,
                'note' => "NWT updated from " . number_format($oldNwt, 2) . " to " . number_format($newNwt, 2),
                'logged_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Item #{$stock->bag_no} NWT updated to {$newNwt}",
            'new_nwt' => $newNwt,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|in:bag,bnd,sqf,other',
            'bag_no' => 'required_if:category,bag|nullable|string|max:50',
            'size' => 'required|string|max:50',
            'nwt' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ]);

        if (in_array($request->category, ['bag', 'bnd', 'sqf', 'other']) && !empty($request->bag_no)) {
            $exists = Stock::where('category', $request->category)
                ->where('bag_no', $request->bag_no)
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => "Error: ".strtoupper($request->category)." #{$request->bag_no} already exists.",
                ], 422);
            }
        }

        $stock = Stock::create($request->only(['category', 'bag_no', 'size', 'nwt', 'quantity', 'extra_type', 'extra_ply', 'extra_mm']));

        StockLog::create([
            'stock_id' => $stock->id,
            'bag_no' => $stock->bag_no,
            'category' => $stock->category,
            'size' => $stock->size,
            'nwt' => $stock->nwt,
            'action' => 'ADD',
            'quantity_changed' => $stock->quantity,
            'quantity_before' => 0,
            'quantity_after' => $stock->quantity,
            'note' => 'New item added',
            'logged_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "New item added: Item #{$stock->bag_no}",
            'stock' => $stock,
        ]);
    }

    public function destroy(Request $request, Stock $stock)
    {
        $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $bagNo = $stock->bag_no;
        $note = trim((string) $request->input('note', ''));
        if ($note === '') {
            $note = 'Item dispatched / deleted';
        }

        DB::transaction(function () use ($stock, $note) {
            StockLog::create([
                'stock_id' => $stock->id,
                'bag_no' => $stock->bag_no,
                'category' => $stock->category,
                'size' => $stock->size,
                'nwt' => $stock->nwt,
                'action' => 'DISPATCH',
                'quantity_changed' => $stock->quantity,
                'quantity_before' => $stock->quantity,
                'quantity_after' => 0,
                'note' => $note,
                'logged_at' => now(),
            ]);

            $stock->delete();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Item #{$bagNo} deleted.",
                'stock_id' => $stock->id,
                'note' => $note,
            ]);
        }

        return redirect()->route('stock.index')->with('success', "Item #{$bagNo} deleted.");
    }
}
