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
        $description = $this->getContent('#tab-description')[0] ?? '';

        preg_match('/(?<content_list><ul>.*?<\/ul>)/s', $tab_description, $match);
        preg_match_all('/(?<content_list><li>.*?<\/li>)/u', $match['content_list'] ?? '', $match_li);

        foreach ($match_li['content_list'] as $element) {
            $element = str_replace(['<li>', '</li>'], '', $element);
            if (str_contains($element, ':')) {
                $this->attributes[] = $element;
            } else {
                $this->short_description[] = $element;
            }
            $description = str_replace($element, '', $description);
        }


        $this->description = str_replace('Product Description', '', $description);

        foreach ($this->short_description as $short_description) {
            if (str_contains($short_description, 'Dimensions')) {
                preg_match_all('/(\d*\.)?\d+/u', $short_description, $match);
                $this->dims['x'] = isset($match[0][0]) ? FeedHelper::convert(StringHelper::getFloat($match[0][0]), 0.39) : null;
                $this->dims['y'] = isset($match[0][1]) ? FeedHelper::convert(StringHelper::getFloat($match[0][1]), 0.39) : null;
                $this->dims['z'] = isset($match[0][2]) ? FeedHelper::convert(StringHelper::getFloat($match[0][2]), 0.39) : null;
            }
        }
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