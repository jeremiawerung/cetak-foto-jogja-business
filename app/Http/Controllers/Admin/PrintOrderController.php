<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrintOrder;
use App\Models\PrintOrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class PrintOrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $orders = PrintOrder::withCount('items')
            ->when($status && array_key_exists($status, PrintOrder::STATUSES), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.order-cetak-foto.index', [
            'orders' => $orders,
            'statusTerpilih' => $status,
        ]);
    }

    public function show(PrintOrder $printOrder): View
    {
        $printOrder->load('items');

        return view('admin.order-cetak-foto.show', compact('printOrder'));
    }

    public function updateStatus(Request $request, PrintOrder $printOrder): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(PrintOrder::STATUSES))],
            'resi' => ['nullable', 'string', 'max:100'],
        ]);

        $printOrder->update($validated);

        return back()->with('status', 'Status order berhasil diperbarui.');
    }

    public function updatePembayaran(Request $request, PrintOrder $printOrder): RedirectResponse
    {
        $validated = $request->validate([
            'status_pembayaran' => ['required', Rule::in(array_keys(PrintOrder::STATUS_PEMBAYARAN))],
        ]);

        $printOrder->update($validated);

        return back()->with('status', 'Status pembayaran berhasil diperbarui.');
    }

    public function buktiTransfer(PrintOrder $printOrder): Response
    {
        abort_if(! $printOrder->bukti_transfer || ! Storage::exists($printOrder->bukti_transfer), 404);

        return Storage::response($printOrder->bukti_transfer);
    }

    public function label(PrintOrder $printOrder): View|RedirectResponse
    {
        if ($printOrder->metode_ambil !== 'dikirim') {
            return redirect()->route('admin.order-cetak-foto.show', $printOrder)
                ->with('status', 'Label cuma untuk order dengan metode pengambilan "Dikirim".');
        }

        $printOrder->loadMissing('items');

        return view('admin.order-cetak-foto.label', [
            'printOrder' => $printOrder,
            'toko' => config('services.toko'),
        ]);
    }

    public function downloadFile(PrintOrder $printOrder, PrintOrderItem $item, int $index): Response
    {
        abort_unless($item->print_order_id === $printOrder->id, 404);

        $path = $item->file_paths[$index] ?? null;

        abort_if(! $path || ! Storage::exists($path), 404);

        return Storage::download($path);
    }

    public function downloadAll(PrintOrder $printOrder): StreamedResponse
    {
        $printOrder->loadMissing('items');
        $adaFile = $printOrder->items->contains(fn ($item) => ! empty($item->file_paths));

        abort_if(! $adaFile, 404);

        $zipRelativePath = 'zip-sementara/order-'.$printOrder->id.'-'.Str::random(8).'.zip';
        Storage::makeDirectory('zip-sementara');
        $zipFullPath = Storage::path($zipRelativePath);

        $zip = new ZipArchive();
        $zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($printOrder->items as $itemIndex => $item) {
            foreach ((array) $item->file_paths as $fileIndex => $path) {
                if (Storage::exists($path)) {
                    $zip->addFile(Storage::path($path), 'item'.($itemIndex + 1).'-'.($fileIndex + 1).'-'.basename($path));
                }
            }
        }

        $zip->close();

        return response()->streamDownload(function () use ($zipFullPath) {
            readfile($zipFullPath);
            @unlink($zipFullPath);
        }, "order-{$printOrder->id}-foto.zip");
    }
}
