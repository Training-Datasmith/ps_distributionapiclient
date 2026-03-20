<?php

declare (strict_types=1);
namespace Presta_Shop\Module\Distribution_Api_Client\Controller\Admin;

use Presta_Shop_Bundle\Controller\Admin\Presta_Shop_Admin_Controller;
use Symfony\Component\Http_Foundation\Response;
use Symfony\Component\Routing\Annotation\Route;
class Top_Contributors_Controller extends Presta_Shop_Admin_Controller
{
    /**
     * @Route("/ps_distributionapiclient/top-contributors", name="ps_distributionapiclient_top_contributors")
     */
    public function index(): Response
    {
        return $this->render('@Modules/ps_distributionapiclient/views/templates/admin/top_contributors.html.twig', ['enableSidebar' => false, 'showContentHeader' => true]);
    }
}