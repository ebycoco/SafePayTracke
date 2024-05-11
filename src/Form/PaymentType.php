<?php

namespace App\Form;

use App\Entity\Payment;
use App\Entity\PaymentVerification;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montantAPayer')
            ->add('imageFile', VichImageType::class, [
                'required' => false,
                'allow_delete' => true,
                'label' => 'Image du reçu (JPG or PNG file)',
                'attr' => [
                    'class' => 'filestyle',
                    'data-buttonname' => 'btn-secondary',
                ]
            ])
            //->add('montantRestant')
           // ->add('status')
            //->add('isVisibilite')
            ->add('datePaiement', null, [
                'widget' => 'single_text',
            ])
            // ->add('users', EntityType::class, [
            //     'class' => User::class,
            //     'choice_label' => 'id',
            // ])
            // ->add('PaymentVerification', EntityType::class, [
            //     'class' => PaymentVerification::class,
            //     'choice_label' => 'id',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}
