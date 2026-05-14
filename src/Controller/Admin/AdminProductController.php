<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/products')]
#[IsGranted('ROLE_ADMIN')]
class AdminProductController extends AbstractController
{
    // =========================
    // LISTE + FILTRES + PAGINATION
    // =========================
    #[Route('/', name: 'admin_product_index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {

        $search = trim($request->query->get('search', ''));
        $status = $request->query->get('status');
        $category = $request->query->get('category');
        $stock = $request->query->get('stock');
        $image = $request->query->get('image');

        $qb = $productRepository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.productImages', 'pi')
            ->addSelect('c')
            ->groupBy('p.id')
            ->orderBy('p.id', 'DESC');

        // =========================
        // SEARCH
        // =========================
        if ($search) {
            $qb->andWhere('
                p.name LIKE :search
                OR p.slug LIKE :search
                OR p.supplierReference LIKE :search
            ')
            ->setParameter('search', '%' . $search . '%');
        }

        // =========================
        // STATUS
        // =========================
        if ($status === 'active') {
            $qb->andWhere('p.isActive = true');
        }

        if ($status === 'inactive') {
            $qb->andWhere('p.isActive = false');
        }

        // =========================
        // CATEGORY
        // =========================
        if ($category) {
            $qb->andWhere('c.id = :category')
               ->setParameter('category', $category);
        }

        // =========================
        // STOCK FILTERS
        // =========================
        if ($stock === 'low') {
            $qb->andWhere('p.stock <= 5')
               ->andWhere('p.stock > 0');
        }

        if ($stock === 'out') {
            $qb->andWhere('p.stock <= 0');
        }

        // =========================
        // WITHOUT IMAGE (FIX PROPRE)
        // =========================
        if ($image === 'missing') {
            $qb->having('COUNT(pi.id) = 0');
        }

        $products = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/admin_product/index.html.twig', [
            'products' => $products,
            'search' => $search,
            'status' => $status,
            'category' => $category,
            'stock' => $stock,
            'image' => $image,
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    // =========================
    // CREATE PRODUCT
    // =========================
    #[Route('/new', name: 'admin_product_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                $targetDirectory = $this->getParameter('uploads_directory') . '/products';

                if (!is_dir($targetDirectory)) {
                    mkdir($targetDirectory, 0777, true);
                }

                try {
                    $imageFile->move($targetDirectory, $newFilename);
                } catch (FileException $e) {
                    throw new \Exception('Erreur upload image : ' . $e->getMessage());
                }

                $image = new ProductImage();
                $image->setUrl('/uploads/products/' . $newFilename);
                $image->setIsMain(true);
                $image->setProduct($product);

                $product->addProductImage($image);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès');

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/admin_product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    // =========================
    // SHOW
    // =========================
    #[Route('/{id}', name: 'admin_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('admin/admin_product/show.html.twig', [
            'product' => $product,
        ]);
    }

    // =========================
    // EDIT
    // =========================
    #[Route('/{id}/edit', name: 'admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {

                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                $targetDirectory = $this->getParameter('uploads_directory') . '/products';

                if (!is_dir($targetDirectory)) {
                    mkdir($targetDirectory, 0777, true);
                }

                try {
                    $imageFile->move($targetDirectory, $newFilename);
                } catch (FileException $e) {
                    throw new \Exception('Erreur upload image : ' . $e->getMessage());
                }

                $image = new ProductImage();
                $image->setUrl('/uploads/products/' . $newFilename);
                $image->setIsMain(false);
                $image->setProduct($product);

                $product->addProductImage($image);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Produit modifié');

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/admin_product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    // =========================
    // DELETE PRODUCT
    // =========================
    #[Route('/{id}', name: 'admin_product_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {

        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {

            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', 'Produit supprimé');
        }

        return $this->redirectToRoute('admin_product_index');
    }

    // =========================
    // DELETE IMAGE
    // =========================
    #[Route('/image/{id}/delete', name: 'admin_product_image_delete', methods: ['POST'])]
    public function deleteImage(
        ProductImage $image,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        if (!$this->isCsrfTokenValid('delete_image'.$image->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public' . $image->getUrl();

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $productId = $image->getProduct()->getId();

        $entityManager->remove($image);
        $entityManager->flush();

        $this->addFlash('success', 'Image supprimée');

        return $this->redirectToRoute('admin_product_edit', [
            'id' => $productId
        ]);
    }

    // =========================
    // SET MAIN IMAGE
    // =========================
    #[Route('/image/{id}/main', name: 'admin_product_image_main', methods: ['POST'])]
    public function setMainImage(
        ProductImage $image,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {

        if (!$this->isCsrfTokenValid('main_image'.$image->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $product = $image->getProduct();

        foreach ($product->getProductImages() as $img) {
            $img->setIsMain(false);
        }

        $image->setIsMain(true);

        $entityManager->flush();

        $this->addFlash('success', 'Image principale mise à jour');

        return $this->redirectToRoute('admin_product_edit', [
            'id' => $product->getId()
        ]);
    }
}