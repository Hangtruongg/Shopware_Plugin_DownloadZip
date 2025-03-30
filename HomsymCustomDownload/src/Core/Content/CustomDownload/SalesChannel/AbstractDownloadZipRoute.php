<?php declare(strict_types=1);

namespace Download\Core\Content\CustomDownload\SalesChannel;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
abstract class AbstractDownloadZipRoute
{
    abstract public function getDecorated(): AbstractDownloadZipRoute;

    abstract public function load(Request $request, SalesChannelContext $context): Response;
}