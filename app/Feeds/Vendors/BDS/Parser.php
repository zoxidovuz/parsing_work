<?php

namespace App\Feeds\Vendors\BDS;

use App\Feeds\Parser\HtmlParser;
use App\Helpers\FeedHelper;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{
    private array $dims = [];
    private ?string $description;
    private array $attributes = [];
    private array $short_description = [];

    public function beforeParse(): void
    {
        $tab_description = $this->getHtml('#tab-description');

        preg_match('/(?<content_list><ul>.*?<\/ul>)/s', $tab_description, $match);
        preg_match_all('/(?<content_list><li>.*?<\/li>)/u', $match['content_list'] ?? '', $match_li);

        foreach ($match_li['content_list'] as $element) {
            $element = str_replace(['<li>', '</li>'], '', $element);
            if (str_contains($element, ':')) {
                [$key, $value] = explode(':', $element);
                $this->attributes += [$key => $value];
            } else {
                $this->short_description[] = $element;
            }
            if (str_contains($element, 'Dimensions')) {
                preg_match_all('/(\d*\.)?\d+/u', $element, $match);
                $this->dims['x'] = isset($match[0][0]) ? FeedHelper::convert(StringHelper::getFloat($match[0][0]), 0.39) : null;
                $this->dims['y'] = isset($match[0][1]) ? FeedHelper::convert(StringHelper::getFloat($match[0][1]), 0.39) : null;
                $this->dims['z'] = isset($match[0][2]) ? FeedHelper::convert(StringHelper::getFloat($match[0][2]), 0.39) : null;
            }
            $tab_description = str_replace("<li>$element</li>", '', $tab_description);
        }

        $this->description = FeedHelper::cleanProductDescription(str_replace('Product Description', '', $tab_description));
    }

    public function getMpn(): string
    {
        return $this->getHtml('[data-product-sku]');
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
        return [$categories[1] ?? ''];
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
        return $this->getHtml('[data-product-upc]');
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