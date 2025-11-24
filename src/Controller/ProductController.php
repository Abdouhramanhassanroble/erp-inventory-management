<?php

namespace App\Controller;

use App\Form\ProductType;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(Connection $connection): Response
    {
        $products = $connection->fetchAllAssociative(
            'SELECT * FROM product ORDER BY id DESC'
        );

        // Convertir les dates string en objets DateTime pour Twig
        foreach ($products as &$product) {
            if (isset($product['created_at']) && $product['created_at']) {
                $product['created_at'] = new \DateTime($product['created_at']);
            }
            if (isset($product['updated_at']) && $product['updated_at']) {
                $product['updated_at'] = new \DateTime($product['updated_at']);
            }
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Connection $connection): Response
    {
        // Note: La vérification d'authentification se fait côté client via JavaScript
        // car nous utilisons JWT stocké dans localStorage
        // Pour une sécurité renforcée, vous pouvez ajouter une vérification côté serveur

        $product = [];
        $form = $this->createForm(ProductType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            $connection->insert('product', [
                'name' => $data->getName(),
                'reference' => $data->getReference(),
                'description' => $data->getDescription(),
                'price' => $data->getPrice(),
                'stock' => $data->getStock(),
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

            $this->addFlash('success', 'Produit créé avec succès !');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(int $id, Connection $connection): Response
    {
        $product = $connection->fetchAssociative(
            'SELECT * FROM product WHERE id = ?',
            [$id]
        );

        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        // Convertir les dates string en objets DateTime pour Twig
        if (isset($product['created_at']) && $product['created_at']) {
            $product['created_at'] = new \DateTime($product['created_at']);
        }
        if (isset($product['updated_at']) && $product['updated_at']) {
            $product['updated_at'] = new \DateTime($product['updated_at']);
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, Connection $connection): Response
    {
        // Note: La vérification d'authentification se fait côté client via JavaScript
        // car nous utilisons JWT stocké dans localStorage
        
        $productData = $connection->fetchAssociative(
            'SELECT * FROM product WHERE id = ?',
            [$id]
        );

        if (!$productData) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        // Créer un objet Product temporaire pour le formulaire
        $product = new \App\Entity\Product();
        $product->setId($productData['id']);
        $product->setName($productData['name']);
        $product->setReference($productData['reference']);
        $product->setDescription($productData['description']);
        $product->setPrice($productData['price']);
        $product->setStock($productData['stock']);

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            $connection->update('product', [
                'name' => $data->getName(),
                'reference' => $data->getReference(),
                'description' => $data->getDescription(),
                'price' => $data->getPrice(),
                'stock' => $data->getStock(),
            ], [
                'id' => $id
            ]);

            $this->addFlash('success', 'Produit modifié avec succès !');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $productData,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, Connection $connection): Response
    {
        // Note: La vérification d'authentification se fait côté client via JavaScript
        // car nous utilisons JWT stocké dans localStorage
        
        if ($this->isCsrfTokenValid('delete'.$id, $request->request->get('_token'))) {
            $connection->delete('product', ['id' => $id]);
            $this->addFlash('success', 'Produit supprimé avec succès !');
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
