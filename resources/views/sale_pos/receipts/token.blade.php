<div style="width: 100%; text-align: center; font-family: Arial, Helvetica, sans-serif; padding: 8px 0;">
    @if (!empty($business_name))
        <div style="font-size: 16px; font-weight: bold;">{{ $business_name }}</div>
    @endif
    <div style="font-size: 18px; letter-spacing: 2px; margin-top: 10px;">@lang('lang_v1.token')</div>
    <div style="font-size: 64px; font-weight: bold; line-height: 1.1; margin-top: 8px;">{{ $invoice_no }}</div>
</div>
