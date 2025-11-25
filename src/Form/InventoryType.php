<?php

namespace App\Form;

use App\Entity\Inventory;
use App\Entity\Product;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InventoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => function(Product $product) {
                    return $product->getName() . ' (' . $product->getReference() . ')';
                },
                'label' => 'Produit',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez un produit',
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => ['class' => 'form-control', 'min' => 1],
            ])
            ->add('movement_type', ChoiceType::class, [
                'label' => 'Type de mouvement',
                'choices' => [
                    'Entrée' => 'ENTRY',
                    'Sortie' => 'EXIT',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('utilisateur', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Utilisateur',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez un utilisateur',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inventory::class,
        ]);
    }
}
