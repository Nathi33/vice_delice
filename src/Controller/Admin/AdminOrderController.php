<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\KernelInterface;

#[Route('/admin/orders')]
#[IsGranted('ROLE_ADMIN')]
class AdminOrderController extends AbstractController
{
    #[Route('/', name: 'admin_orders_index')]
    public function index(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findBy([], ['id' => 'DESC']);

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}', name: 'admin_orders_show', requirements: ['id' => '\d+'])]
    public function show(Order $order, KernelInterface $kernel): Response
    {
        $invoicePath = null;

        if ($order->getInvoiceNumber() !== null) {
            $candidate = $kernel->getProjectDir()
                . '/var/invoices/'
                . $order->getInvoiceNumber()
                . '.pdf';

            if (is_file($candidate)) {
                $invoicePath = $candidate;
            }
        }

        return $this->render('admin/orders/show.html.twig', [
            'order' => $order,
            'invoiceAvailable' => $invoicePath !== null,
        ]);
    }

    
    #[Route(
        '/{id}/invoice',
        name: 'admin_orders_invoice_download',
        methods: ['GET'],
        requirements: ['id' => '\d+']
    )]
    public function downloadInvoice(
        Order $order,
        KernelInterface $kernel
    ): Response {
        $invoiceNumber = $order->getInvoiceNumber();

        if (
            $invoiceNumber === null
            || !preg_match('/^VD-\d{4}-\d{6}$/', $invoiceNumber)
        ) {
            throw $this->createNotFoundException(
                'Aucune facture valide n’est associée à cette commande.'
            );
        }

        $invoicePath = $kernel->getProjectDir()
            . '/var/invoices/'
            . $invoiceNumber
            . '.pdf';

        if (
            !is_file($invoicePath)
            || !is_readable($invoicePath)
            || filesize($invoicePath) === 0
        ) {
            throw $this->createNotFoundException(
                'Le fichier PDF de cette facture est introuvable ou vide.'
            );
        }

        $handle = fopen($invoicePath, 'rb');

        if ($handle === false) {
            throw $this->createNotFoundException(
                'Impossible de lire le fichier de cette facture.'
            );
        }

        try {
            $signature = fread($handle, 5);
        } finally {
            fclose($handle);
        }

        if ($signature !== '%PDF-') {
            throw $this->createNotFoundException(
                'Le fichier associé à cette facture n’est pas un PDF valide.'
            );
        }

        return $this->file($invoicePath, $invoiceNumber . '.pdf');
    }
 
    #[Route(
        '/{id}/status',
        name: 'admin_orders_status',
        methods: ['POST'],
        requirements: ['id' => '\d+']
    )]
    public function updateStatus(
        Order $order,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Vérifier le jeton CSRF avant toute modification.
        $token = $request->request->get('_token');

        if (
            !is_string($token)
            || !$this->isCsrfTokenValid(
                'order_status_' . $order->getId(),
                $token
            )
        ) {
            throw $this->createAccessDeniedException(
                'Le jeton de sécurité est invalide. Rechargez la page et réessayez.'
            );
        }

        $status = $request->request->get('status');
        $currentStatus = $order->getStatus();


        // Seuls ces statuts sont accessibles depuis le formulaire.
        $allowed = ['pending', 'cancelled', 'shipped'];

        if (!is_string($status) || !in_array($status, $allowed, true)) {
            $this->addFlash(
                'error',
                'Statut invalide. Le paiement doit être confirmé par Stripe.'
            );

            return $this->redirectToRoute('admin_orders_show', [
                'id' => $order->getId(),
            ]);
        }

        // Une commande annulée ou remboursée est verrouillée ici.
        if (in_array($currentStatus, ['cancelled', 'refunded'], true)) {
            $this->addFlash(
                'error',
                'Cette commande est annulée ou remboursée. Son statut ne peut pas être modifié ici.'
            );

            return $this->redirectToRoute('admin_orders_show', [
                'id' => $order->getId(),
            ]);
        }

        // Le paiement confirmé ne peut pas être annulé ou remis en attente ici.
        if (
            $currentStatus === 'paid'
            && in_array($status, ['pending', 'cancelled'], true)
        ) {
            $this->addFlash(
                'error',
                'Cette commande est payée. Un éventuel remboursement nécessite un traitement spécifique.'
            );

            return $this->redirectToRoute('admin_orders_show', [
                'id' => $order->getId(),
            ]);
        }

        // Une commande expédiée ne peut pas revenir à un statut antérieur.
        if (
            $currentStatus === 'shipped'
            && $status !== 'shipped'
        ) {
            $this->addFlash(
                'error',
                'Une commande expédiée ne peut pas revenir à un statut antérieur.'
            );

            return $this->redirectToRoute('admin_orders_show', [
                'id' => $order->getId(),
            ]);
        }

        // Pas de changement inutile.
        if ($currentStatus === $status) {
            $this->addFlash('success', 'Le statut est déjà à jour.');

            return $this->redirectToRoute('admin_orders_show', [
                'id' => $order->getId(),
            ]);
        }

        $order->setStatus($status);
        $em->flush();

        $this->addFlash('success', 'Statut mis à jour.');

        return $this->redirectToRoute('admin_orders_show', [
            'id' => $order->getId(),
        ]);
    }

}
