<div class="chat-rating">
    <style>
        .rating-star-btn {
            transition: transform 0.2s ease, color 0.2s ease;
        }
        .rating-star-btn:hover {
            transform: scale(1.2);
        }
        .rating-star-btn:hover .fa-star,
        .rating-star-btn:focus .fa-star {
            color: #ffc107 !important;
        }
    </style>
    @if (!$ratingSubmitted)
        <div class="rating-prompt mb-2">
            <p class="text-muted small mb-1">Was this response helpful? Rate from 1-5 stars</p>
            <div class="rating-stars d-flex align-items-center">
                @for ($i = 1; $i <= 5; $i++)
                    <button
                        wire:click="setRating({{ $i }})"
                        class="btn btn-sm px-1 {{ $rating >= $i ? 'text-warning' : 'text-muted' }} rating-star-btn"
                        aria-label="Rate {{ $i }} stars"
                        @if($isRegenerating) disabled title="Rating disabled during regenerate" @endif
                    >
                        <i class="fas fa-star fa-lg"></i>
                    </button>
                @endfor
                @if ($rating > 0 && !$showFeedbackForm)
                    <span class="ms-2 small text-muted">{{ $rating }}/5</span>
                @endif
            </div>
        </div>

        @if ($showFeedbackForm)
            <div class="feedback-form mb-3 mt-2">
                <div class="form-group">
                    <label class="small">
                        @if ($rating == 1)
                            Feedback <span class="text-danger">*</span>
                        @else
                            Feedback (Optional)
                        @endif
                    </label>
                    <textarea
                        wire:model.defer="feedback"
                        class="form-control form-control-sm @error('feedback') is-invalid @enderror"
                        rows="2"
                        placeholder="@if ($rating == 1) Please provide feedback (required) @else What could be improved? (Optional) @endif"
                    ></textarea>
                    @error('feedback')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Notes Field -->
                <div class="form-group mt-2">
                    <label class="small text-muted">Additional Notes (Optional)</label>
                    <textarea
                        wire:model.defer="notes"
                        class="form-control form-control-sm"
                        rows="2"
                        placeholder="Add any additional notes about this response..."
                    ></textarea>
                    <small class="form-text text-muted">These notes are for internal use and won't be shared publicly.</small>
                </div>

                <div class="d-flex justify-content-end mt-2">
                    <button wire:click="toggleFeedbackForm" class="btn btn-sm btn-light me-2">Cancel</button>
                    <button wire:click="submitRating" class="btn btn-sm btn-primary" 
                        @if($isRegenerating) disabled title="Submit disabled during regenerate" @endif>Submit</button>
                </div>
            </div>
        @endif

        @if ($errorMessage)
            <div class="alert alert-danger small py-1 px-2 mt-2">
                {{ $errorMessage }}
            </div>
        @endif
    @else
        <div class="rating-complete small">
            <p class="mb-0">
                <i class="fas fa-check-circle text-success me-1"></i>
                <span class="text-muted">Thank you for your feedback!</span>
                <span class="ms-2">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="fas fa-star {{ $rating >= $i ? 'text-warning' : 'text-muted' }}"></i>
                    @endfor
                </span>
                <button wire:click="handleEdit" class="btn btn-link btn-sm p-0 ms-2 text-muted">
                    Edit
                </button>
            </p>
        </div>
    @endif
</div>
