@extends('master.back')

@section('content')

<div class="container-fluid">

	<!-- Page Heading -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-sm-flex align-items-center justify-content-between">
                <h3 class=" mb-0 bc-title"> <b>{{ __('SMS Setting') }}</b> </h3>
            </div>
        </div>
    </div>

	<!-- Form -->
	<div class="row">
		<div class="col-xl-12 col-lg-12 col-md-12">
			<div class="card o-hidden border-0 shadow-lg">
				<div class="card-body ">
					<!-- Nested Row within Card Body -->
					<div class="row">
						<div class="col-lg-12">
							<div class="p-5">
								@include('alerts.alerts')

                                <div class="container pl-0 pr-0 ml-0 mr-0 w-100 mw-100">
                                    <div id="tabs">
                                        <ul class="nav nav-pills nav-secondary nav-justified mb-3" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" data-toggle="pill" href="#conf">{{ __('Configuration') }}</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-toggle="pill" href="#template">{{ __('SMS Section') }}</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-toggle="pill" href="#test-sms">{{ __('Test SMS') }}</a>
                                            </li>
                                        </ul>

                                        <!-- Tab panes -->
                                        <div class="tab-content">
                                            <div id="conf" class="container tab-pane active"><br>
                                                <div class="row justify-content-center">
                                                    <div class="col-lg-8">
                                                        <form action="{{ route('back.sms.update') }}" method="POST" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="form-group">
                                                                <label class="switch-primary">
                                                                    <input type="checkbox" class="switch switch-bootstrap status radio-check" name="is_twilio" value="1" {{ $setting->is_twilio == 1 ? 'checked' : '' }}>
                                                                    <span class="switch-body"></span>
                                                                    <span class="switch-text">{{ __('Enable SMS Notifications') }}</span>
                                                                </label>
                                                            </div>

                                                            <div class="radio-show {{ $setting->is_twilio == 0 ? 'd-none' : '' }}">
                                                                <div class="form-group">
                                                                    <label for="sms_gateway">{{ __('SMS Gateway') }}</label>
                                                                    <select name="sms_gateway" id="sms_gateway" class="form-control">
                                                                        <option value="automas" {{ ($setting->sms_gateway ?? 'automas') == 'automas' ? 'selected' : '' }}>{{ __('Automas SMS Gateway (Recommended)') }}</option>
                                                                        <option value="custom" {{ ($setting->sms_gateway ?? 'automas') == 'custom' ? 'selected' : '' }}>{{ __('Custom / Universal SMS URL') }}</option>
                                                                    </select>
                                                                </div>

                                                                <div id="automas_fields" class="{{ ($setting->sms_gateway ?? 'automas') == 'automas' ? '' : 'd-none' }}">
                                                                    <div class="alert alert-info">
                                                                        <i class="fas fa-info-circle"></i> {{ __('To get your API Key and Sender ID, visit') }} <a href="https://sms.automas.com.bd/api" target="_blank" class="text-dark font-weight-bold">Automas SMS API Documentation</a>.
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="automas_api_key">{{ __('Automas API Key') }}</label>
                                                                        <input type="text" class="form-control" id="automas_api_key" name="automas_api_key" placeholder="{{ __('Enter Automas API Key') }}" value="{{ $setting->automas_api_key ?? '' }}">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="automas_sender_id">{{ __('Approved Sender ID') }}</label>
                                                                        <input type="text" class="form-control" id="automas_sender_id" name="automas_sender_id" placeholder="{{ __('Enter Approved Sender ID') }}" value="{{ $setting->automas_sender_id ?? '' }}">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="automas_type">{{ __('SMS Encoding Type') }}</label>
                                                                        <select name="automas_type" id="automas_type" class="form-control">
                                                                            <option value="auto" {{ ($setting->automas_type ?? 'auto') == 'auto' ? 'selected' : '' }}>{{ __('Auto-detect Unicode/Bengali (Recommended)') }}</option>
                                                                            <option value="unicode" {{ ($setting->automas_type ?? 'auto') == 'unicode' ? 'selected' : '' }}>{{ __('Unicode (type=8 for Bengali)') }}</option>
                                                                            <option value="text" {{ ($setting->automas_type ?? 'auto') == 'text' ? 'selected' : '' }}>{{ __('Standard Text (ASCII)') }}</option>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <div id="custom_fields" class="{{ ($setting->sms_gateway ?? 'automas') == 'custom' ? '' : 'd-none' }}">
                                                                    <div class="form-group">
                                                                        <label for="sms_url">{{ __('Universal SMS API URL') }}</label>
                                                                        <input type="text" class="form-control" id="sms_url" name="sms_url" placeholder="{{ __('e.g., http://api.sms.com/send?to={number}&msg={message}') }}" value="{{ $setting->sms_url ?? '' }}">
                                                                        <small class="form-text text-muted">{{ __('Use {number} for the recipient phone number and {message} for the SMS content.') }}</small>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="form-group d-flex justify-content-center">
                                                                <button type="submit" class="btn btn-secondary btn-block w-100">{{ __('Save Configuration') }}</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="template" class="container tab-pane"><br>
                                                <div class="row justify-content-center">
                                                    <div class="col-lg-8">
                                                        <form action="{{ route('back.sms.update') }}" method="POST" enctype="multipart/form-data">
                                                            @csrf
                                                            @php
                                                                $sms_section = json_decode($setting->twilio_section,true) ?? [];
                                                            @endphp
                                                            <div class="alert alert-info">
                                                                <strong>{{ __('Available Tags:') }}</strong>
                                                                <ul class="mb-0 mt-2">
                                                                    <li><code>{order_number}</code> - {{ __('Order Number') }}</li>
                                                                    <li><code>{order_amount}</code> - {{ __('Total Order Price') }}</li>
                                                                    <li><code>{order_date}</code> - {{ __('Order Date') }}</li>
                                                                    <li><code>{payment_method}</code> - {{ __('Payment Method used (e.g. Stripe, Cash On Delivery)') }}</li>
                                                                    <li><code>{customer_name}</code>, <code>{customer_phone}</code>, <code>{customer_address}</code> - {{ __('Customer Info (Merchant SMS)') }}</li>
                                                                    <li><code>{order_items}</code> - {{ __('List of items (Merchant SMS)') }}</li>
                                                                </ul>
                                                            </div>
                                                            
                                                            <div class="form-group ">
                                                                <label for="order_purchase">{{ __('Customer Order Confirmation') }}</label>
                                                                <textarea name="twilio_section['purchase']" class="form-control" id="order_purchase" placeholder="{{__('Enter Message')}}">{{$sms_section["'purchase'"] ?? ($sms_section["purchase"] ?? '')}}</textarea>
                                                            </div>

                                                            <div class="form-group ">
                                                                <label for="order_status">{{ __('Customer Order Status Update') }}</label>
                                                                <textarea name="twilio_section['order_status']" class="form-control" id="order_status" placeholder="{{__('Enter Message')}}">{{$sms_section["'order_status'"] ?? ($sms_section["order_status"] ?? '')}}</textarea>
                                                            </div>

                                                            <div class="form-group ">
                                                                <label for="merchant_purchase">{{ __('Merchant Notification (New Order)') }}</label>
                                                                <textarea name="twilio_section['merchant_purchase']" class="form-control" id="merchant_purchase" placeholder="{{__('Enter Message')}}">{{$sms_section["'merchant_purchase'"] ?? ($sms_section["merchant_purchase"] ?? '')}}</textarea>
                                                            </div>

                                                            <div class="form-group d-flex justify-content-center">
                                                                <button type="submit" class="btn btn-secondary btn-block w-100">{{ __('Save Templates') }}</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="test-sms" class="container tab-pane"><br>
                                                <div class="row justify-content-center">
                                                    <div class="col-lg-8">
                                                        <form action="{{ route('back.sms.test') }}" method="POST" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle"></i> {{ __('Note: The Test SMS tool requires the Automas SMS Gateway to be configured and selected.') }}
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="test_phone">{{ __('Recipient Mobile Number') }}</label>
                                                                <input type="text" class="form-control" id="test_phone" name="test_phone" placeholder="{{ __('e.g., 017XXXXXXXX') }}" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="test_message">{{ __('Test Message') }}</label>
                                                                <textarea name="test_message" class="form-control" id="test_message" rows="3" placeholder="{{__('Enter a test message here...')}}" required>This is a test message from {{ $setting->title }}.</textarea>
                                                            </div>
                                                            <div class="form-group d-flex justify-content-center">
                                                                <button type="submit" class="btn btn-primary btn-block w-100"><i class="fas fa-paper-plane"></i> {{ __('Send Test SMS') }}</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#sms_gateway').on('change', function() {
            var val = $(this).val();
            if (val == 'automas') {
                $('#automas_fields').removeClass('d-none');
                $('#custom_fields').addClass('d-none');
            } else {
                $('#automas_fields').addClass('d-none');
                $('#custom_fields').removeClass('d-none');
            }
        });
    });
</script>
@endsection
