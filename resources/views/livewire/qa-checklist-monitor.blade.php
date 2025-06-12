<div>
    @if(config('app.debug') && app()->environment('local'))
    <div x-data="{ minimized: false }"
        style="position:fixed;bottom:10px;right:380px;z-index:9999;width:350px;background:#fff;border:1px solid #ddd;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);padding:16px;">

        <div style="display:flex;justify-content:space-between;align-items:center;">
            <strong>QA Checklist Monitor</strong>
            <button @click="minimized = !minimized"
                style="background:none;border:none;font-size:18px;line-height:1;cursor:pointer;">
                <span x-show="!minimized">&#8211;</span>
                <span x-show="minimized">&#x25A1;</span>
            </button>
        </div>
        <div style="font-size:12px;color:#888;" x-show="!minimized">
            URL: <span style="color:#333">{{ $url }}</span>
        </div>
        <hr x-show="!minimized">
        <div x-show="!minimized">
            @if($checklists && count($checklists))
            <ul style="max-height:200px;overflow:auto;padding-left:18px;">
                @foreach($checklists as $idx => $item)
                <li x-data="{ showDetail: false }" style="margin-bottom:10px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <strong>{{ $item->feature_name }}</strong>
                        </div>
                        <button @click="showDetail = !showDetail"
                            style="background:none;border:none;font-size:13px;cursor:pointer;color:#007bff;">
                            <span x-show="!showDetail">Detail</span>
                            <span x-show="showDetail">Tutup</span>
                        </button>
                    </div>
                    <div style="font-size:12px;">
                        <span>Status: <b>{{ $item->status }}</b></span><br>
                        <span>Tested by: {{ $item->tester_name }}</span><br>
                        <span>Date: {{ $item->test_date ? $item->test_date->format('Y-m-d') : '-' }}</span>
                    </div>
                    <div x-show="showDetail" style="margin-top:8px;background:#f8f9fa;padding:8px;border-radius:6px;">
                        <div><b>Test Case:</b> {{ $item->test_case }}</div>
                        <div><b>Test Steps:</b>
                            <pre style="white-space:pre-wrap;font-size:12px;">{{ $item->test_steps }}</pre>
                        </div>
                        <div><b>Expected Result:</b>
                            <pre style="white-space:pre-wrap;font-size:12px;">{{ $item->expected_result }}</pre>
                        </div>
                        <div><b>Test Type:</b> {{ $item->test_type }}</div>
                        @if($item->notes)
                        <div><b>Notes:</b> {{ $item->notes }}</div>
                        @endif
                        @if($item->error_details)
                        <div><b>Error Details:</b> {{ $item->error_details }}</div>
                        @endif
                    </div>
                </li>
                @endforeach
            </ul>
            @else
            <div style="color:#888;">No QA Checklist found for this page.</div>
            @endif
        </div>
    </div>
    @endif
</div>