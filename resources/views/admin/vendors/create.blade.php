<x-layouts.admin title="Add new vendor" active="vendors">
    <div class="form-back">
        <a href="{{ route('dashboard') }}">
            ← Back to Vendors
        </a>
    </div>

    <div class="page-heading vendor-form-heading">
        <div>
            <h1>
                Add New Vendor
            </h1>
            <p>
                Enter the details below to register a new vendor and assign a stall within the public market.<br>
                Ensure all required fields are completed.
            </p>
        </div>
    </div>

    @if ($errors->any())
        <div class="form-alert portal-alert">
            Please check the highlighted details and try again.
        </div>
    @endif

    <form method="POST" action="{{ route('vendors.store') }}" class="vendor-form" enctype="multipart/form-data">
        @csrf

        <section class="vendor-panel vendor-details">
            <h2>
                Vendor Details
            </h2>

            <div class="form-field full">
                <label for="name">
                    Full legal name
                </label>
                <input id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Jane Doe" required>
            </div>

            <div class="form-grid">
                <div class="form-field">
                    <label for="contact_number">
                        Contact number
                    </label>
                    <input id="contact_number" name="contact_number" value="{{ old('contact_number') }}" placeholder="(555) 000-0000">
                </div>

                <div class="form-field">
                    <label for="email">
                        Email address
                    </label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="vendor@example.com">
                </div>
            </div>

            <div class="form-field full">
                <label for="residential_address">
                    Residential address
                </label>
                <textarea id="residential_address" name="residential_address" placeholder="Enter full address">{{ old('residential_address') }}</textarea>
            </div>

            <div class="form-field full">
                <label for="photo">
                    Vendor photo (ID)
                </label>
                <label class="upload-box" for="photo">
                    <span class="upload-symbol">
                        ↥
                    </span>
                    <strong>
                        Click to upload or drag and drop
                    </strong>
                    <small>
                        SVG, PNG, JPG or GIF (max. 5MB)
                    </small>
                    <input id="photo" name="photo" type="file" accept="image/*">
                </label>
            </div>
        </section>

        <div class="vendor-side-panels">
            <section class="vendor-panel">
                <h2>
                    Stall Details
                </h2>

                <div class="form-field">
                    <label for="market_section">
                        Market section
                    </label>
                    <select id="market_section" name="market_section" required>
                        <option value="">
                            Select a section
                        </option>
                        <option {{ old('market_section') === 'Fresh Produce' ? 'selected' : '' }}>
                            Fresh Produce
                        </option>
                        <option {{ old('market_section') === 'Dry Goods' ? 'selected' : '' }}>
                            Dry Goods
                        </option>
                        <option {{ old('market_section') === 'Food Court' ? 'selected' : '' }}>
                            Food Court
                        </option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="stall_number">
                        Stall number
                    </label>
                    <select id="stall_number" name="stall_number" required>
                        <option value="">
                            Select stall
                        </option>
                        <option>
                            A-14
                        </option>
                        <option>
                            B-07
                        </option>
                        <option>
                            C-22
                        </option>
                        <option>
                            D-03
                        </option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="monthly_rent">
                        Monthly rental rate
                    </label>
                    <div class="money-input">
                        <span>
                            $
                        </span>
                        <input id="monthly_rent" name="monthly_rent" type="number" step="0.01" min="0" value="{{ old('monthly_rent', '450.00') }}" required>
                    </div>
                    <small class="field-help">
                        *Rate is auto-calculated based on stall selection.
                    </small>
                </div>
            </section>

            <section class="vendor-panel contract-panel">
                <h2>
                    Contract Terms
                </h2>

                <div class="form-field">
                    <label>
                        Billing cycle
                    </label>
                    <div class="radio-row">
                        <label>
                            <input type="radio" name="billing_cycle" value="Monthly" checked> 
                            Monthly
                        </label>
                        <label>
                            <input type="radio" name="billing_cycle" value="Quarterly"> 
                            Quarterly
                        </label>
                    </div>
                </div>

                <div class="form-field">
                    <label for="contract_start_date">
                        Contract start date
                    </label>
                    <input id="contract_start_date" name="contract_start_date" type="date" value="{{ old('contract_start_date') }}" required>
                </div>

                <div class="form-field">
                    <label for="contract_end_date">
                        Contract end date
                    </label>
                    <input id="contract_end_date" name="contract_end_date" type="date" value="{{ old('contract_end_date') }}" required>
                </div>

                <input type="hidden" name="status" value="Active">
            </section>
        </div>

        <div class="vendor-form-actions">
            <a href="{{ route('dashboard') }}">
                Cancel
            </a>
            <button class="black-button" type="submit">
                Save Vendor 
                <span>
                    →
                </span>
            </button>
        </div>
    </form>
</x-layouts.admin>
