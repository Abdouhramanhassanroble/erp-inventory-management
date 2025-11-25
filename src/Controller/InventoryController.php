<?php

namespace App\Controller;

use App\Form\InventoryType;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/inventory')]
class InventoryController extends AbstractController
{
    #[Route('/', name: 'app_inventory', methods: ['GET'])]
    public function index(Connection $connection): Response
    {
        $inventories = $connection->fetchAllAssociative(
            'SELECT i.*, p.name as product_name, p.reference as product_reference, u.email as user_email
             FROM inventory i
             LEFT JOIN product p ON i.product_id = p.id
             LEFT JOIN "user" u ON i.utilisateur_id = u.id
             ORDER BY i.id DESC'
        );

        // Convertir les dates string en objets DateTime pour Twig
        foreach ($inventories as &$inventory) {
            if (isset($inventory['created_at']) && $inventory['created_at']) {
                $inventory['created_at'] = new \DateTime($inventory['created_at']);
            }
        }

        return $this->render('inventory/index.html.twig', [
            'inventories' => $inventories,
        ]);
    }

    #[Route('/new', name: 'app_inventory_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Connection $connection): Response
    {
        // Récupérer la liste des produits et utilisateurs pour le formulaire
        $products = $connection->fetchAllAssociative('SELECT id, name, reference FROM product ORDER BY name');
        $users = $connection->fetchAllAssociative('SELECT id, email FROM "user" ORDER BY email');

        $form = $this->createForm(InventoryType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            $connection->insert('inventory', [
                'product_id' => $data->getProduct()->getId(),
                'quantity' => $data->getQuantity(),
                'movement_type' => $data->getMovementType(),
                'utilisateur_id' => $data->getUtilisateur() ? $data->getUtilisateur()->getId() : null,
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

            // Mettre à jour le stock du produit
            if ($data->getMovementType() === 'ENTRY') {
                $connection->executeStatement(
                    'UPDATE product SET stock = stock + ? WHERE id = ?',
                    [$data->getQuantity(), $data->getProduct()->getId()]
                );
            } elseif ($data->getMovementType() === 'EXIT') {
                $connection->executeStatement(
                    'UPDATE product SET stock = GREATEST(0, stock - ?) WHERE id = ?',
                    [$data->getQuantity(), $data->getProduct()->getId()]
                );
            }

            $this->addFlash('success', 'Mouvement d\'inventaire créé avec succès !');
            return $this->redirectToRoute('app_inventory', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('inventory/new.html.twig', [
            'form' => $form,
            'products' => $products,
            'users' => $users,
        ]);
    }

    #[Route('/{id}', name: 'app_inventory_show', methods: ['GET'])]
    public function show(int $id, Connection $connection): Response
    {
        $inventory = $connection->fetchAssociative(
            'SELECT i.*, p.name as product_name, p.reference as product_reference, u.email as user_email
             FROM inventory i
             LEFT JOIN product p ON i.product_id = p.id
             LEFT JOIN "user" u ON i.utilisateur_id = u.id
             WHERE i.id = ?',
            [$id]
        );

        if (!$inventory) {
            throw $this->createNotFoundException('Mouvement d\'inventaire non trouvé');
        }

        // Convertir les dates string en objets DateTime pour Twig
        if (isset($inventory['created_at']) && $inventory['created_at']) {
            $inventory['created_at'] = new \DateTime($inventory['created_at']);
        }

        return $this->render('inventory/show.html.twig', [
            'inventory' => $inventory,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_inventory_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, Connection $connection): Response
    {
        $inventoryData = $connection->fetchAssociative(
            'SELECT * FROM inventory WHERE id = ?',
            [$id]
        );

        if (!$inventoryData) {
            throw $this->createNotFoundException('Mouvement d\'inventaire non trouvé');
        }

        // Récupérer la liste des produits et utilisateurs
        $products = $connection->fetchAllAssociative('SELECT id, name, reference FROM product ORDER BY name');
        $users = $connection->fetchAllAssociative('SELECT id, email FROM "user" ORDER BY email');

        // Créer un objet Inventory temporaire pour le formulaire
        $inventory = new \App\Entity\Inventory();
        $inventory->setId($inventoryData['id']);
        $inventory->setQuantity($inventoryData['quantity']);
        $inventory->setMovementType($inventoryData['movement_type']);
        
        // Récupérer les objets Product et User
        $product = $connection->fetchAssociative('SELECT * FROM product WHERE id = ?', [$inventoryData['product_id']]);
        if ($product) {
            $productEntity = new \App\Entity\Product();
            $productEntity->setId($product['id']);
            $productEntity->setName($product['name']);
            $inventory->setProduct($productEntity);
        }

        if ($inventoryData['utilisateur_id']) {
            $user = $connection->fetchAssociative('SELECT * FROM "user" WHERE id = ?', [$inventoryData['utilisateur_id']]);
            if ($user) {
                $userEntity = new \App\Entity\User();
                $userEntity->setId($user['id']);
                $userEntity->setEmail($user['email']);
                $inventory->setUtilisateur($userEntity);
            }
        }

        $form = $this->createForm(InventoryType::class, $inventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            $connection->update('inventory', [
                'product_id' => $data->getProduct()->getId(),
                'quantity' => $data->getQuantity(),
                'movement_type' => $data->getMovementType(),
                'utilisateur_id' => $data->getUtilisateur() ? $data->getUtilisateur()->getId() : null,
            ], [
                'id' => $id
            ]);

            $this->addFlash('success', 'Mouvement d\'inventaire modifié avec succès !');
            return $this->redirectToRoute('app_inventory', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('inventory/edit.html.twig', [
            'inventory' => $inventoryData,
            'form' => $form,
            'products' => $products,
            'users' => $users,
        ]);
    }

    #[Route('/{id}', name: 'app_inventory_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, Connection $connection): Response
    {
        if ($this->isCsrfTokenValid('delete'.$id, $request->request->get('_token'))) {
            $connection->delete('inventory', ['id' => $id]);
            $this->addFlash('success', 'Mouvement d\'inventaire supprimé avec succès !');
        }

        return $this->redirectToRoute('app_inventory', [], Response::HTTP_SEE_OTHER);
    }
}
