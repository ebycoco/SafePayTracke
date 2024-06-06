<?php

namespace App\Form;

use App\Entity\Payment;
use App\Entity\PaymentVerification;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentVerificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montantPrevu',NumberType::class,[
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le montant prévu est obligatoire.']),
                    new Assert\Positive(['message' => 'Le montant doit être positif.']),
                ],
                'label' => 'Montant prévu'
            ])
            ->add('montantRecu',NumberType::class,[
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le montant reçu est obligatoire.']),
                    new Assert\Positive(['message' => 'Le montant doit être positif.']),
                ],
                'label' => 'Montant reçu'
            ])
            ->add('typePaiement', ChoiceType::class, [
                'choices' => [
                    'Paiement normal' => 'Normal',
                    'Paiement retard' => 'Retard',
                    'Paiement anticiper' => 'Anticiper',
                ],
                'placeholder'=> '-- Selectionner le type de paiement --',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le type de paiement est obligatoire.']),
                ],
                'label' => 'Type de paiement'
            ]) 
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PaymentVerification::class,
        ]);
    }
}
