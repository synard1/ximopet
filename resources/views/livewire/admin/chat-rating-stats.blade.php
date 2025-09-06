<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">AI Chat Rating Statistics</h5>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-0">{{ $stats['average_rating'] }}</h2>
                <small class="text-muted">Average Rating ({{ $stats['total_ratings'] }} ratings)</small>
            </div>
            <div>
                <select wire:model.live="period" class="form-select form-select-sm">
                    <option value="day">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="year">This Year</option>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <h6>Rating Distribution</h6>
            @for ($i = 5; $i >= 1; $i--)
                <div class="d-flex align-items-center mb-2">
                    <span class="me-2">{{ $i }} star{{ $i > 1 ? 's' : '' }}</span>
                    <div class="flex-grow-1">
                        <div class="progress" style="height: 10px;">
                            @php
                                $percentage = $stats['total_ratings'] > 0 ?
                                    (($stats['rating_distribution'][$i] ?? 0) / $stats['total_ratings']) * 100 : 0;
                            @endphp
                            <div class="progress-bar bg-{{ $i >= 4 ? 'success' : ($i >= 3 ? 'warning' : 'danger') }}"
                                 role="progressbar"
                                 style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                    <span class="ms-2 small">{{ $stats['rating_distribution'][$i] ?? 0 }}</span>
                </div>
            @endfor
        </div>

        @if (!empty($stats['recent_feedback']))
            <div>
                <h6>Recent Feedback</h6>
                <div class="list-group">
                    @foreach($stats['recent_feedback'] as $feedback)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $feedback->user->name ?? 'Anonymous' }}</strong>
                                    <span class="badge bg-{{ $feedback->rating >= 4 ? 'success' : ($feedback->rating >= 3 ? 'warning' : 'danger') }} ms-2">
                                        {{ $feedback->rating }} star{{ $feedback->rating > 1 ? 's' : '' }}
                                    </span>
                                </div>
                                <small class="text-muted">{{ $feedback->created_at->diffForHumans() }}</small>
                            </div>
                            <p class="mb-0 mt-1">{{ $feedback->feedback }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
