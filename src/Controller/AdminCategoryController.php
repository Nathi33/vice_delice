<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
final class AdminCategoryController extends AbstractController
{
    
    #[Route('', name: 'admin_category_index', methods: ['GET'])]
    public function index(
        Request $request,
        CategoryRepository $categoryRepository
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $search = trim($request->query->getString('search', ''));
        $parentId = $request->query->getInt('parent', 0);
        $status = $request->query->getString('status', '');
        $sort = $request->query->getString('sort', 'name');
        $direction = $request->query->getString('direction', 'ASC');

        $result = $categoryRepository->paginateAdmin(
            page: $page,
            limit: 20,
            search: $search,
            parentId: $parentId > 0 ? $parentId : null,
            status: $status !== '' ? $status : null,
            sort: $sort,
            direction: $direction
        );

        return $this->render('admin/admin_category/index.html.twig', [
            'categories' => $result['items'],
            'totalCategories' => $result['total'],
            'totalPages' => $result['pages'],
            'currentPage' => $result['currentPage'],
            'search' => $search,
            'selectedParent' => $parentId,
            'selectedStatus' => $status,
            'selectedSort' => $sort,
            'selectedDirection' => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
            'parentCategories' => $categoryRepository->findBy(
                ['parent' => null],
                ['name' => 'ASC']
            ),
        ]);
    }

    #[Route('/autocomplete', name: 'admin_category_autocomplete', methods: ['GET'])]
    public function autocomplete(
        Request $request,
        CategoryRepository $categoryRepository
    ): JsonResponse {
        $search = trim($request->query->getString('q', ''));

        if (mb_strlen($search) < 2) {
            return $this->json([]);
        }

        $categories = $categoryRepository->findAdminSuggestions($search, 8);

        $results = array_map(
            static fn (Category $category): array => [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'externalReference' => $category->getExternalReference(),
            ],
            $categories
        );

        return $this->json($results);
    }

    #[Route('/new', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute(
                'admin_category_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('admin/admin_category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_category_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Category $category): Response
    {
        return $this->render('admin/admin_category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_category_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute(
                'admin_category_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('admin/admin_category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_category_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete' . $category->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute(
            'admin_category_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}
