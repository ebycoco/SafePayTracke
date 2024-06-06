<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email',EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'L\'email ne peut pas être vide.',
                    ]),
                    new Assert\Email([
                        'message' => 'Veuillez entrer un email valide.',
                    ]),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Utilisateur' => 'ROLE_USER',
                    'Locataire' => 'ROLE_LOCATEUR',
                    'Gardienage' => 'ROLE_GARDIEN',
                    // Ajoutez d'autres rôles selon vos besoins
                ],
                'multiple' => true, // Si vous voulez sélectionner plusieurs rôles
                'expanded' => true, // Si vous voulez afficher les options comme des cases à cocher
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Vous devez choisir au moins un rôle.',
                    ]),
                ],
            ])
            ->add('numero',NumberType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le numéro ne peut pas être vide.',
                    ]),
                    new Assert\Positive([
                        'message' => 'Le numéro doit être positif.',
                    ]),
                ],
            ])
            ->add('nomDeSociete',TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom de la société ne peut pas être vide.',
                    ]),
                    new Assert\Length([
                        'min' => 5,
                        'minMessage' => 'Le nom de la société doit dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
