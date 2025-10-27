<?php

namespace App\Livewire\Create;

use App\Models\Product;
use Livewire\Component;
use App\Models\Discount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


class CreateDiscount extends Component
{
    public $name;
    public $code;
    public $discount_type;
    public $discount_value;
    public $start_date;
    public $end_date;
    public $is_active = true;
    public $products = [];
    public $product_ids = [];
    public $search = '';
    public $loadingMore = false;
    public $perPage = 20;
    public $page = 1;
    protected $queryString = ['search'];

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('discounts', 'code')],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    if ($this->discount_type === 'percentage' && $value > 100) {
                        $fail('Percentage discount cannot be greater than 100%.');
                    }
                }
            ],
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
        ];
    }
    
    public function messages()
    {
        return [
            'product_ids.min' => 'Please select at least one product.',
            'discount_value.min' => 'Discount value must be greater than 0.',
            'end_date.after' => 'End date must be after start date.',
        ];
    }

    public function createDiscount()
    {
        try {
            $validatedData = $this->validate();
            
            DB::beginTransaction();
            
            // Create the discount
            $discount = Discount::create($validatedData);
    
            // Attach the selected products to the discount
            $discount->products()->attach($this->product_ids);
            
            DB::commit();
            
            // Reset form and show success message
            $this->reset();
            session()->flash('success', '✨ Discount created successfully!');
            
            return redirect()->route('discounts.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', '❌ Something went wrong! Please try again.');
        }
    }
    public function generateCode()
    {
        do {
            $this->code = strtoupper(Str::random(8)); // Generate a random alphanumeric code
            $existingDiscount = Discount::where('code', $this->code)->first();
        } while ($existingDiscount); // Repeat if the code already exists
    
        // Clear any previous code errors
        $this->resetErrorBag('code');
    }

    public function mount()
    {
        $this->loadProducts();
    }

    public function loadProducts()
    {
        $query = Product::query()
            ->select('id', 'name', 'price')
            ->orderBy('name');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $this->products = $query->take($this->perPage)->get();
    }

    public function loadMore()
    {
        if ($this->loadingMore) return;

        $this->loadingMore = true;
        $this->page++;

        $query = Product::query()
            ->select('id', 'name', 'price')
            ->orderBy('name')
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage);

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $newProducts = $query->get();
        
        if ($newProducts->count() > 0) {
            $this->products = $this->products->concat($newProducts);
        }

        $this->loadingMore = false;
    }

    public function updatedSearch()
    {
        $this->reset(['page', 'products']);
        $this->loadProducts();
    }

    public function toggleProduct($id)
    {
        if (in_array($id, $this->product_ids)) {
            $this->product_ids = array_values(array_diff($this->product_ids, [$id]));
        } else {
            $this->product_ids[] = $id;
        }
    }

    public function clearSelection()
    {
        $this->product_ids = [];
    }
    public function render()
    {
        return view('livewire.create.create-discount');
    }
}
