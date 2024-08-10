<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Contact;
use Ramsey\Uuid\Uuid; // Import the UUID library


class Contacts extends Component
{
    public $contacts, $kode, $nama, $alamat, $email;
    public $contact_id = null; // Initialize with null instead of empty string
    public $isOpen = 0;

    public function render()
    {
        $this->contacts = Contact::all();
        return view('livewire.contacts');
    }

    public function create()
    {
        $this->resetInputFields();
        // $this->kode = (string) Uuid::uuid4(); // Generate and cast to string
        $this->openModal();
    }

    public function store()
    {
        $this->validate();
        Contact::updateOrCreate(['id' => $this->contact_id], [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'email' => $this->email,
        ]);

        session()->flash('message', 
        $this->contact_id ? 'Contact updated successfully.' : 'Contact created successfully.');
        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $contact = Contact::findOrFail($id);
        $this->contact_id = $id;
        $this->kode = $contact->kode;
        $this->nama = $contact->nama;
        $this->alamat = $contact->alamat;
        $this->email = $contact->email;

        $this->openModal();
    }

    public function delete($id)
    {
        Contact::find($id)->delete();
        session()->flash('message', 'Contact deleted successfully.');
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    private function resetInputFields(){
        // $this->kode = Str::uuid(); // Generate UUID for new contacts
        $this->kode = ''; // Generate UUID for new contacts
        $this->nama = '';
        $this->alamat = '';
        $this->email = '';
        $this->contact_id = '';
    }

    protected $rules = [
        'kode' => 'required|unique:contacts,kode', // Unique except on update
        'nama' => 'required',
        'alamat' => 'required',
        'email' => 'required|email',
    ];

    public function updatingKode()
    {
        if ($this->contact_id) {
            $this->resetErrorBag('kode'); // Clear error if it's an edit
        }
    }
}
