@extends('layouts.app', ['title' => 'Edit Purchase Requisition - ' . $pr->pr_number])

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="margin-bottom: 0.25rem;">Edit Requisition: {{ $pr->pr_number }}</h1>
            <p style="color: #64748b; margin: 0;">Status: <strong>DRAFT</strong></p>
        </div>
        <a href="{{ route('pr.show', $pr) }}" class="btn btn-secondary">Cancel</a>
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

    <form method="POST" action="{{ route('pr.update', $pr) }}">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Cost Centre *</label>
                <select name="cost_centre_id" required style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                    @foreach ($costCentres as $cc)
                        <option value="{{ $cc->id }}" {{ old('cost_centre_id', $pr->cost_centre_id) == $cc->id ? 'selected' : '' }}>
                            {{ $cc->code }} - {{ $cc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 0.4rem;">Requisition Notes</label>
                <input type="text" name="notes" value="{{ old('notes', $pr->notes) }}" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px;">
            </div>
        </div>

        <h3 style="margin-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem;">Line Items</h3>

        <div id="lines-container">
            @foreach ($pr->lines as $index => $line)
                <div class="line-row" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem; margin-bottom: 1rem;">
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 600;">Item *</label>
                            <select name="lines[{{ $index }}][item_id]" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}" {{ $line->item_id == $item->id ? 'selected' : '' }}>
                                        [{{ $item->category->value }}] {{ $item->code }} - {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 600;">Quantity *</label>
                            <input type="number" step="0.001" min="0.001" name="lines[{{ $index }}][quantity]" value="{{ old("lines.{$index}.quantity", (float)$line->quantity) }}" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 600;">UOM *</label>
                            <select name="lines[{{ $index }}][uom_code]" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->code }}" {{ $line->uom_code == $uom->code ? 'selected' : '' }}>{{ $uom->code }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 600;">Est. Unit Price (₹) *</label>
                            <input type="number" step="0.01" min="0" name="lines[{{ $index }}][estimated_unit_price]" value="{{ old("lines.{$index}.estimated_unit_price", (float)$line->estimated_unit_price) }}" required style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                        </div>
                    </div>

                    <div style="margin-top: 0.5rem;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600;">Line Specification</label>
                        <input type="text" name="lines[{{ $index }}][description]" value="{{ old("lines.{$index}.description", $line->description) }}" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn" style="padding: 0.75rem 1.5rem; font-size: 1rem;">Update Draft</button>
    </form>
</div>
@endsection
