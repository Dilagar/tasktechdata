@extends('layouts.app', ['title' => 'Select Approved PR Lines'])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Convert Approved PR Lines to Purchase Order</h1>
            <p style="color: #64748b; margin: 0;">Step 1: Select approved requisition lines that have remaining order quantities.</p>
        </div>
        <a href="{{ route('po.index') }}" class="btn btn-secondary">Cancel</a>
    </div>

    @if ($errors->any())
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.25rem;">
            @foreach ($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('po.configure') }}">
        @csrf
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">Select</th>
                    <th>PR Number</th>
                    <th>Cost Centre</th>
                    <th>Item</th>
                    <th>Approved Qty</th>
                    <th>Already Ordered</th>
                    <th>Remaining Qty</th>
                    <th>Estimated Price</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($prLines as $line)
                    @php
                        $alreadyOrdered = (string) $line->poPrLines->sum('quantity');
                        $remaining = bcsub((string) $line->quantity, $alreadyOrdered, 3);
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox" name="selected_lines[]" value="{{ $line->id }}" style="width: 18px; height: 18px; cursor: pointer;">
                        </td>
                        <td>
                            <strong>{{ $line->purchaseRequisition->pr_number }}</strong>
                            <div style="font-size: 0.75rem; color: #64748b;">Req: {{ $line->purchaseRequisition->requester?->name }}</div>
                        </td>
                        <td>{{ $line->purchaseRequisition->costCentre?->code }}</td>
                        <td>
                            <strong>{{ $line->item->code }} - {{ $line->item->name }}</strong>
                            <div style="font-size: 0.75rem; color: #64748b;">Category: {{ $line->item->category?->value }} | GST: {{ $line->item->gst_rate }}%</div>
                        </td>
                        <td>{{ number_format((float) $line->quantity, 3) }} {{ $line->uom_code }}</td>
                        <td>{{ number_format((float) $alreadyOrdered, 3) }} {{ $line->uom_code }}</td>
                        <td>
                            <strong style="color: #166534;">{{ number_format((float) $remaining, 3) }} {{ $line->uom_code }}</strong>
                        </td>
                        <td>₹ {{ number_format((float) $line->estimated_unit_price, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 2rem;">
                            No approved PR lines with remaining quantity available for conversion.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($prLines->isNotEmpty())
            <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn" style="background-color: #2563eb; padding: 0.65rem 1.5rem;">
                    Next: Select Vendor & Pricing →
                </button>
            </div>
        @endif
    </form>
</div>
@endsection
