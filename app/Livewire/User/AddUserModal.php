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

    public $generate_length = 12;
    public $generate_uppercase = true;
    public $generate_lowercase = true;
    public $generate_numbers = true;
    public $generate_symbols = true;

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
        'new_user_company' => 'createCompanyUser',
    ];

    public function render()
    {
        $roles = auth()->user()->getAvailableRoles();
        $roles_description = config('xolution.company_role_descriptions', []);

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

        // dd($this->all());

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

            // Handle role assignment
            if ($this->edit_mode) {
                $user->syncRoles($this->role);
            } else {
                $user->assignRole($this->role);
            }

            // Handle CompanyUser data for Administrator users
            if (auth()->user()->hasRole('Administrator')) {
                $currentUserMapping = \App\Models\CompanyUser::getUserMapping();

                if ($currentUserMapping) {
                    // Update or create CompanyUser record
                    \App\Models\CompanyUser::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'company_id' => $currentUserMapping->company_id,
                        ],
                        [
                            'isAdmin' => $this->role === 'Administrator',
                            'status' => 'active',
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]
                    );
                }
            }

            if ($this->edit_mode) {
                $this->dispatch('success', __('User updated'));
            } else {
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

    public function createCompanyUser()
    {
        $this->isOpen = true;

        // $this->openModal();

    }

    public function generatePassword()
    {
        $length = $this->generate_length;
        $useUpper = $this->generate_uppercase;
        $useLower = $this->generate_lowercase;
        $useNumbers = $this->generate_numbers;
        $useSymbols = $this->generate_symbols;

        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}|;:,.<>?';

        $all = '';
        if ($useUpper) $all .= $upper;
        if ($useLower) $all .= $lower;
        if ($useNumbers) $all .= $numbers;
        if ($useSymbols) $all .= $symbols;

        if ($all === '') {
            $this->dispatch('error', 'Pilih minimal satu opsi karakter!');
            return;
        }

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        $this->password = $password;
        $this->passwordConfirmation = $password;
        $this->dispatch('success', 'Password berhasil digenerate!');
    }
}
