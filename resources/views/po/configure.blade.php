@extends('layouts.app', ['title' => 'Configure Purchase Order'])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Configure Purchase Order</h1>
            <p style="color: #64748b; margin: 0;">Step 2: Choose active vendor, verify agreed pricing and order quantities.</p>
        </div>
        <a href="{{ route('po.create-from-pr') }}" class="btn btn-secondary">← Back to Line Selection</a>
    </div>

    @if ($errors->any())
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.25rem;">
            @foreach ($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('po.store') }}">
        @csrf

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
            <div class="form-group">
                <label for="vendor_id" style="font-weight: 600; display: block; margin-bottom: 0.35rem;">Target Vendor *</label>
                <select id="vendor_id" name="vendor_id" required style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="">-- Choose Active Vendor --</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}">
                            {{ $vendor->code }} - {{ $vendor->name }} ({{ $vendor->state }} | GSTIN: {{ $vendor->gstin }})
                        </option>
                    @endforeach
                </select>
                <small style="color: #64748b;">GST (Intra vs Inter-state) will be calculated based on Vendor State vs Company State.</small>
            </div>

            <div class="form-group">
                <label for="notes" style="font-weight: 600; display: block; margin-bottom: 0.35rem;">PO Notes / Instructions</label>
                <input type="text" id="notes" name="notes" placeholder="e.g. Delivery required within 15 business days" style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
        </div>

        <h3>Selected Items for Conversion</h3>
        <table>
            <thead>
                <tr>
                    <th>PR Ref</th>
                    <th>Item</th>
                    <th>Remaining Approved</th>
                    <th style="width: 160px;">Order Qty *</th>
                    <th style="width: 180px;">Agreed Unit Price (₹) *</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($selectedLines as $idx => $line)
                    @php
                        $alreadyOrdered = (string) $line->poPrLines->sum('quantity');
                        $remaining = bcsub((string) $line->quantity, $alreadyOrdered, 3);
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $line->purchaseRequisition->pr_number }}</strong>
                            <div style="font-size: 0.75rem; color: #64748b;">Cost Centre: {{ $line->purchaseRequisition->costCentre?->code }}</div>
                            <input type="hidden" name="lines[{{ $idx }}][pr_line_id]" value="{{ $line->id }}">
                            <input type="hidden" name="lines[{{ $idx }}][uom_code]" value="{{ $line->uom_code }}">
                        </td>
                        <td>
                            <strong>{{ $line->item->code }} - {{ $line->item->name }}</strong>
                            <div style="font-size: 0.75rem; color: #64748b;">Base UoM: {{ $line->item->base_uom_code }} | GST: {{ $line->item->gst_rate }}%</div>
                        </td>
                        <td>
                            <strong>{{ number_format((float) $remaining, 3) }} {{ $line->uom_code }}</strong>
                        </td>
                        <td>
                            <input type="number" step="0.001" min="0.001" max="{{ (float) $remaining }}" name="lines[{{ $idx }}][order_qty]" value="{{ (float) $remaining }}" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="lines[{{ $idx }}][unit_price]" value="{{ (float) $line->estimated_unit_price }}" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="{{ route('po.create-from-pr') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn" style="background-color: #16a34a; padding: 0.65rem 1.75rem; font-weight: 600;">
                Generate Purchase Order (Draft)
            </button>
        </div>
    </form>
</div>
@endsection
