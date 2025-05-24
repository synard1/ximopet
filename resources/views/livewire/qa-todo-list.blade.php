<div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">QA Todo List</h3>
            <div class="card-toolbar">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#todoModal">
                    Add New Todo
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-5">
                <div class="col-md-3">
                    <input type="text" class="form-control" wire:model.live="search" placeholder="Search todos...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="filterEnvironment">
                        <option value="">All Environments</option>
                        @foreach($environments as $env)
                        <option value="{{ $env }}">{{ ucfirst($env) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="filterPriority">
                        <option value="">All Priorities</option>
                        @foreach($priorities as $priority)
                        <option value="{{ $priority }}">{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="filterStatus">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
            @endif

            @if (session()->has('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                    <thead>
                        <tr class="fw-bold text-muted">
                            <th>Module</th>
                            <th>Feature</th>
                            <th>Environment</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Reviewed By</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($todoLists as $todo)
                        <tr>
                            <td>{{ $todo->module_name }}</td>
                            <td>{{ $todo->feature_name }}</td>
                            <td>
                                <span
                                    class="badge badge-light-{{ $todo->environment === 'production' ? 'danger' : ($todo->environment === 'staging' ? 'warning' : 'info') }}">
                                    {{ ucfirst($todo->environment) }}
                                </span>
                            </td>
                            <td>
                                <span
                                    class="badge badge-light-{{ $todo->priority === 'critical' ? 'danger' : ($todo->priority === 'high' ? 'warning' : ($todo->priority === 'medium' ? 'primary' : 'success')) }}">
                                    {{ ucfirst($todo->priority) }}
                                </span>
                            </td>
                            <td>
                                <select class="form-select form-select-sm"
                                    wire:change="updateStatus({{ $todo->id }}, $event.target.value)">
                                    @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ $todo->status === $status ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>{{ $todo->assignedTo?->name ?? 'Unassigned' }}</td>
                            <td>{{ $todo->reviewedBy?->name ?? 'Not Reviewed' }}</td>
                            <td>{{ $todo->due_date?->format('Y-m-d') ?? 'No due date' }}</td>
                            <td>
                                <button class="btn btn-sm btn-light-primary me-2" wire:click="edit('{{ $todo->id }}')"
                                    data-bs-toggle="modal" data-bs-target="#todoModal">
                                    Edit
                                </button>
                                <button class="btn btn-sm btn-light-danger"
                                    wire:click="confirmDelete('{{ $todo->id }}')" wire:loading.attr="disabled">
                                    Delete
                                </button>
                                <button class="btn btn-sm btn-light-info"
                                    wire:click="toggleComments('{{ $todo->id }}')">
                                    Comments
                                </button>
                            </td>
                        </tr>
                        @if($showComments && $selectedTodoId === $todo->id)
                        <tr>
                            <td colspan="8">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Comments</h5>

                                        <!-- Comments List -->
                                        <div class="comments-list mb-4">
                                            @foreach($todo->comments as $comment)
                                            <div class="d-flex mb-4">
                                                <div class="flex-shrink-0">
                                                    <div class="symbol symbol-35px">
                                                        <i class="fas fa-user fa-2x"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <span class="fw-bold">{{ $comment->user->name }}</span>
                                                        <span class="text-muted ms-2">{{
                                                            $comment->created_at->diffForHumans() }}</span>
                                                        @if($comment->user_id === auth()->id() ||
                                                        auth()->user()->can('delete qa-todo'))
                                                        <button class="btn btn-sm btn-icon btn-light-danger ms-auto"
                                                            wire:click="confirmDeleteComment('{{ $comment->id }}')">
                                                            <i class="ki-duotone ki-trash fs-2"></i>
                                                        </button>
                                                        @endif
                                                    </div>
                                                    <div class="text-gray-800">{{ $comment->comment }}</div>

                                                    <!-- Attachments -->
                                                    @if($comment->attachments)
                                                    <div class="mt-2">
                                                        @foreach($comment->attachments as $index => $attachment)
                                                        <div class="d-inline-block me-2">
                                                            @if(str_starts_with($attachment['mime'], 'image/'))
                                                            <a href="{{ Storage::url($attachment['path']) }}"
                                                                target="_blank">
                                                                <img src="{{ Storage::url($attachment['path']) }}"
                                                                    alt="{{ $attachment['name'] }}"
                                                                    class="img-thumbnail" style="max-height: 100px;">
                                                            </a>
                                                            @else
                                                            <a href="{{ route('qa.todo.download-attachment', ['comment' => $comment->id, 'index' => $index]) }}"
                                                                class="btn btn-sm btn-light-primary">
                                                                <i class="ki-duotone ki-document fs-2"></i>
                                                                {{ $attachment['name'] }}
                                                            </a>
                                                            @endif
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>

                                        <!-- Add Comment Form -->
                                        <form wire:submit="addComment">
                                            <div class="mb-3">
                                                <label class="form-label">Add Comment</label>
                                                <textarea class="form-control" wire:model="comment" rows="3"
                                                    placeholder="Type your comment here..."></textarea>
                                                @error('comment') <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Attachments</label>
                                                <input type="file" class="form-control" wire:model="attachments"
                                                    multiple>
                                                <div class="form-text">You can upload multiple images (max 2MB each)
                                                </div>
                                                @error('attachments.*') <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="text-end">
                                                <button type="submit" class="btn btn-primary">
                                                    <span wire:loading.remove wire:target="addComment">Add
                                                        Comment</span>
                                                    <span wire:loading wire:target="addComment"
                                                        class="spinner-border spinner-border-sm align-middle me-2"></span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $todoLists->links() }}
            </div>
        </div>
    </div>

    <!-- Todo Modal -->
    <div class="modal fade" id="todoModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editingId ? 'Edit Todo' : 'Add New Todo' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit="save">
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">Module Name</label>
                                <input type="text" class="form-control" wire:model="module_name"
                                    placeholder="Enter module name">
                                @error('module_name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Feature Name</label>
                                <input type="text" class="form-control" wire:model="feature_name"
                                    placeholder="Enter feature name">
                                @error('feature_name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" wire:model="description" rows="3"
                                    placeholder="Enter description"></textarea>
                                @error('description') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Environment</label>
                                <select class="form-select" wire:model="environment">
                                    <option value="">Select Environment</option>
                                    @foreach($environments as $env)
                                    <option value="{{ $env }}">{{ ucfirst($env) }}</option>
                                    @endforeach
                                </select>
                                @error('environment') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Priority</label>
                                <select class="form-select" wire:model="priority">
                                    <option value="">Select Priority</option>
                                    @foreach($priorities as $priority)
                                    <option value="{{ $priority }}">{{ ucfirst($priority) }}</option>
                                    @endforeach
                                </select>
                                @error('priority') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Status</label>
                                <select class="form-select" wire:model="status">
                                    @foreach($statuses as $status)
                                    <option value="{{ $status }}">{{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Assigned To</label>
                                <select class="form-select" wire:model="assigned_to">
                                    <option value="">Select User</option>
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('assigned_to') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Reviewed By</label>
                                <select class="form-select" wire:model="reviewed_by">
                                    <option value="">Select Reviewer</option>
                                    @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('reviewed_by') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Due Date</label>
                                <input type="date" class="form-control" wire:model="due_date">
                                @error('due_date') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" wire:model="notes" rows="3"
                                    placeholder="Enter notes"></textarea>
                                @error('notes') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="text-end pt-5">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:loading.remove wire:target="save">Save</span>
                                <span wire:loading wire:target="save"
                                    class="spinner-border spinner-border-sm align-middle me-2"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', function () {
            Livewire.on('todoSaved', () => {
                const modal = document.getElementById('todoModal');
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });

            // Listen for the 'error' event and display validation errors
            Livewire.on('error', (data) => {
                if (data && data.errors) {
                    // Clear previous errors
                    document.querySelectorAll('.text-danger').forEach(el => el.innerText = '');
                    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

                    // Display new errors
                    for (const [key, value] of Object.entries(data.errors)) {
                        const inputElement = document.getElementById(key);
                        if (inputElement) {
                            inputElement.classList.add('is-invalid');
                            let errorElement = inputElement.nextElementSibling;
                            if (errorElement && errorElement.classList.contains('text-danger')) {
                                 errorElement.innerText = value[0];
                            } else {
                                const newErrorElement = document.createElement('div');
                                newErrorElement.classList.add('text-danger', 'mt-1');
                                newErrorElement.innerText = value[0];
                                inputElement.parentNode.insertBefore(newErrorElement, inputElement.nextSibling);
                            }
                        }
                    }
                } else if (data && data.message) {
                     // Handle other types of errors (non-validation)
                     console.error('Error:', data);
                     // You might want to display a general error message here
                }
            });

            // Listen for the confirmDeleteWithComments event
            Livewire.on('confirmDeleteWithComments', (todoId) => {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This todo item has comments. Deleting it will also delete all associated comments. Are you sure you want to proceed?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.find('{{ $this->getId() }}').deleteConfirmed(todoId);
                    }
                });
            });

             // Listen for the confirmDeleteComment event
            Livewire.on('confirmDeleteComment', (commentId) => {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You are about to delete this comment.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                         Livewire.find('{{ $this->getId() }}').deleteCommentConfirmed(commentId);
                    }
                });
            });
        });
    </script>
    @endpush
</div>