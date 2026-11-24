@extends('layouts.app', ['title' => 'Edit PO - ' . $po->po_number])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Edit Purchase Order {{ $po->po_number }}</h1>
            <p style="color: #64748b; margin: 0;">Update agreed unit pricing and procurement instructions before submitting for approval.</p>
        </div>
        <a href="{{ route('po.show', $po) }}" class="btn btn-secondary">Cancel</a>
    </div>

    @if ($errors->any())
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.25rem;">
            @foreach ($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('po.update', $po) }}">
        @csrf
        @method('PUT')

        <div style="margin-bottom: 1.5rem;">
            <label for="notes" style="font-weight: 600; display: block; margin-bottom: 0.35rem;">PO Instructions / Notes</label>
            <input type="text" id="notes" name="notes" value="{{ old('notes', $po->notes) }}" style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>

        <h3>Line Items & Unit Prices</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Item</th>
                    <th>Ordered Qty</th>
                    <th>Current Unit Price (₹)</th>
                    <th style="width: 200px;">New Agreed Unit Price (₹) *</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($po->lines as $idx => $line)
                    <tr>
                        <td>{{ $line->line_number }}</td>
                        <td>
                            <strong>{{ $line->item?->code }} - {{ $line->item?->name }}</strong>
                            <input type="hidden" name="lines[{{ $idx }}][id]" value="{{ $line->id }}">
                        </td>
                        <td>{{ number_format((float) $line->quantity, 3) }} {{ $line->uom_code }}</td>
                        <td>₹ {{ number_format((float) $line->unit_price, 2) }}</td>
                        <td>
                            <input type="number" step="0.01" min="0" name="lines[{{ $idx }}][unit_price]" value="{{ old('lines.'.$idx.'.unit_price', (float) $line->unit_price) }}" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="{{ route('po.show', $po) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn" style="background-color: #2563eb; padding: 0.65rem 1.75rem; font-weight: 600;">
                Update Purchase Order
            </button>
        </div>
    </form>
</div>
@endsection
