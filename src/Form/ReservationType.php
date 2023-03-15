<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastname', TextType::class, ['label' => 'Nom'])
            ->add('firstname', TextType::class, ['label' => 'Prénom'])
            ->add('phone', TelType::class, [
                'label' => 'Numéro de téléphone mobile',
                'constraints' => [
                    new Length([
                        'min' => 10,
                        'max' => 10,
                    ]),
                ],
            ])
            ->add('email', EmailType::class, ['label' => 'E-mail'])
            ->add('date', DateTimeType::class,['data'   => new \DateTime(),
            'attr'   => ['min' => ( new \DateTime() )->format('d-m-Y H:i:s')]])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de réparation',
                'choices'  => [
                    'Pare-Brise' => 'Pare-Brise',
                    'Pneus' => 'Pneus',
                    'Disques et plaquettes' => 'Disques et plaquettes',
                ],
                'expanded' => true,
                'multiple' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
