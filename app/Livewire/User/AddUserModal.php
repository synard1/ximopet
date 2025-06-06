<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AddUserModal extends Component
{
    use WithFileUploads;

    public $user_id = null;
    public $name;
    public $email;
    public $role;
    public $avatar;
    public $saved_avatar;
    public $password = '';
    public $passwordConfirmation = '';
    public $user;

    public $edit_mode = false;
    public $isOpen = 0;

    protected $rules = [
        'name' => 'required|string',
        'email' => 'required|email',
        'role' => 'required|string',
        'avatar' => 'nullable|sometimes|image|max:1024',
        'password' => 'required|min:8|same:passwordConfirmation',
        'passwordConfirmation' => 'required',


    ];

    protected $listeners = [
        'delete_user' => 'deleteUser',
        'update_user' => 'updateUser',
        'edit' => 'edit',
        'new_user' => 'create',
        'suspendUser' => 'suspendUser',
        'cancelSuspension' => 'cancelSuspension',
    ];

    public function render()
    {
        // $roles = Role::all();
        // Get roles, exclude 'SuperAdmin' if current user is not one
        $rolesQuery = Role::query();
        if (! auth()->user()->hasRole('SuperAdmin')) {
            $rolesQuery->where('name', '!=', 'SuperAdmin');
        }
        $roles = $rolesQuery->get();

        $roles_description = [
            'Administrator' => 'Best for business owners and company administrators',
            'Supervisor' => 'Best for developers or people primarily using the API',
            'Operator' => 'Best for employees who regularly refund payments and respond to disputes',
            'trial' => 'Best for people who need to preview content data, but don\'t need to make any updates',
        ];

        foreach ($roles as $i => $role) {
            $roles[$i]->description = $roles_description[$role->name] ?? '';
        }

        return view('livewire.user.user', compact('roles'));
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    // public function store()
    // {
    //     $data =[
    //         'user_id' => $this->user_id,
    //         'name' => $this->name,
    //         'email' => $this->email,
    //     ];

    //     dd($data);
    //     $this->validate();
    //     User::updateOrCreate(['id' => $this->supplier_id], [
    //         'jenis' => "Supplier",
    //         'kode' => $this->kode,
    //         'nama' => $this->nama,
    //         'alamat' => $this->alamat,
    //         'email' => $this->email,
    //         'status' => 'Aktif',
    //     ]);

    //     if($this->supplier_id){
    //         $this->dispatch('success', __('Data Supplier Berhasil Diubah'));

    //     }else{
    //         $this->dispatch('success', __('Data Supplier Berhasil Dibuat'));
    //     }

    //     // session()->flash('message', 
    //     // $this->supplier_id ? 'User updated successfully.' : 'User created successfully.');
    //     $this->closeModal();
    //     $this->resetInputFields();
    // }

    public function create()
    {
        // $this->resetInputFields();
        $this->openModal();
    }

    public function store()
    {
        // Validate the form input data
        $this->validate();

        DB::transaction(function () {
            // Prepare the data for creating a new user
            $data = [
                'name' => $this->name,
            ];

            if (!$this->edit_mode) {
                $data['password'] = Hash::make($this->password);
            }

            // Update or Create a new user record in the database
            $data['email'] = $this->email;
            $data['password'] = Hash::make($this->password);
            $data['email_verified_at'] = now();
            $user = User::find($this->user_id) ?? User::create($data);

            if ($this->edit_mode) {
                foreach ($data as $k => $v) {
                    $user->$k = $v;
                }
                $user->save();
            }

            if ($this->edit_mode) {
                // Assign selected role for user
                $user->syncRoles($this->role);

                // Emit a success event with a message
                $this->dispatch('success', __('User updated'));
            } else {
                // Assign selected role for user
                $user->assignRole($this->role);

                // Send a password reset link to the user's email
                // Password::sendResetLink($user->only('email'));

                // Emit a success event with a message
                $this->dispatch('success', __('New user created'));
            }
        });

        $this->closeModal();

        // Reset the form fields after successful submission
        $this->reset();
    }

    public function deleteUser($id)
    {
        try {
            // Prevent deletion of current user
            if ($id == Auth::id()) {
                $this->dispatch('error', 'User cannot be deleted');
                return;
            }

            // Check if the user is the creator of any LivestockPurchase, FeedPurchase, or SupplyPurchase
            if (\App\Models\LivestockPurchase::where('created_by', $id)->exists() || \App\Models\FeedPurchase::where('created_by', $id)->exists() || \App\Models\SupplyPurchase::where('created_by', $id)->exists()) {
                // Ask for confirmation before suspending the user
                $this->dispatch('confirm', [
                    'title' => 'Confirm Suspension',
                    'text' => 'User cannot be deleted because they have created purchases. Do you want to suspend the user instead?',
                    'confirmButtonText' => 'Yes, Suspend',
                    'cancelButtonText' => 'No, Cancel',
                    'onConfirmed' => 'suspendUser',
                    'onCancelled' => 'cancelSuspension',
                    'params' => ['id' => $id],
                ]);
                return;
            }

            DB::table('farm_operators')->where('user_id', $id)->delete();

            // Delete the user record with the specified ID
            User::destroy($id);

            // Emit a success event with a message
            $this->dispatch('success', 'User successfully deleted');
        } catch (\Exception $e) {
            DB::rollBack();

            // Handle validation and general errors
            $this->dispatch('error', 'Terjadi kesalahan saat menghapus data. ' . $e->getMessage());
        }
    }

    public function suspendUser($id)
    {
        $user = User::find($id);
        $user->update(['status' => 'suspended']);
        $this->dispatch('success', 'User suspended successfully');
    }

    public function cancelSuspension()
    {
        $this->dispatch('error', 'Suspension cancelled');
    }

    public function updateUser($id)
    {
        $this->edit_mode = true;

        $user = User::with('roles')->find($id);

        if (!$user) {
            $this->dispatch('error', 'User not found');
            return;
        }

        $this->user_id = $user->id;
        $this->saved_avatar = $user->profile_photo_url;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()->name ?? '';
        $this->user = $user;

        $this->openModal();
    }

    // public function hydrate()
    // {
    //     $this->resetErrorBag();
    //     $this->resetValidation();
    //     $this->reset();
    // }

    public function edit($id)
    {
        $this->edit_mode = true;
        $user = User::with('roles')->where('id', $id)->first();
        $this->user_id = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()->name ?? '';
        $this->user = $user;

        $this->openModal();
    }
}
