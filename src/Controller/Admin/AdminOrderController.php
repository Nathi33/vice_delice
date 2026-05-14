<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/orders')]
#[IsGranted('ROLE_ADMIN')]
class AdminOrderController extends AbstractController
{
    // =========================
    // LISTE DES COMMANDES
    // =========================
    #[Route('/', name: 'admin_orders_index')]
    public function index(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findBy([], ['id' => 'DESC']);

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders
        ]);
    }

    // =========================
    // DÉTAIL COMMANDE
    // =========================
    #[Route('/{id}', name: 'admin_orders_show')]
    public function show(Order $order): Response
    {
        return $this->render('admin/orders/show.html.twig', [
            'order' => $order
        ]);
    }

    // =========================
    // UPDATE STATUS
    // =========================
    #[Route('/{id}/status', name: 'admin_orders_status', methods: ['POST'])]
    public function updateStatus(
        Order $order,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        $status = $request->request->get('status');

        $allowed = ['pending', 'paid', 'cancelled', 'shipped', 'refunded'];

        if (!in_array($status, $allowed, true)) {
            $this->addFlash('error', 'Statut invalide');
            return $this->redirectToRoute('admin_orders_show', ['id' => $order->getId()]);
        }

        $order->setStatus($status);
        $em->flush();

        $this->addFlash('success', 'Statut mis à jour');

        return $this->redirectToRoute('admin_orders_show', [
            'id' => $order->getId()
        ]);
    }
}