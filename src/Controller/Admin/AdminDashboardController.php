<?php

namespace App\Controller\Admin;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        OrderRepository $orderRepository,
        ProductRepository $productRepository
    ): Response {

        // =========================
        // COMMANDES
        // =========================

        $orders = $orderRepository->findAll();

        $totalOrders = count($orders);

        $paidOrders = array_filter($orders, function ($order) {
            return $order->getStatus() === 'paid';
        });

        $paidOrdersCount = count($paidOrders);

        // =========================
        // CHIFFRE D'AFFAIRES
        // =========================

        $revenue = 0;

        foreach ($paidOrders as $order) {
            $revenue += (float) $order->getTotal();
        }

        // =========================
        // PRODUITS
        // =========================

        $productsCount = count($productRepository->findAll());

        // =========================
        // DERNIÈRES COMMANDES
        // =========================

        $latestOrders = $orderRepository->findBy(
            [],
            ['id' => 'DESC'],
            10
        );

        return $this->render('admin/dashboard/index.html.twig', [
            'totalOrders' => $totalOrders,
            'paidOrdersCount' => $paidOrdersCount,
            'revenue' => $revenue,
            'productsCount' => $productsCount,
            'latestOrders' => $latestOrders
        ]);
    }
}