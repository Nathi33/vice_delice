<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/cart', name: 'cart_show')]
    public function show(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        return $this->render('order/cart.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(
        int $id,
        SessionInterface $session,
        ProductRepository $productRepository
    ): JsonResponse {

        $cart = $session->get('cart', []);

        if (!isset($cart[$id])) {
            $cart[$id] = 0;
        }

        $cart[$id]++;

        $session->set('cart', $cart);

        return new JsonResponse([
            'success' => true,
            'cart' => $cart
        ]);
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove')]
    public function remove(int $id, SessionInterface $session): JsonResponse
    {
        $cart = $session->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
        }

        $session->set('cart', $cart);

        return new JsonResponse([
            'success' => true
        ]);
    }

    #[Route('/mes-commandes', name: 'app_orders')]
public function orders(OrderRepository $orderRepository): Response
{
    $user = $this->getUser();

    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $orders = $orderRepository->findBy(
        ['user' => $user],
        ['createdAt' => 'DESC']
    );

    return $this->render('order/orders.html.twig', [
        'orders' => $orders,
    ]);
}

    #[Route('/order/checkout', name: 'order_checkout')]
    public function checkout(
        SessionInterface $session,
        ProductRepository $productRepository,
        EntityManagerInterface $em
    ): Response {

        $cart = $session->get('cart', []);

        if (!$cart) {
            throw $this->createNotFoundException('Panier vide');
        }

        $order = new Order();
        $order->setUser($this->getUser());

        foreach ($cart as $productId => $qty) {

            $product = $productRepository->find($productId);

            if (!$product) {
                continue;
            }

            $item = new OrderItem();
            $item->setProduct($product);
            $item->setQuantity($qty);
            $item->setPrice($product->getPrice());
            $item->setOrder($order);

            $order->getItems()->add($item);
        }

        $em->persist($order);
        $em->flush();

        $session->remove('cart');

        return $this->render('order/success.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/mes-commandes/{id}/commander-a-nouveau', name: 'order_reorder')]
    public function reorder(
        Order $order,
        SessionInterface $session,
        ProductRepository $productRepository
    ): Response {

        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 🔐 Vérifie que la commande appartient bien au client connecté
        if ($order->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $cart = $session->get('cart', []);

        $addedProducts = 0;
        $unavailableProducts = 0;

        foreach ($order->getItems() as $item) {

            $product = $item->getProduct();

            if (!$product) {
                $unavailableProducts++;
                continue;
            }

            // Vérifie que le produit existe toujours
            $currentProduct = $productRepository->find($product->getId());

            if (!$currentProduct) {
                $unavailableProducts++;
                continue;
            }

            $productId = $currentProduct->getId();
            $quantity = $item->getQuantity();

            if (isset($cart[$productId])) {
                $cart[$productId] += $quantity;
            } else {
                $cart[$productId] = $quantity;
            }

            $addedProducts++;
        }

        $session->set('cart', $cart);

        if ($addedProducts > 0) {
            $this->addFlash(
                'success',
                'Les produits de votre commande ont été ajoutés à votre panier.'
            );
        }

        if ($unavailableProducts > 0) {
            $this->addFlash(
                'warning',
                'Certains produits de cette commande ne sont plus disponibles.'
            );
        }

        return $this->redirectToRoute('cart_show');
    }

    #[Route('/cart/count', name: 'cart_count')]
    public function count(SessionInterface $session): JsonResponse
    {
        $cart = $session->get('cart', []);

        $count = array_sum($cart);

        return new JsonResponse([
            'count' => $count
        ]);
    }
}