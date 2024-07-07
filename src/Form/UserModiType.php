<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserModiType extends AbstractType
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
            ->add('numero',TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le numéro ne peut pas être vide.',
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Le numéro doit être supérieur ou égal à zéro.',
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
