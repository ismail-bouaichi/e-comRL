<?php

namespace App\Livewire\Show;

use App\Models\Order;
use App\Models\DeliveryWorker;
use Livewire\Component;

class OrderShow extends Component
{
    public $order;
    public $first_name,$last_name, $email,$phone ,$customer_id, $delivery_worker_id, $status,$is_assigned,$session_id,$shipping_cost,$latitude, $longitude;
    public $deliveryWorker; // Add delivery worker object
    public $showOrderDetails = false;
    
    public function mount($orderId){
       $this->order = Order::with(['orderDetails.product', 'deliveryWorker.user'])->findOrFail($orderId);
       $this->first_name = $this->order->first_name;
       $this->last_name = $this->order->last_name;
       $this->email = $this->order->email;
       $this->phone = $this->order->phone;
       $this->customer_id = $this->order->customer_id;
       $this->delivery_worker_id = $this->order->delivery_worker_id;
       $this->deliveryWorker = $this->order->deliveryWorker; // Load delivery worker
       $this->status = $this->order->status;
       $this->is_assigned = $this->order->is_assigned;
       $this->session_id = $this->order->session_id;
       $this->shipping_cost = $this->order->shipping_cost;
       $this->latitude = $this->order->latitude;
       $this->longitude = $this->order->longitude;
       
    }

    public function toggleOrderDetails()
    {
        $this->showOrderDetails = !$this->showOrderDetails;
    }
    public function render()
    {
        return view('livewire.show.order-show');
    }
}
