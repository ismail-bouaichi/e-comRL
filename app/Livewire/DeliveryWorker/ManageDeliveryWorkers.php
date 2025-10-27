<?php

namespace App\Livewire\DeliveryWorker;

use App\Models\DeliveryWorker;
use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

class ManageDeliveryWorkers extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDir = 'DESC';
    
    // Modal properties
    public $showAssignModal = false;
    public $selectedWorkerId = null;
    public $selectedOrderId = null;
    public $availableOrders = [];

    public function setSortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            $this->sortDir = 'ASC';
        }
        $this->sortBy = $field;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openAssignModal($workerId)
    {
        $this->selectedWorkerId = $workerId;
        $this->showAssignModal = true;
        
        // Load available orders (pending/processing orders without delivery worker)
        $this->availableOrders = Order::with('customer')
            ->whereNull('delivery_worker_id')
            ->whereIn('status', ['pending', 'processing', 'paid'])
            ->latest()
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function assignToOrder()
    {
        $this->validate([
            'selectedOrderId' => 'required|exists:orders,id',
            'selectedWorkerId' => 'required|exists:delivery_workers,id',
        ]);

        $order = Order::findOrFail($this->selectedOrderId);
        $worker = DeliveryWorker::findOrFail($this->selectedWorkerId);

        // Update order
        $order->update([
            'delivery_worker_id' => $worker->id,
            'delivery_started_at' => now(),
        ]);

        // Update worker status
        $worker->update([
            'status' => 'on_delivery',
            'current_order_id' => $order->id,
        ]);

        session()->flash('success', 'Delivery worker assigned successfully!');
        $this->closeAssignModal();
    }

    public function closeAssignModal()
    {
        $this->showAssignModal = false;
        $this->selectedWorkerId = null;
        $this->selectedOrderId = null;
        $this->availableOrders = [];
    }

    public function updateWorkerStatus($workerId, $newStatus)
    {
        $worker = DeliveryWorker::findOrFail($workerId);
        
        $worker->update(['status' => $newStatus]);
        
        // If setting to available, clear current order
        if ($newStatus === 'available') {
            $worker->update(['current_order_id' => null]);
        }

        session()->flash('success', 'Worker status updated successfully!');
    }

    public function render()
    {
        $query = DeliveryWorker::with(['user', 'currentOrder']);

        // Search filter
        if (!empty($this->search)) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('email', 'like', '%'.$this->search.'%');
            })->orWhere('phone', 'like', '%'.$this->search.'%');
        }

        // Status filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $workers = $query->orderBy($this->sortBy, $this->sortDir)
            ->paginate($this->perPage);

        return view('livewire.delivery-worker.manage-delivery-workers', [
            'workers' => $workers
        ]);
    }
}
