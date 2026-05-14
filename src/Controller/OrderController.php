<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
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