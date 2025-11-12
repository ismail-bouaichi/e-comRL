<?php

namespace App\Livewire\Create;

use App\Models\Role;
use App\Models\User;
use App\Models\DeliveryWorker;
use Livewire\Component;




class CreateUser extends Component
{
    public $name; 
    public $email;
    public $password;
    public $password_confirmation;
    public $selectedRole;
    public $roles;
    public $role_id;
    
    // Delivery worker specific fields
    public $phone;
    public $vehicle_type;
    public $vehicle_number;
    public $license_number;

   
    public function updatedSelectedRole($value)
{
    $this->role_id = $value;
}
    
    public function submit(){
        // Get the selected role to check if it's delivery_worker
        $selectedRole = Role::find($this->role_id);
        $isDeliveryWorker = $selectedRole && $selectedRole->name === 'delivery_worker';
        
        // Base validation
        $rules = [
            'name' => 'required|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'password_confirmation' => 'required|same:password',
            'role_id' => 'required|exists:roles,id',
        ];
        
        // Add delivery worker specific validation
        if ($isDeliveryWorker) {
            $rules['phone'] = 'required|string|max:20';
            $rules['vehicle_type'] = 'required|in:bike,car,motorcycle,van,truck';
            $rules['vehicle_number'] = 'required|string|max:50';
            $rules['license_number'] = 'required|string|max:50';
        }
        
        $this->validate($rules);
    
        // Create the user
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
            'role_id' => $this->role_id
        ]);
        
        // If user is delivery worker, create delivery_worker record
        if ($isDeliveryWorker) {
            DeliveryWorker::create([
                'user_id' => $user->id,
                'phone' => $this->phone,
                'vehicle_type' => $this->vehicle_type,
                'vehicle_number' => $this->vehicle_number,
                'license_number' => $this->license_number,
                'status' => 'offline', // Default status
                'current_order_id' => null,
            ]);
        }
    
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'role_id', 'phone', 'vehicle_type', 'vehicle_number', 'license_number']);
    
        session()->flash('success', 'User created successfully!');
    }
    
    public function generatePassword() : Void {

        $lowercase=range('a','z');
        $uppercase=range('A','Z');
        $digits=range(0,9);
        $special=['!','@','#','$','%','^','&','*'];
        $chars=array_merge($lowercase,$uppercase,$digits,$special);

        $length=env('PASSWORD_LENGTH',8);


        do {
            $password=array();
            
            for($i=0;$i<=$length;$i++)
            {
                $int=rand(0,count($chars)-1);

                array_push($password,$chars[$int]);
            }

        } while (empty(array_intersect($special,$password)));

        $this->setPasswords(implode('',$password));
        
    }


    public function setPasswords($value)  {
        $this->password=$value;

        $this->password_confirmation=$value;
        
    }
    public function cancel()  {
      return  $this->reset(['name', 'email', 'password', 'password_confirmation', 'role_id', 'phone', 'vehicle_type', 'vehicle_number', 'license_number']);
    }
    public function mount()
    {
        $this->roles = Role::all(); // Assuming you have a Role model
    }
    public function render()
    {
        return view('livewire.create.create-user');
    }
}
