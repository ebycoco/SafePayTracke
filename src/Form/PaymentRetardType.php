<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Payment;
use App\Entity\PaymentVerification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentRetardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montantAPayer',NumberType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le montant à payer est obligatoire.']),
                    new Assert\Positive(['message' => 'Le montant doit être positif.']),
                ],
                'label' => 'Montant à payer'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}
