<x-layouts.admin title="Edit Vendor" active="vendors">
    <div class="form-back"><a href="{{ route('vendors.show', $vendor) }}">← Back to vendor account</a></div>
    <section class="page-heading"><h1>Edit Vendor Details</h1><p>Update the vendor profile and their linked stall and rental information.</p></section>
    @if ($errors->any())<div class="form-alert">Please correct the highlighted fields.</div>@endif
    <section class="form-panel max-w-4xl"><form class="portal-form" method="POST" action="{{ route('vendors.update', $vendor) }}">@csrf @method('PUT')
        <div class="form-grid"><label class="field">Full name<input name="name" value="{{ old('name', $vendor->name) }}" required></label><label class="field">Email<input name="email" type="email" value="{{ old('email', $vendor->email) }}"></label></div>
        <div class="form-grid"><label class="field">Contact number<input name="contact_number" value="{{ old('contact_number', $vendor->contact_number) }}"></label><label class="field">Status<select name="status"><option value="Active" @selected(old('status', $vendor->status) === 'Active')>Active</option><option value="Inactive" @selected(old('status', $vendor->status) === 'Inactive')>Inactive</option></select></label></div>
        <label class="field">Residential address<textarea name="residential_address">{{ old('residential_address', $vendor->residential_address) }}</textarea></label>
        <div class="form-grid">
            <label class="field">Linked stall number
                <select name="stall_number" id="stall_number" required>
                    <option value="">Select available stall</option>
                    @if ($vendor->stall_number && ! $stalls->contains('stall_number', $vendor->stall_number))
                        <option value="{{ $vendor->stall_number }}"
                            data-section="{{ $vendor->market_section }}"
                            data-rate="{{ $vendor->monthly_rent }}"
                            selected>
                            {{ $vendor->stall_number }} (Current stall)
                        </option>
                    @endif
                    @foreach ($stalls as $stall)
                        <option value="{{ $stall->stall_number }}"
                            data-section="{{ $stall->market_section }}"
                            data-rate="{{ $stall->monthly_rate }}"
                            @selected(old('stall_number', $vendor->stall_number) === $stall->stall_number)>
                            {{ $stall->stall_number }} - {{ $stall->market_section }} ({{ $stall->stall_number === $vendor->stall_number ? 'Current' : 'Available' }})
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="field">Market section
                <input name="market_section" id="market_section" value="{{ old('market_section', $vendor->market_section) }}" required>
            </label>
        </div>
        <div class="form-grid">
            <label class="field">Rental rate
                <input name="monthly_rent" id="monthly_rent" type="number" min="0" step="0.01" value="{{ old('monthly_rent', $vendor->monthly_rent) }}" required>
            </label>
            <label class="field">Billing cycle
                <select name="billing_cycle">
                    <option value="Monthly" @selected(old('billing_cycle', $vendor->billing_cycle) === 'Monthly')>Monthly</option>
                    <option value="Quarterly" @selected(old('billing_cycle', $vendor->billing_cycle) === 'Quarterly')>Quarterly</option>
                </select>
            </label>
        </div>
        <div class="form-grid"><label class="field">Contract start<input name="contract_start_date" type="date" value="{{ old('contract_start_date', $vendor->contract_start_date?->toDateString()) }}" required></label><label class="field">Contract end<input name="contract_end_date" type="date" value="{{ old('contract_end_date', $vendor->contract_end_date?->toDateString()) }}" required></label></div>
        <div class="form-actions"><a href="{{ route('vendors.show', $vendor) }}">Cancel</a><button class="black-button" type="submit">Save changes</button></div>
    </form></section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const stallSelect = document.getElementById('stall_number');
            const marketSectionInput = document.getElementById('market_section');
            const monthlyRentInput = document.getElementById('monthly_rent');

            if (stallSelect) {
                stallSelect.addEventListener('change', function () {
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption && selectedOption.dataset) {
                        if (selectedOption.dataset.section && marketSectionInput) {
                            marketSectionInput.value = selectedOption.dataset.section;
                        }
                        if (selectedOption.dataset.rate && monthlyRentInput) {
                            monthlyRentInput.value = parseFloat(selectedOption.dataset.rate).toFixed(2);
                        }
                    }
                });
            }
        });
    </script>
</x-layouts.admin>
