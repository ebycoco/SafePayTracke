<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Payment;
use App\Entity\PaymentVerification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentType extends AbstractType
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
            ->add('imageFile', VichImageType::class, [
                'required' => false,
                'allow_delete' => true,
                'label' => 'Image du reçu (JPG)',
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '1024k',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG ou PNG).'
                    ])
                ],
                'attr' => [
                    'class' => 'filestyle',
                    'data-buttonname' => 'btn-secondary',
                ]
            ]) 
            ->add('datePaiement', null, [
                'widget' => 'single_text',
                'label' => 'Date de la période de paiement'
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
            'data_class' => Payment::class,
        ]);
    }
}
