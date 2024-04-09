<div class="row mb-3">
    <div class="col-md-8 mx-auto">
        <label class="col-md-3 text-md-start">{{ $localizedLabel }}</label><br>
        <img class="img-fluid" src="/admin-customer/{{ $customerID }}/get-customer-img-field/{{ $fieldID ?? '-1' }}" alt="{{ $localizedLabel }}">
    </div>
    <div class="col-md-4">
        <label for="{{ $fieldName }}_status" class="col-md-3 col-form-label text-md-start">{{ __('Status') }}</label>
        <div class="col-md-12">
            <div class="form-group">
                <div class="col-md-12">
                    <select id="{{ $fieldName }}_status"
                        class="form-control form-select @error($fieldName . '_status') is-invalid @enderror"
                        name="{{ $fieldName }}_status" required>
                        <option value="ACCEPTED"
                            {{ $statusValue == "ACCEPTED" ? "selected" : "" }}>
                            ACCEPTED</option>
                        <option value="PROCESSING"
                            {{ $statusValue == "PROCESSING" ? "selected" : "" }}>
                            PROCESSING</option>
                        <option value="REJECTED"
                            {{ $statusValue == "REJECTED" ? "selected" : "" }}>
                            REJECTED</option>
                        <option value="VERIFICATION_REQUIRED"
                            {{ $statusValue == "VERIFICATION_REQUIRED" ? "selected" : "" }}>
                            VERIFICATION_REQUIRED</option>

                    </select>
                    @error($fieldName . '_status')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>

            </div>
        </div>
    </div>
</div>