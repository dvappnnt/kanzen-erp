<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attribute;
use App\Models\AttributeValue;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        // Attributes
        $condition = Attribute::create(['name' => 'Condition']);
        $color = Attribute::create(['name' => 'Color']);
        $size = Attribute::create(['name' => 'Size']);
        $material = Attribute::create(['name' => 'Material']);
        $flavor = Attribute::create(['name' => 'Flavor']);
        $packaging = Attribute::create(['name' => 'Packaging']);
        $dosage = Attribute::create(['name' => 'Dosage']);
        $voltage = Attribute::create(['name' => 'Voltage']);
        $capacity = Attribute::create(['name' => 'Capacity']);
        $brand = Attribute::create(['name' => 'Brand']);
        $model = Attribute::create(['name' => 'Model']);
        $sku = Attribute::create(['name' => 'SKU']);
        $weight = Attribute::create(['name' => 'Weight']);
        $description = Attribute::create(['name' => 'Description']);

        // Attribute Values
        // Standard Attribute Values
        foreach (['New', 'Used', 'Refurbished'] as $val) {
            AttributeValue::create(['attribute_id' => $condition->id, 'value' => $val]);
        }

        foreach (['Red', 'Blue', 'Green', 'Black', 'White'] as $val) {
            AttributeValue::create(['attribute_id' => $color->id, 'value' => $val]);
        }

        foreach (['XS', 'S', 'M', 'L', 'XL'] as $val) {
            AttributeValue::create(['attribute_id' => $size->id, 'value' => $val]);
        }

        foreach (['Cotton', 'Polyester', 'Wool'] as $val) {
            AttributeValue::create(['attribute_id' => $material->id, 'value' => $val]);
        }

        foreach (['Strawberry', 'Chocolate', 'Vanilla'] as $val) {
            AttributeValue::create(['attribute_id' => $flavor->id, 'value' => $val]);
        }

        foreach (['Box', 'Bottle', 'Strip'] as $val) {
            AttributeValue::create(['attribute_id' => $packaging->id, 'value' => $val]);
        }

        foreach (['250mg', '500mg', '1000mg'] as $val) {
            AttributeValue::create(['attribute_id' => $dosage->id, 'value' => $val]);
        }

        foreach (['110V', '220V'] as $val) {
            AttributeValue::create(['attribute_id' => $voltage->id, 'value' => $val]);
        }

        foreach (['128GB', '256GB', '512GB', '1TB'] as $val) {
            AttributeValue::create(['attribute_id' => $capacity->id, 'value' => $val]);
        }

        foreach (['100g', '250g', '500g', '1kg', '2kg', '5kg'] as $val) {
            AttributeValue::create(['attribute_id' => $weight->id, 'value' => $val]);
        }

        // Note: Brand, Model, SKU, and Description don't need predefined values
        // as they will be unique to each product
    }
}
