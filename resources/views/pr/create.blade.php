@extends('layouts.app', ['title' => 'Raise Purchase Requisition'])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Raise Purchase Requisition</h1>
            <p style="color: #64748b; margin: 0;">Create a new PR in DRAFT status.</p>
        </div>
        <a href="{{ route('pr.index') }}" class="btn btn-secondary">Back to List</a>
    </div>

    @if ($errors->any())
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.25rem;">
            <ul style="margin: 0; padding-left: 1.25rem;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('pr.store') }}">
        @csrf

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Cost Centre *</label>
                <select name="cost_centre_id" required style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                    <option value="">-- Select Department Cost Centre --</option>
                    @foreach ($costCentres as $cc)
                        <option value="{{ $cc->id }}" {{ old('cost_centre_id') == $cc->id ? 'selected' : '' }}>
                            {{ $cc->code }} - {{ $cc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Requisition Notes</label>
                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="e.g. Q3 Hardware upgrade" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px;">
            </div>
        </div>

        <h3 style="margin-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem;">Line Items</h3>

        <div id="lines-container">
            <!-- Line 1 -->
            <div class="line-row" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; margin-bottom: 1rem;">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr; gap: 1rem; align-items: start;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600;">Item *</label>
                        <select name="lines[0][item_id]" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">
                                    [{{ $item->category->value }}] {{ $item->code }} - {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600;">Quantity *</label>
                        <input type="number" step="0.001" min="0.001" id="pr-qty-0" name="lines[0][quantity]" value="{{ old('lines.0.quantity', '1') }}" required oninput="calcLineTotal(0)" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600;">UOM *</label>
                        <select name="lines[0][uom_code]" id="pr-uom-0" required onchange="updatePriceLabel(0); calcLineTotal(0);" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                            @foreach ($uoms as $uom)
                                <option value="{{ $uom->code }}">{{ $uom->code }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label id="pr-price-label-0" style="display: block; font-size: 0.85rem; font-weight: 600;">Price per PCS (₹) *</label>
                        <input type="number" step="0.01" min="0" id="pr-price-0" name="lines[0][estimated_unit_price]" value="{{ old('lines.0.estimated_unit_price', '12500.00') }}" required oninput="calcLineTotal(0)" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600;">Est. Total (₹)</label>
                        <div id="pr-line-total-0" style="padding: 0.5rem; background: #e2e8f0; border-radius: 4px; font-weight: 700; color: #0f172a; text-align: right;">₹ 12,500.00</div>
                    </div>
                </div>

                <div style="margin-top: 0.5rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600;">Line Specification / Justification</label>
                    <input type="text" name="lines[0][description]" placeholder="e.g. Standard configuration" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                </div>
            </div>
        </div>

        <button type="submit" class="btn" style="padding: 0.75rem 1.5rem; font-size: 1rem;">Save as Draft</button>
    </form>
</div>

<script>
    function updatePriceLabel(idx) {
        const uomSelect = document.getElementById(`pr-uom-${idx}`);
        const label = document.getElementById(`pr-price-label-${idx}`);
        if (uomSelect && label) {
            const uom = uomSelect.value || 'Unit';
            label.textContent = `Price per ${uom} (₹) *`;
        }
    }

    function calcLineTotal(idx) {
        const qtyInput = document.getElementById(`pr-qty-${idx}`);
        const priceInput = document.getElementById(`pr-price-${idx}`);
        const totalDiv = document.getElementById(`pr-line-total-${idx}`);
        if (qtyInput && priceInput && totalDiv) {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const total = qty * price;
            totalDiv.textContent = '₹ ' + total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updatePriceLabel(0);
        calcLineTotal(0);
    });
</script>
</div>
@endsection
