<?php

namespace App\Feeds\Vendors\BDS;

use App\Feeds\Parser\HtmlParser;
use App\Helpers\FeedHelper;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{
    private array $dims = [];
    private string $mpn = '';
    private ?string $upc = '';
    private ?string $description;
    private ?array $attributes;
    private array $short_description = [];

    public function beforeParse(): void
    {
        $description = $this->getHtml('#tab-description');
//        $this->description = FeedHelper::getShortsAndAttributesInDescription(
//            $this->getHtml('#tab-description'),
//            ['/(?<content_list><li>.*?<\/li>)/u']
//        );

        preg_match_all('/(?<content_list><ul>.*?<\/ul>)/u', $description, $short_description);

        if ($short_description["content_list"]) {
            // Short description
            if (isset($short_description["content_list"][0])) {
                $description = str_replace($short_description["content_list"][0], '', $description);
                preg_match_all('/(?<content_list><li>.*?<\/li>)/u', $short_description["content_list"][0], $short);
                if (isset($short['content_list'])) {
                    $this->short_description = [];
                    foreach ($short['content_list'] as $content) {
                        $this->short_description[] = str_replace(['<li>', '</li>'], '', $content);
                    }
                }
            }
            // Attributes
            if (isset($short_description["content_list"][1])) {
                preg_match_all('/(?<content_list><li>.*?<\/li>)/u', $short_description["content_list"][1], $attrs);
                if (isset($attrs['content_list'])) {
                    $this->attributes = [];
                    foreach ($attrs['content_list'] as $content) {
                        $this->attributes[] = str_replace(['<li>', '</li>'], '', $content);
                    }
                }
            }
        }

        $this->description = str_replace('Product Description', '', $description);

        foreach ($this->short_description as $short_description) {
            if (str_contains($short_description, 'Dimensions')) {
                preg_match_all('/(\d*\.)?\d+/u', 'Dimensions: 29.5 * 13.5 * 27 cm.', $match);
                $this->dims['x'] = isset($match[0][0]) ? FeedHelper::convert(StringHelper::getFloat($match[0][0]), 0.39) : null;
                $this->dims['y'] = isset($match[0][1]) ? FeedHelper::convert(StringHelper::getFloat($match[0][1]), 0.39) : null;
                $this->dims['z'] = isset($match[0][2]) ? FeedHelper::convert(StringHelper::getFloat($match[0][2]), 0.39) : null;
            }
        }

        $product_info = $this->getHtml('.productView-info');
        preg_match('#dd class="productView-info-value" data-product-sku>(.*?)<\/dd>#s', $product_info, $sku_value);
        preg_match('#dd class="productView-info-value" data-product-upc>(.*?)<\/dd>#s', $product_info, $upc_value);
        if (isset($sku_value[1])) {
            $this->mpn = $sku_value[1];
        }
        if (isset($upc_value[1])) {
            $this->upc = $upc_value[1];
        };

    }

    public function getMpn(): string
    {
        return $this->mpn;
    }

    public function getProduct(): string
    {
        return $this->getText('.productView-title');
    }

    public function getDescription(): string
    {
        return StringHelper::normalizeSpaceInString($this->description);
    }

    public function getShortDescription(): array
    {
        return $this->short_description;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes ?? null;
    }

    public function getImages(): array
    {
        return array_values(array_unique($this->getSrcImages('figure.fancy-gallery img')));
    }

    public function getBrand(): ?string
    {
        return $this->getText('.productView-brand a');
    }

    public function getCategories(): array
    {
        $categories = $this->getContent('.breadcrumb a');
        array_shift($categories);
        return $categories;
    }

    public function getCostToUs(): float
    {
        return StringHelper::getMoney($this->getText('.price--withoutTax'));
    }

    public function getMinAmount(): ?int
    {
        return $this->getAttr('input[name="qty[]"]', 'value') ?? 1;
    }

    public function getAvail(): ?int
    {
        return self::DEFAULT_AVAIL_NUMBER;
    }

    public function getUpc(): ?string
    {
        return $this->upc;
    }

    public function getDimX(): ?float
    {
        return $this->dims['x'] ?? null;
    }

    public function getDimY(): ?float
    {
        return $this->dims['y'] ?? null;
    }

    public function getDimZ(): ?float
    {
        return $this->dims['z'] ?? null;
    }
}