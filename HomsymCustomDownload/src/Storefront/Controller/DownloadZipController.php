<?php declare(strict_types=1);

namespace Download\Storefront\Controller;

use Download\Core\Content\CustomDownload\SalesChannel\DownloadZipRouteResponse;
use Download\Core\Content\CustomDownload\SalesChannel\AbstractDownloadZipRoute;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['storefront']])]
class DownloadZipController extends StorefrontController
{

    public function __construct(private readonly AbstractDownloadZipRoute $route)
    {
    }

    #[Route(path: '/account/order/download/zip/{orderId}/{downloadId}', name: 'frontend.account.order.download.zip', methods: ['GET'])]
    public function downloadFileZip(Request $request, SalesChannelContext $context): Response
    {
        if (!$context->getCustomer()) {
            return $this->redirectToRoute(
                'frontend.account.order.single.page',
                [
                    'deepLinkCode' => $request->get('deepLinkCode', false),
                ]
            );
        }

        return $this->route->load($request, $context);
    }
}