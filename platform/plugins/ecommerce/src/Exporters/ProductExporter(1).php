<?php

namespace Botble\Ecommerce\Exporters;

use Botble\DataSynchronize\Exporter\ExportColumn;
use Botble\DataSynchronize\Exporter\ExportCounter;
use Botble\DataSynchronize\Exporter\Exporter;
use Botble\Ecommerce\Enums\ProductTypeEnum;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\ProductVariation;
use Botble\Language\Facades\Language;
use Botble\Media\Facades\RvMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductExporter extends Exporter
{
    protected bool $isMarketplaceActive;

    protected bool $isEnabledDigital;

    protected array $supportedLocales = [];

    protected string $defaultLanguage;

    public function __construct()
    {
        $this->isMarketplaceActive = is_plugin_active('marketplace');
        $this->isEnabledDigital = EcommerceHelper::isEnabledSupportDigitalProducts();

        if (defined('LANGUAGE_MODULE_SCREEN_NAME')) {
            $this->supportedLocales = Language::getSupportedLocales();
            $this->defaultLanguage = Language::getDefaultLanguage(['lang_code'])->lang_code;
        }
    }

    public function getLabel(): string
    {
        return trans('plugins/ecommerce::products.name');
    }

    public function columns(): array
    {
        $columns = [
            ExportColumn::make('name'),
            ExportColumn::make('description'),
            ExportColumn::make('slug'),
            ExportColumn::make('url')->label('URL'),
            ExportColumn::make('sku')->label('SKU'),
            ExportColumn::make('categories'),
            ExportColumn::make('status'),
            ExportColumn::make('is_featured'),
            ExportColumn::make('brand'),
            ExportColumn::make('product_collections'),
            ExportColumn::make('labels'),
            ExportColumn::make('taxes'),
            ExportColumn::make('image'),
            ExportColumn::make('images'),
            ExportColumn::make('price'),
            ExportColumn::make('product_attributes'),
            ExportColumn::make('import_type'),
            ExportColumn::make('is_variation_default'),
            ExportColumn::make('stock_status'),
            ExportColumn::make('with_storehouse_management'),
            ExportColumn::make('quantity'),
            ExportColumn::make('allow_checkout_when_out_of_stock'),
            ExportColumn::make('sale_price'),
            ExportColumn::make('start_date_sale_price'),
            ExportColumn::make('end_date_sale_price'),
            ExportColumn::make('weight'),
            ExportColumn::make('length'),
            ExportColumn::make('width'),
            ExportColumn::make('height'),
            ExportColumn::make('depth'),
            ExportColumn::make('shipping_width'),
            ExportColumn::make('shipping_height'),
            ExportColumn::make('shipping_depth'),
            ExportColumn::make('shipping_length'),
            ExportColumn::make('cost_per_item'),
            ExportColumn::make('barcode'),
            ExportColumn::make('content'),
            ExportColumn::make('tags'),
            ExportColumn::make('generate_license_code'),
            ExportColumn::make('minimum_order_quantity'),
            ExportColumn::make('maximum_order_quantity'),
            //ExportColumn::make('handle'),
            ExportColumn::make('variant_grams'),
            ExportColumn::make('variant_inventory_tracker'),
            ExportColumn::make('variant_inventory_quantity'),
            ExportColumn::make('variant_inventory_policy'),
            ExportColumn::make('variant_fulfillment_service'),
            ExportColumn::make('variant_requires_shipping'),
            ExportColumn::make('variant_barcode'),
            ExportColumn::make('gift_card'),
            ExportColumn::make('seo_title'),
            ExportColumn::make('seo_description'),
            ExportColumn::make('google_shopping_category'),
            // ExportColumn::make('google_shopping_gender'),
            // ExportColumn::make('google_shopping_age_group'),
            // ExportColumn::make('google_shopping_mpn'),
            // ExportColumn::make('google_shopping_condition'),
            // ExportColumn::make('google_shopping_custom_product'),
            // ExportColumn::make('google_shopping_custom_label_0'),
            // ExportColumn::make('google_shopping_custom_label_1'),
            // ExportColumn::make('google_shopping_custom_label_2'),
            // ExportColumn::make('google_shopping_custom_label_3'),
            // ExportColumn::make('google_shopping_custom_label_4'),
            ExportColumn::make('box_quantity'),
            // ExportColumn::make('technical_table'),
            // ExportColumn::make('technical_spec'),

        ];

        if ($this->isEnabledDigital) {
            $columns[] = ExportColumn::make('product_type');
        }

        if ($this->isMarketplaceActive) {
            $columns[] = ExportColumn::make('vendor');
        }

        foreach ($this->supportedLocales as $locale) {
            if ($locale['lang_code'] !== $this->defaultLanguage) {
                $columns[] = ExportColumn::make("name_{$locale['lang_code']}")
                    ->label('Name (' . strtoupper($locale['lang_code']) . ')');
                $columns[] = ExportColumn::make("description_{$locale['lang_code']}")
                    ->label('Description (' . strtoupper($locale['lang_code']) . ')');
                $columns[] = ExportColumn::make("content_{$locale['lang_code']}")
                    ->label('Content (' . strtoupper($locale['lang_code']) . ')');
            }
        }

        return $columns;
    }

    public function collection(): Collection
    {
        $products = collect();

        $with = [
            'categories',
            'slugable',
            'brand',
            'taxes',
            'productLabels',
            'productCollections',
            'variations',
            'variations.product',
            'variations.configurableProduct',
            'variations.productAttributes.productAttributeSet',
            'tags',
            'productAttributeSets',
        ];

        if ($this->isMarketplaceActive) {
            $with[] = 'store';
        }

        if (count($this->supportedLocales)) {
            $with[] = 'translations';
        }

        Product::query()
            ->select(['*'])
            ->where('is_variation', 0)
            ->with($with)
            ->chunk(400, function ($collection) use (&$products) {
                $products = $products->concat($this->productResults($collection));
            });

        return $products;
    }

    public function counters(): array
    {
        $products = Product::query()->where('is_variation', false)->count();
        $variations = ProductVariation::query()
            ->whereHas('product')
            ->whereHas(
                'configurableProduct',
                fn (Builder $query) => $query->where('is_variation', false)
            )
            ->count();

        return [
            ExportCounter::make()
                ->label(trans('plugins/ecommerce::products.export.total_items'))
                ->value(number_format($products + $variations)),
            ExportCounter::make()
                ->label(trans('plugins/ecommerce::products.export.total_products'))
                ->value(number_format($products)),
            ExportCounter::make()
                ->label(trans('plugins/ecommerce::products.export.total_variations'))
                ->value(number_format($variations)),
        ];
    }

    public function hasDataToExport(): bool
    {
        return Product::query()->exists();
    }

    public function productResults(Collection $products): array
    {
        $results = [];

        foreach ($products as $product) {
            $productAttributes = [];

            if (! $product->is_variation) {
                $productAttributes = $product->productAttributeSets->pluck('title')->all();
            }

            $result = [
                'name' => $product->name,
                'description' => $product->description,
                'slug' => $product->slug,
                'url' => $product->url,
                'sku' => $product->sku,
                'categories' => implode(',', $product->categories->pluck('name')->all()),
                'status' => $product->status->getValue(),
                'is_featured' => $product->is_featured,
                'brand' => $product->brand->name,
                'product_collections' => implode(',', $product->productCollections->pluck('name')->all()),
                'labels' => implode(',', $product->productLabels->pluck('name')->all()),
                'taxes' => implode(',', $product->taxes->pluck('title')->all()),
                'image' => RvMedia::getImageUrl($product->image),
                'images' => collect($product->images)->map(fn ($value) => RvMedia::getImageUrl($value))->implode(','),
                'price' => $product->price,
                'product_attributes' => implode(',', $productAttributes),
                'import_type' => 'product',
                'is_variation_default' => $product->is_variation_default,
                'stock_status' => $product->stock_status->getValue(),
                'with_storehouse_management' => $product->with_storehouse_management,
                'quantity' => $product->quantity,
                'allow_checkout_when_out_of_stock' => $product->allow_checkout_when_out_of_stock,
                'sale_price' => $product->sale_price,
                'start_date_sale_price' => $product->start_date,
                'end_date_sale_price' => $product->end_date,
                'weight' => $product->weight,
                'length' => $product->length,
                'width' => $product->width,
                'height' => $product->height,
                'depth' => $product->depth,
                'shipping_width' => $product->shipping_width,
                'shipping_height' => $product->shipping_height,
                'shipping_depth' => $product->shipping_depth,
                'shipping_length' => $product->shipping_length,
                'cost_per_item' => $product->cost_per_item,
                'barcode' => $product->barcode,
                'content' => $product->content,
                'tags' => implode(',', $product->tags->pluck('name')->all()),
                'generate_license_code' => $product->generate_license_code,
                'minimum_order_quantity' => $product->minimum_order_quantity,
                'maximum_order_quantity' => $product->maximum_order_quantity,
                    // New fields
                //'handle' => $product->handle,
                'variant_grams' => $product->variant_grams,
                'variant_inventory_tracker' => $product->variant_inventory_tracker,
                'variant_inventory_quantity' => $product->variant_inventory_quantity,
                'variant_inventory_policy' => $product->variant_inventory_policy,
                'variant_fulfillment_service' => $product->variant_fulfillment_service,
                'variant_requires_shipping' => $product->variant_requires_shipping,
                'variant_barcode' => $product->variant_barcode,
                'gift_card' => $product->gift_card,
                'seo_title' => $product->seo_title,
                'seo_description' => $product->seo_description,
                'google_shopping_category' => $product->google_shopping_category,
                // 'google_shopping_gender' => $product->google_shopping_gender,
                // 'google_shopping_age_group' => $product->google_shopping_age_group,
                // 'google_shopping_mpn' => $product->google_shopping_mpn,
                // 'google_shopping_condition' => $product->google_shopping_condition,
                // 'google_shopping_custom_product' => $product->google_shopping_custom_product,
                // 'google_shopping_custom_label_0' => $product->google_shopping_custom_label_0,
                // 'google_shopping_custom_label_1' => $product->google_shopping_custom_label_1,
                // 'google_shopping_custom_label_2' => $product->google_shopping_custom_label_2,
                // 'google_shopping_custom_label_3' => $product->google_shopping_custom_label_3,
                // 'google_shopping_custom_label_4' => $product->google_shopping_custom_label_4,
                'box_quantity' => $product->box_quantity,
                // 'technical_table' => $product->technical_table,
                // 'technical_spec' => $product->technical_spec,
            ];

            if ($this->isEnabledDigital) {
                $result['product_type'] = $product->product_type;
            }

            if ($this->isMarketplaceActive) {
                $result['vendor'] = $product->store_id ? $product->store->name : null;
            }

            foreach ($this->supportedLocales as $properties) {
                if ($properties['lang_code'] != $this->defaultLanguage) {
                    $translation = $product->translations->where('lang_code', $properties['lang_code'])->first();

                    $result['name_' . $properties['lang_code']] = $translation ? $translation->name : '';
                    $result['description_' . $properties['lang_code']] = $translation ? $translation->description : '';
                    $result['content_' . $properties['lang_code']] = $translation ? $translation->content : '';
                }
            }

            $results[] = $result;

            if ($product->variations->count()) {
                foreach ($product->variations as $variation) {
                    $productAttributes = $this->getProductAttributes($variation);

                    $data = [
                        'name' => $variation->product->name,
                        'description' => '',
                        'slug' => '',
                        'url' => '',
                        'sku' => $variation->product->sku,
                        'categories' => '',
                        'status' => $variation->product->status->getValue(),
                        'is_featured' => '',
                        'brand' => '',
                        'product_collections' => '',
                        'labels' => '',
                        'taxes' => '',
                        'image' => RvMedia::getImageUrl($variation->product->image),
                        'images' => collect($variation->product->images)->map(fn ($value) => RvMedia::getImageUrl($value))->implode(','),
                        'price' => $variation->product->price,
                        'product_attributes' => implode(',', $productAttributes),
                        'import_type' => 'variation',
                        'is_variation_default' => $variation->is_default,
                        'stock_status' => $variation->product->stock_status->getValue(),
                        'with_storehouse_management' => $variation->product->with_storehouse_management,
                        'quantity' => $variation->product->quantity,
                        'allow_checkout_when_out_of_stock' => $variation->product->allow_checkout_when_out_of_stock,
                        'sale_price' => $variation->product->sale_price,
                        'start_date_sale_price' => $variation->product->start_date,
                        'end_date_sale_price' => $variation->product->end_date,
                        'weight' => $variation->product->weight,
                        'length' => $variation->product->length,
                        'width' => $variation->product->width,
                        'height' => $variation->product->height,
                        'depth' => $variation->product->depth,
                        'shipping_length' => $variation->product->shipping_length,
                        'shipping_depth' => $variation->product->shipping_depth,
                        'shipping_height' => $variation->product->shipping_height,
                        'shipping_width' => $variation->product->shipping_width,
                        'cost_per_item' => $variation->product->cost_per_item,
                        'barcode' => $variation->product->barcode,
                        'content' => '',
                        'tags' => '',
                        'generate_license_code' => $variation->product->generate_license_code,
                        'minimum_order_quantity' => $variation->product->minimum_order_quantity,
                        'maximum_order_quantity' => $variation->product->maximum_order_quantity,
                        //'handle' => $variation->product->handle,
                        'variant_grams' => $variation->product->variant_grams,
                        'variant_inventory_tracker' => $variation->product->variant_inventory_tracker,
                        'variant_inventory_quantity' => $variation->product->variant_inventory_quantity,
                        'variant_inventory_policy' => $variation->product->variant_inventory_policy,
                        'variant_fulfillment_service' => $variation->product->variant_fulfillment_service,
                        'variant_requires_shipping' => $variation->product->variant_requires_shipping,
                        'variant_barcode' => $variation->product->variant_barcode,
                        'gift_card' => $variation->product->gift_card,
                        'seo_title' => $variation->product->seo_title,
                        'seo_description' => $variation->product->seo_description,
                        'google_shopping_category' => $variation->product->google_shopping_category,
                        // 'google_shopping_gender' => $variation->product->google_shopping_gender,
                        // 'google_shopping_age_group' => $variation->product->google_shopping_age_group,
                        // 'google_shopping_mpn' => $variation->product->google_shopping_mpn,
                        // 'google_shopping_condition' => $variation->product->google_shopping_condition,
                        // 'google_shopping_custom_product' => $variation->product->google_shopping_custom_product,
                        // 'google_shopping_custom_label_0' => $variation->product->google_shopping_custom_label_0,
                        // 'google_shopping_custom_label_1' => $variation->product->google_shopping_custom_label_1,
                        // 'google_shopping_custom_label_2' => $variation->product->google_shopping_custom_label_2,
                        // 'google_shopping_custom_label_3' => $variation->product->google_shopping_custom_label_3,
                        // 'google_shopping_custom_label_4' => $variation->product->google_shopping_custom_label_4,
                        'box_quantity' => $variation->product->box_quantity,
                        // 'technical_table' => $variation->product->technical_table,
                        // 'technical_spec' => $variation->product->technical_spec,
                    ];

                    if ($this->isEnabledDigital) {
                        $data['product_type'] = ProductTypeEnum::PHYSICAL;
                    }

                    if ($this->isMarketplaceActive) {
                        $data['vendor'] = '';
                    }

                    foreach ($this->supportedLocales as $properties) {
                        if ($properties['lang_code'] != $this->defaultLanguage) {
                            $translation = $variation->product->translations->where('lang_code', $properties['lang_code'])->first();

                            $data['name_' . $properties['lang_code']] = $translation ? $translation->name : '';
                            $data['description_' . $properties['lang_code']] = $translation ? $translation->description : '';
                            $data['content_' . $properties['lang_code']] = '';
                        }
                    }

                    $results[] = $data;
                }
            }
        }

        return $results;
    }

    public function getProductAttributes(Product|ProductVariation $product): array
    {
        $productAttributes = [];

        foreach ($product->productAttributes as $productAttribute) {
            if ($productAttribute->productAttributeSet) {
                $productAttributes[] = $productAttribute->productAttributeSet->title . ':' . $productAttribute->title;
            }
        }

        return $productAttributes;
    }
}
