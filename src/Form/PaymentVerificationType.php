<?php

namespace App\Form;

use App\Entity\Payment;
use App\Entity\PaymentVerification;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentVerificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montantPrevu')
            ->add('montantRecu')
            ->add('typePaiement', ChoiceType::class, [
                'choices' => [
                    'Paiement normal' => 'Normal',
                    'Paiement retard' => 'Retard',
                    'Paiement anticiper' => 'Anticiper',
                ],
                'placeholder'=> '-- Selectionner le type de paiement --',
            ])
            // ->add('Payment', EntityType::class, [
            //     'class' => Payment::class,
            //     'choice_label' => 'id',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PaymentVerification::class,
        ]);
    }
}
