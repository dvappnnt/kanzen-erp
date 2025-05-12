<?php

namespace App\Console\Commands;

use App\Models\ProductVariation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateProductVariationCodes extends Command
{
    protected $signature = 'product:generate-codes';
    protected $description = 'Generate SKU and barcode for product variations where they are null';

    public function handle()
    {
        $this->info('Starting to generate SKUs and barcodes...');

        $variations = ProductVariation::whereNull('sku')
            ->orWhereNull('barcode')
            ->with('product') // Eager load product relation
            ->get();

        if ($variations->isEmpty()) {
            $this->info('No product variations found with missing SKU or barcode.');
            return;
        }

        $bar = $this->output->createProgressBar($variations->count());
        $bar->start();

        foreach ($variations as $variation) {
            if (!$variation->sku) {
                // Get first 3 letters of product name
                $productPrefix = substr($variation->product->name ?? '', 0, 3);
                // Get first 3 letters of variation name
                $variationPrefix = substr($variation->name ?? '', 0, 3);
                // Get datetime in format YMDHI (Year Month Day Hour Minute)
                $datetime = now()->format('ymdHi');
                
                // Combine all parts and convert to uppercase
                $variation->sku = strtoupper($productPrefix . $variationPrefix . $datetime);
            }

            if (!$variation->barcode) {
                $variation->barcode = Str::random(10);
            }

            $variation->save();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Successfully generated codes for ' . $variations->count() . ' product variations.');
    }
} 