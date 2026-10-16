<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use App\Entity\Document;
use App\Enum\DocumentNature;

class DocumentMetadataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nature', ChoiceType::class, [
                'label' => 'Nature du document',
                'choices' => DocumentNature::cases(),
                'choice_label' => fn (DocumentNature $n) => $n->label(),
                'choice_value' => fn (?DocumentNature $n) => $n?->value,
                'placeholder' => '— Non catégorisé —',
                'required' => false,
            ])
            ->add('periodYear', IntegerType::class, [
                'label' => 'Année',
                'required' => false,
                'attr' => [
                    'min' => 2000,
                    'max' => 2100,
                    'placeholder' => '2024',
                ],
            ])
            ->add('periodMonth', ChoiceType::class, [
                'label' => 'Mois',
                'required' => false,
                'placeholder' => '— Tous —',
                'choices' => [
                    'Janvier' => 1, 'Février' => 2, 'Mars' => 3,
                    'Avril' => 4, 'Mai' => 5, 'Juin' => 6,
                    'Juillet' => 7, 'Août' => 8, 'Septembre' => 9,
                    'Octobre' => 10, 'Novembre' => 11, 'Décembre' => 12,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
        ]);
    }
}
