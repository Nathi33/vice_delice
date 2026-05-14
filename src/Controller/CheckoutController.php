<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\ProductRepository;
use App\Service\CartService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'checkout_index')]
    #[IsGranted('ROLE_USER')]
    public function index(CartService $cartService, Request $request): Response
    {
        $cart = $cartService->getCart();

        if (empty($cart)) {
            return $this->redirectToRoute('product_index');
        }

        if (!$request->getSession()->has('checkout_token')) {
            $request->getSession()->set(
                'checkout_token',
                bin2hex(random_bytes(16))
            );
        }

        return $this->render('checkout/index.html.twig', [
            'checkout_token' => $request->getSession()->get('checkout_token')
        ]);
    }

    #[Route('/checkout/validate', name: 'checkout_validate', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function validate(
        Request $request,
        CartService $cartService,
        ProductRepository $productRepository,
        EntityManagerInterface $em,
        StripeService $stripeService
    ): Response {

        $session = $request->getSession();
        $cart = $cartService->getCart();

        if (empty($cart)) {
            return $this->redirectToRoute('product_index');
        }

        // token anti double submit
        $tokenForm = $request->request->get('checkout_token');
        $tokenSession = $session->get('checkout_token');

        if (!$tokenForm || $tokenForm !== $tokenSession) {
            $this->addFlash('error', 'Commande expirée.');
            return $this->redirectToRoute('checkout_index');
        }

        $session->remove('checkout_token');

        // form
        $firstName = trim($request->request->get('firstName'));
        $lastName  = trim($request->request->get('lastName'));
        $email     = trim($request->request->get('email'));
        $address   = trim($request->request->get('address'));
        $postalCode = trim($request->request->get('postalCode'));
        $city      = trim($request->request->get('city'));
        $adult     = $request->request->get('adult');

        if (!$firstName || !$lastName || !$email || !$address || !$postalCode || !$city) {
            $this->addFlash('error', 'Tous les champs sont obligatoires.');
            return $this->redirectToRoute('checkout_index');
        }

        if ($adult !== '1') {
            $this->addFlash('error', 'Vous devez être majeur.');
            return $this->redirectToRoute('checkout_index');
        }

        // ORDER
        $order = new Order();

        $order->setUser($this->getUser());
        $order->setStatus('pending');

        $order->setFirstName($firstName);
        $order->setLastName($lastName);
        $order->setEmail($email);
        $order->setAddress($address);
        $order->setPostalCode($postalCode);
        $order->setCity($city);
        $order->setAdultConfirmed(true);

        $total = 0;

        foreach ($cart as $productId => $qty) {

            $product = $productRepository->find($productId);

            if (!$product) continue;

            $price = (float) $product->getPrice();
            $subtotal = $price * $qty;

            $item = new OrderItem();
            $item->setOrder($order);
            $item->setProduct($product);
            $item->setProductName($product->getName());
            $item->setProductSlug($product->getSlug());
            $item->setQuantity($qty);
            $item->setPrice($price);
            $item->setSubtotal($subtotal);

            $order->addItem($item);

            $total += $subtotal;
        }

        $order->setTotal($total);

        $em->persist($order);
        $em->flush();

        // Stripe
        $stripeUrl = $stripeService->createCheckoutSession($order);

        return $this->redirect($stripeUrl);
    }

    #[Route('/checkout/success/{id}', name: 'checkout_success')]
    #[IsGranted('ROLE_USER')]
    public function success(Order $order): Response
    {
        return $this->render('checkout/success.html.twig', [
            'order' => $order
        ]);
    }

    #[Route('/checkout/cancel/{id}', name: 'checkout_cancel')]
    #[IsGranted('ROLE_USER')]
    public function cancel(Order $order): Response
    {
        return $this->render('checkout/cancel.html.twig', [
            'order' => $order
        ]);
    }
}