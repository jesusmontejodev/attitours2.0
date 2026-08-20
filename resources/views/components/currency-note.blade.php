@props(['usd'])
@if(!empty($exchangeRates) && is_numeric($usd))
    <span {{ $attributes->class(['block text-[10px] font-medium text-slate-400 mt-0.5']) }}>
        &asymp; ${{ number_format($usd * ($exchangeRates['MXN'] ?? 0)) }} MXN &middot; &euro;{{ number_format($usd * ($exchangeRates['EUR'] ?? 0)) }} EUR
    </span>
@endif
