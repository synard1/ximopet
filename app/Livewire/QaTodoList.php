<?php

namespace App\Livewire;

use App\Models\QaTodoList as QaTodoListModel;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\QaTodoComment;
use Illuminate\Support\Facades\Log;

class QaTodoList extends Component
{
    use WithPagination;
    use WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    public $module_name;
    public $feature_name;
    public $description;
    public $environment;
    public $priority;
    public $status = 'pending';
    public $assigned_to;
    public $reviewed_by;
    public $due_date;
    public $notes;
    public $editingId;
    public $search = '';
    public $filterEnvironment = '';
    public $filterPriority = '';
    public $filterStatus = '';
    public $comment = '';
    public $attachments = [];
    public $selectedTodoId = null;
    public $showComments = false;
    public $duplicateModuleName;
    public $duplicateFeatureName;
    public $todoToDuplicate = null;

    // --- Properti untuk Flash Message ---
    public $flashMessage = '';
    public $flashMessageType = ''; // 'success' atau 'error'
    // --- End Flash Message Properties ---

    protected $rules = [
        'module_name' => 'required|string|max:255',
        'feature_name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'environment' => 'required|in:dev,staging,production',
        'priority' => 'required|in:low,medium,high,critical',
        'status' => 'required|in:pending,in_progress,completed,blocked',
        'assigned_to' => 'nullable|exists:users,id',
        'reviewed_by' => 'nullable|exists:users,id',
        'due_date' => 'nullable|date',
        'notes' => 'nullable|string',
        // 'comment' => 'required|string|min:1',
        // 'attachments.*' => 'nullable|image|max:2048'
    ];

    public function mount()
    {
        if (!Auth::user()->can('access qa-todo')) {
            abort(403);
        }
    }

    public function render()
    {
        $query = QaTodoListModel::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('module_name', 'like', '%' . $this->search . '%')
                        ->orWhere('feature_name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterEnvironment, function ($query) {
                $query->where('environment', $this->filterEnvironment);
            })
            ->when($this->filterPriority, function ($query) {
                $query->where('priority', $this->filterPriority);
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            });

        $todoLists = $query->latest()->paginate(10);
        $users = User::all();

        return view('livewire.qa-todo-list', [
            'todoLists' => $todoLists,
            'users' => $users,
            'environments' => ['dev', 'staging', 'production'],
            'priorities' => ['low', 'medium', 'high', 'critical'],
            'statuses' => ['pending', 'in_progress', 'completed', 'blocked']
        ]);
    }

    public function save()
    {
        Log::info('save called');
        try {
            // Check permissions
            if (!Auth::user()->can('create qa-todo') && !$this->editingId) {
                throw new \Exception('You do not have permission to create QA todo items.');
            }

            if (!Auth::user()->can('update qa-todo') && $this->editingId) {
                throw new \Exception('You do not have permission to update QA todo items.');
            }

            // Validate input
            $this->validate();

            // Prepare data
            $data = [
                'module_name' => $this->module_name,
                'feature_name' => $this->feature_name,
                'description' => $this->description,
                'environment' => $this->environment,
                'priority' => $this->priority,
                'status' => $this->status,
                'assigned_to' => $this->assigned_to,
                'reviewed_by' => $this->reviewed_by,
                'due_date' => $this->due_date,
                'notes' => $this->notes,
            ];

            // Save or update
            if ($this->editingId) {
                $todoList = QaTodoListModel::findOrFail($this->editingId);
                $todoList->update($data);
                $message = 'QA todo item updated successfully.';
            } else {
                $data['created_by'] = Auth::id();
                QaTodoListModel::create($data);
                $message = 'QA todo item created successfully.';
            }

            // Reset form and dispatch success
            $this->reset(['module_name', 'feature_name', 'description', 'environment', 'priority', 'status', 'assigned_to', 'reviewed_by', 'due_date', 'notes', 'editingId']);
            $this->dispatch('todoSaved');
            session()->flash('message', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors - still dispatching array for JS to parse field errors
            $this->dispatch('error', [
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()->toArray()
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle model not found
            $errorMessage = 'Todo item not found: ' . $e->getMessage();
            $this->dispatch('error', $errorMessage);
            session()->flash('error', $errorMessage);
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database errors
            $errorMessage = 'Database error occurred: ' . $e->getMessage();
            // Log details for debugging
            Log::error("Database error in QA Todo save: " . $e->getMessage(), [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings()
            ]);
            $this->dispatch('error', $errorMessage);
            session()->flash('error', $errorMessage);
        } catch (\Exception $e) {
            // Handle any other errors
            $errorMessage = 'An unexpected error occurred: ' . $e->getMessage() .
                ' File: ' . $e->getFile() .
                ' Line: ' . $e->getLine();
            // Log full trace for debugging
            Log::error("An unexpected error occurred while saving QA Todo: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('error', $errorMessage);
            session()->flash('error', $errorMessage);
        }
    }

    public function edit($id)
    {
        if (!Auth::user()->can('update qa-todo')) {
            session()->flash('error', 'You do not have permission to edit QA todo items.');
            return;
        }

        $todoList = QaTodoListModel::findOrFail($id);
        $this->editingId = $todoList->id;
        $this->module_name = $todoList->module_name;
        $this->feature_name = $todoList->feature_name;
        $this->description = $todoList->description;
        $this->environment = $todoList->environment;
        $this->priority = $todoList->priority;
        $this->status = $todoList->status;
        $this->assigned_to = $todoList->assigned_to;
        $this->reviewed_by = $todoList->reviewed_by;
        $this->due_date = $todoList->due_date?->format('Y-m-d');
        $this->notes = $todoList->notes;
    }

    public function confirmDelete($id)
    {
        Log::info('confirmDelete called', ['id' => $id]);

        if (!Auth::user()->can('delete qa-todo')) {
            session()->flash('error', 'You do not have permission to delete QA todo items.');
            return;
        }

        try {
            $todoList = QaTodoListModel::findOrFail($id);
            Log::info('confirmDelete: Todo item found', ['id' => $id, 'todoList_type' => get_class($todoList)]);

            if ($todoList->comments()->exists()) {
                Log::info('confirmDelete: Todo has comments, dispatching confirmation', ['id' => $todoList->id]);
                $this->dispatch('confirmDeleteWithComments', $todoList->id);
            } else {
                Log::info('confirmDelete: Todo has no comments, proceeding to deleteConfirmed', ['id' => $id]);
                $this->deleteConfirmed($id);
            }
        } catch (\Exception $e) {
            $errorMessage = 'Error in confirmDelete: ' . $e->getMessage();
            Log::error($errorMessage, [
                'id' => $id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', $errorMessage);
        }
    }

    public function deleteConfirmed($id)
    {
        Log::info('deleteConfirmed called', ['id' => $id]);

        if (!Auth::user()->can('delete qa-todo')) {
            session()->flash('error', 'You do not have permission to delete QA todo items.');
            return;
        }

        try {
            // Ensure $id is a string
            $id = is_array($id) ? $id[0] : $id;

            $todoList = QaTodoListModel::findOrFail($id);
            Log::info('deleteConfirmed: Todo item found before deletion', ['id' => $id, 'todoList_type' => get_class($todoList)]);

            // Fetch comments directly using the todo ID
            $comments = QaTodoComment::where('todo_id', $id)->get();
            Log::info('deleteConfirmed: Fetched comments', ['todo_id' => $id, 'comment_count' => $comments->count()]);

            foreach ($comments as $comment) {
                // Optionally delete attachments here if not handled by model events
                if ($comment->attachments) {
                    foreach ($comment->attachments as $attachment) {
                        if (Storage::disk('public')->exists($attachment['path'])) {
                            Storage::disk('public')->delete($attachment['path']);
                        }
                    }
                }
                $comment->delete();
            }

            // Then delete the todo item
            $todoList->delete();

            session()->flash('message', 'QA todo item and associated comments deleted successfully.');
            Log::info('deleteConfirmed: Todo item and comments deleted successfully.', ['id' => $id]);
        } catch (\Exception $e) {
            $errorMessage = 'An error occurred while deleting the todo item: ' . $e->getMessage();
            Log::error($errorMessage, [
                'id' => $id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', $errorMessage);
        }
    }

    // public function updateStatus($id, $status)
    // {
    //     if (!Auth::user()->can('update qa-todo')) {
    //         session()->flash('error', 'You do not have permission to update QA todo items.');
    //         return;
    //     }

    //     $todoList = QaTodoListModel::findOrFail($id);
    //     $todoList->update(['status' => $status]);
    //     session()->flash('message', 'Status updated successfully.');
    // }

    // --- Ubah fungsi updateStatus Anda di sini ---
    public function updateStatus($id, $status)
    {
        // Sesuaikan model name jika QaTodoListModel bukan nama yang benar
        $todoList = QaTodoListModel::findOrFail($id);

        // Pastikan izin diperiksa sebelum pembaruan
        if (!Auth::user()->can('update qa-todo')) {
            $this->setFlashMessage('You do not have permission to update QA todo items.', 'error');
            return;
        }

        $todoList->update(['status' => $status]);
        $this->setFlashMessage('Status updated successfully.', 'success');
    }
    // --- Akhir perubahan fungsi updateStatus ---

    // --- Fungsi tambahan untuk Flash Message ---
    public function setFlashMessage($message, $type)
    {
        $this->flashMessage = $message;
        $this->flashMessageType = $type;
        // Dispatch a browser event to trigger the JS for auto-closing
        $this->dispatch('flash-message-shown', type: $type);
    }

    public function clearFlashMessage()
    {
        $this->flashMessage = '';
        $this->flashMessageType = '';
    }
    // --- End Fungsi tambahan ---

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function toggleComments($todoId)
    {
        $this->selectedTodoId = $todoId;
        $this->showComments = !$this->showComments;
        $this->comment = '';
        $this->attachments = [];
    }

    public function addComment()
    {
        Log::info('addComment called');
        $this->validate([
            'comment' => 'required|string|min:1',
            'attachments.*' => 'nullable|image|max:2048'
        ]);

        Log::info('addComment validation passed', ['attachments_count' => count($this->attachments)]);

        $todo = QaTodoListModel::findOrFail($this->selectedTodoId);

        $attachments = [];
        // Ensure $this->attachments is an array before iterating
        if (is_array($this->attachments) && count($this->attachments) > 0) {
            Log::info('Processing attachments');
            foreach ($this->attachments as $key => $file) {
                // Check if $file is a valid UploadedFile instance
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    try {
                        $path = $file->store('qa-todo-attachments', 'public');
                        Log::info('File stored', ['path' => $path, 'name' => $file->getClientOriginalName(), 'key' => $key]);
                        $attachments[] = [
                            'path' => $path,
                            'name' => $file->getClientOriginalName(),
                            'mime' => $file->getMimeType()
                        ];
                    } catch (\Exception $e) {
                        Log::error('Failed to store attachment', ['name' => $file->getClientOriginalName(), 'error' => $e->getMessage()]);
                        // Optionally add a session flash or dispatch an error event here
                    }
                } else {
                    Log::warning('Skipping invalid file in attachments array', ['key' => $key, 'file_type' => gettype($file)]);
                }
            }
        }

        $todo->comments()->create([
            'user_id' => Auth::id(),
            'comment' => $this->comment,
            'attachments' => $attachments
        ]);

        $this->comment = '';
        $this->attachments = []; // Reset attachments after save
        session()->flash('message', 'Comment added successfully.');
    }

    public function confirmDeleteComment($commentId)
    {
        if (!Auth::user()->can('delete qa-todo')) {
            session()->flash('error', 'You do not have permission to delete this comment.');
            return;
        }
        // Dispatch event to trigger SweetAlert confirmation in the view
        $this->dispatch('confirmDeleteComment', $commentId);
    }

    public function deleteCommentConfirmed($commentId)
    {
        Log::info('deleteCommentConfirmed called', ['comment_id' => $commentId]);

        // Ensure $commentId is a string
        $commentId = is_array($commentId) ? $commentId[0] : $commentId;

        if (!Auth::user()->can('delete qa-todo')) {
            session()->flash('error', 'You do not have permission to delete this comment.');
            return;
        }

        try {
            $comment = QaTodoComment::findOrFail($commentId);

            // Move attachments to retention folder instead of deleting
            if ($comment->attachments) {
                $retentionPath = 'retained_attachments/comments/' . $comment->id;
                foreach ($comment->attachments as $attachment) {
                    $originalPath = $attachment['path']; // Path relative to public disk root
                    $newPath = $retentionPath . '/' . $attachment['name']; // Path relative to default disk root (storage/app)

                    // Ensure the retention directory exists
                    Storage::makeDirectory($retentionPath);

                    // Move the file
                    if (Storage::disk('public')->exists($originalPath)) {
                        Storage::disk('public')->move($originalPath, $newPath);
                        Log::info('Moved comment attachment to retention', [
                            'original_path' => $originalPath,
                            'new_path' => $newPath,
                            'comment_id' => $comment->id
                        ]);
                    } else {
                        Log::warning('Comment attachment not found for moving', [
                            'original_path' => $originalPath,
                            'comment_id' => $comment->id
                        ]);
                    }
                }
            }

            $comment->delete();
            session()->flash('message', 'Comment and attachments moved to retention successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting comment: ' . $e->getMessage(), [
                'comment_id' => $commentId,
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'An error occurred while deleting the comment: ' . $e->getMessage());
        }
    }

    public function downloadAttachment($commentId, $attachmentIndex)
    {
        try {
            $comment = QaTodoComment::findOrFail($commentId);
            $attachment = $comment->attachments[$attachmentIndex] ?? null;

            if (!$attachment) {
                session()->flash('error', 'Attachment not found.');
                return;
            }

            // Get the full path to the file
            $filePath = storage_path('app/public/' . $attachment['path']);

            if (!file_exists($filePath)) {
                session()->flash('error', 'File not found on server.');
                return;
            }

            // Return the file as a download response
            return response()->download(
                $filePath,
                $attachment['name'],
                ['Content-Type' => $attachment['mime'] ?? 'application/octet-stream']
            );
        } catch (\Exception $e) {
            Log::error('Error downloading attachment: ' . $e->getMessage(), [
                'comment_id' => $commentId,
                'attachment_index' => $attachmentIndex,
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Error downloading file: ' . $e->getMessage());
            return null;
        }
    }

    public function refresh()
    {
        $this->resetPage();
    }

    public function confirmDuplicate($id)
    {
        if (!Auth::user()->can('create qa-todo')) {
            session()->flash('error', 'You do not have permission to duplicate QA todo items.');
            return;
        }

        try {
            $todo = QaTodoListModel::findOrFail($id);
            $this->todoToDuplicate = $todo;
            $this->duplicateModuleName = $todo->module_name . ' (Copy)';
            $this->duplicateFeatureName = $todo->feature_name . ' (Copy)';
            $this->dispatch('confirmDuplicate', $id);
        } catch (\Exception $e) {
            session()->flash('error', 'Error preparing todo item for duplication: ' . $e->getMessage());
        }
    }

    public function duplicateConfirmed()
    {
        if (!Auth::user()->can('create qa-todo')) {
            $this->setFlashMessage('You do not have permission to duplicate QA todo items.', 'error');
            return;
        }

        try {
            $this->validate([
                'duplicateModuleName' => 'required|string|max:255',
                'duplicateFeatureName' => 'required|string|max:255',
            ]);

            if (!$this->todoToDuplicate) {
                throw new \Exception('No todo item selected for duplication.');
            }

            // Create new todo item with duplicated data
            $newTodo = $this->todoToDuplicate->replicate();
            $newTodo->module_name = $this->duplicateModuleName;
            $newTodo->feature_name = $this->duplicateFeatureName;
            $newTodo->status = 'pending';
            $newTodo->created_by = Auth::id();
            $newTodo->created_at = now();
            $newTodo->updated_at = now();
            $newTodo->save();

            // Reset the form and close modal
            $this->reset(['duplicateModuleName', 'duplicateFeatureName', 'todoToDuplicate']);

            // Set success message
            $this->setFlashMessage('QA todo item duplicated successfully.', 'success');

            // Dispatch events for modal closing and refresh
            $this->dispatch('closeModal', 'duplicateModal');
            $this->dispatch('todoSaved');
        } catch (\Exception $e) {
            $this->setFlashMessage('Error duplicating todo item: ' . $e->getMessage(), 'error');
        }
    }
}
