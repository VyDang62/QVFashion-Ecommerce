<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $fillable = ['attribute_id', 'value', 'hex_code'];

    public function attribute(){
        return $this->belongsTo(Attribute::class);
    }

    public function productVariants(){
        return $this->belongsToMany(ProductVariant::class, 'product_variant_attribute_values', 'attribute_value_id', 'variant_id');
    }
}
