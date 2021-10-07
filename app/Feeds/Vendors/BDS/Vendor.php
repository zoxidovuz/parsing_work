<?php

namespace App\Feeds\Vendors\BDS;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\HttpProcessor;

class Vendor extends HttpProcessor
{
    public const CATEGORY_LINK_CSS_SELECTORS = ['.navPages-item > a', '.pagination-link'];
    public const PRODUCT_LINK_CSS_SELECTORS = ['li.product > article > figure > a'];
    public array $first = ['https://www.beddingdropship.com'];

    protected const CHUNK_SIZE = 30;

    public function isValidFeedItem(FeedItem $fi): bool
    {
        return !empty($fi->getMpn());
    }

}