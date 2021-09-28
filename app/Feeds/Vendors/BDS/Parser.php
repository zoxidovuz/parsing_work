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
    private ?array $description;

    public function beforeParse(): void
    {
        $this->description = FeedHelper::getShortsAndAttributesInDescription(
            $this->getHtml('#tab-description'),
            ['/(?<content_list><li>.*?<\/li>)/u']
        );

        foreach ($this->description['short_description'] as $short_description) {
            if (str_contains($short_description, 'Dimensions')) {
                preg_match_all('/(\d*\.)?\d+/u', 'Dimensions: 29.5 * 13.5 * 27 cm.', $match);
                $this->dims['x'] = isset($match[0][0]) ? FeedHelper::convertCmToInch(StringHelper::getFloat($match[0][0])) : null;
                $this->dims['y'] = isset($match[0][1]) ? FeedHelper::convertCmToInch(StringHelper::getFloat($match[0][1])) : null;
                $this->dims['z'] = isset($match[0][2]) ? FeedHelper::convertCmToInch(StringHelper::getFloat($match[0][2])) : null;
            }
        }

        $product_info = $this->getHtml('.productView-info');
        preg_match('#dd class="productView-info-value" data-product-sku>(.*?)<\/dd>#s', $product_info, $sku_value);
        preg_match('#dd class="productView-info-value" data-product-upc>(.*?)<\/dd>#s', $product_info, $upc_value);
        if (isset($sku_value[1])) {
            $this->mpn = $sku_value[1];
        };
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
        return StringHelper::normalizeSpaceInString($this->description['description']);
    }

    public function getShortDescription(): array
    {
        return $this->description["short_description"];
    }

    public function getAttributes(): ?array
    {
        return $this->description["attributes"];
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
        array_shift( $categories );
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