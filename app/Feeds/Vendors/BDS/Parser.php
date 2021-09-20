<?php

namespace App\Feeds\Vendors\BDS;

use App\Feeds\Parser\HtmlParser;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{
    private string $mpn = '';
    private ?string $upc = '';

    public function beforeParse(): void
    {
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
        return $this->getText('#tab-description div');
    }

    public function getImages(): array
    {
        return array_values(array_unique($this->getSrcImages('figure.fancy-gallery img')));
    }

    public function getBrand(): ?string
    {
        return $this->getText('.productView-brand a');
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
}