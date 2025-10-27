<?php

namespace App\Livewire\Create;

use App\Models\Brand;
use App\Models\Category;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Product;
use App\Models\Image;

class CreateProduct extends Component
{
    use WithFileUploads;

    public $name, $description, $price, $stock_quantity, $category_id,$brand_id;
    public $images = [];
    public $categories ,$brands;
    protected $rules = [
        'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'stock_quantity' => 'required|integer',
            'category_id' => 'required|integer',
            'brand_id' => 'required|integer',

            'images.*' => 'image|max:1024', // 1MB Max per image
    ];

    public function updatedImages()
    {
        $this->validate([
            'images.*' => 'image|max:10240', // Max file size: 10MB
        ]);
    }

    public function submit()
    {
        $this->validate();

        $product = Product::create([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock_quantity' => $this->stock_quantity,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
        ]);

        foreach ($this->images as $image) {
            $path = $image->store('storage/images');
            Image::create([
                'product_id' => $product->id,
                'file_path' => $path,
            ]);
            $this->images = [];
        }

        session()->flash('message', 'Product successfully created with images.');
        return $this->reset(['name', 'description', 'price', 'stock_quantity',]);

    }

    public function cancel()  {
        return  $this->reset(['name', 'description', 'price', 'stock_quantity','file_path','product_id']);
  
      }
    public function mount()
    {
        $this->categories = Category::all(); 
        $this->brands=Brand::all();
    }
    public function render()
    {
        return view('livewire.create.create-product');
    }
}
