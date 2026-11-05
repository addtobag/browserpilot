@php
    $status = $session->status();
    $label = match ($status) {
        'needs_human' => 'Needs you',
        'ended' => 'Finished',
        default => 'Agent is working',
    };
    $chip = match ($status) {
        'needs_human' => ['#ECEDFF', '#3B3D99', '#8E92F0'],
        'ended' => ['#F1F0F7', '#5D5B6B', '#B9B7CC'],
        default => ['#E3F6EA', '#1F6B3D', '#4CC07A'],
    };
    $btn = 'display:inline-flex;align-items:center;height:44px;padding:0 20px;border-radius:22px;border:0;font:inherit;font-size:15px;font-weight:800;cursor:pointer;';
@endphp

<div wire:poll.{{ $poll }} style="font-family:'Nunito',ui-rounded,ui-sans-serif,system-ui,sans-serif;color:#23222E;border-radius:28px;background:#FFFFFF;box-shadow:0 1px 2px rgba(35,34,60,.04),0 6px 20px rgba(35,34,60,.05);overflow:hidden">
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid #F0EFF6">
        <span style="display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:16px;background:{{ $chip[0] }};color:{{ $chip[1] }};font-size:13px;font-weight:800">
            <span aria-hidden="true" style="width:8px;height:8px;border-radius:50%;background:{{ $chip[2] }}"></span>
            {{ $label }}
        </span>

        @if ($session->needsHuman() && $session->handoffReason())
            <span style="flex:1 1 240px;min-width:0;font-size:15px;font-weight:700;color:#3A3948">{{ $session->handoffReason() }}</span>
        @else
            <span style="flex:1 1 240px"></span>
        @endif

        @if ($controls && ! $session->hasEnded())
            <div style="display:flex;gap:8px">
                @if ($session->needsHuman())
                    <button type="button" wire:click="handBack" style="{{ $btn }}background:#C7CBFF;color:#23246B">I'm done, hand back</button>
                @else
                    <button type="button" wire:click="takeOver" style="{{ $btn }}background:#F3F2F8;color:#23222E">Take over</button>
                @endif
                <button type="button" wire:click="end" wire:confirm="End this browser session?" style="{{ $btn }}background:#FFFFFF;color:#A33232;box-shadow:inset 0 0 0 1.5px #F3D4D4">End</button>
            </div>
        @endif
    </div>

    <div style="position:relative;background:#F6F5FB;height:{{ $height }}">
        @if ($session->hasEnded())
            <div style="height:100%;display:flex;align-items:center;justify-content:center;color:#6A6878;font-size:16px;font-weight:700">This browser session has finished.</div>
        @elseif ($session->liveViewUrl())
            <iframe
                src="{{ $session->liveViewUrl() }}"
                title="Live browser"
                sandbox="allow-same-origin allow-scripts"
                allow="clipboard-read; clipboard-write"
                style="width:100%;height:100%;border:0;display:block;background:#FFFFFF"
                wire:ignore
            ></iframe>

            @unless ($session->needsHuman())
                {{-- Watch-only while the agent is in control --}}
                <div aria-hidden="true" style="position:absolute;inset:0;cursor:not-allowed"></div>
            @endunless
        @else
            <div style="height:100%;display:flex;align-items:center;justify-content:center;padding:24px;text-align:center;color:#6A6878;font-size:16px;font-weight:700">
                Live view isn't available for the {{ $session->driverName() }} driver.
            </div>
        @endif
    </div>
</div>
