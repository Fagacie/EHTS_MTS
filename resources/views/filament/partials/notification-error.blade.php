<div class="space-y-3 text-sm text-slate-700">
    <div>
        <p class="font-semibold text-slate-900">Recipient</p>
        <p>{{ $record->recipient_email }}</p>
    </div>
    <div>
        <p class="font-semibold text-slate-900">Delivery Key</p>
        <p class="font-mono text-xs">{{ $record->delivery_key }}</p>
    </div>
    <div>
        <p class="font-semibold text-slate-900">Failure Message</p>
        <pre class="mt-1 whitespace-pre-wrap rounded-lg bg-rose-50 p-3 font-mono text-xs text-rose-800">{{ $record->error_message }}</pre>
    </div>
</div>
