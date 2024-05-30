<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserModiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            // ->add('roles', ChoiceType::class, [
            //     'choices' => [
            //         'Admin' => 'ROLE_ADMIN',
            //         'Utilisateur' => 'ROLE_USER',
            //         'Locataire' => 'ROLE_LOCATEUR',
            //         'Gardienage' => 'ROLE_GARDIEN',
            //         // Ajoutez d'autres rôles selon vos besoins
            //     ],
            //     'multiple' => true, // Si vous voulez sélectionner plusieurs rôles
            //     'expanded' => true, // Si vous voulez afficher les options comme des cases à cocher
            // ])
            //->add('password')
            ->add('numero')
            ->add('nomDeSociete')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
