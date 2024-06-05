<?php

namespace App\Form;

use App\Entity\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\File;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class,[])
            ->add('typeDocument', ChoiceType::class, [
                'choices' => [
                    'Contrat' => 'Contrat',
                    'Factures' => 'Factures',
                    'Charge commune' => 'Charge commune',
                ],
                'placeholder'=> '-- Selectionner le type de document --',
            ])
            ->add('imageFile', VichImageType::class, [
                'required' => true, // Rend le champ obligatoire
                'allow_delete' => true,
                'label' => 'Document (PDF)',
                'attr' => [
                    'class' => 'filestyle',
                    'data-buttonname' => 'btn-secondary',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '1024k', // Taille maximale du fichier
                        'mimeTypes' => [
                            'application/pdf', // Type MIME PDF
                        ],
                        'mimeTypesMessage' => 'Veuillez mettre un fichier PDF valide.', // Message d'erreur
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
        ]);
    }
}
