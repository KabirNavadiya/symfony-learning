<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\City;
use App\Entity\Country;
use App\Repository\CityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    private $cityRepository;

    public function __construct(CityRepository $cityRepository)
    {
        $this->cityRepository = $cityRepository;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'placeholder' => 'Select Country',
                'choice_label' => 'name',
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'placeholder' => 'Select Country',
                'choices' => [],
                'choice_label' => 'name',
            ])
            ->add('streetName', TextareaType::class, [
                'required' => true,
            ])
            ->add('postcode', TextType::class, [
                'required' => true,
                'attr' => ['pattern' => '\d*'],
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            $countryId = $data['country'] ?? null;

            if ($countryId) {
                $cities = $this->cityRepository->findBy([
                    'country' => $countryId,
                    'isDeleted' => false,
                ]);

                $form->add('city', EntityType::class, [
                    'class' => City::class,
                    'choices' => $cities,
                    'placeholder' => 'Select City',
                    'choice_label' => 'name',
                ]);
            }
        });
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $address = $event->getData();

            if ($address && $address->getCountry()) {
                $countryId = $address->getCountry()->getId();
                $cities = $this->cityRepository->findBy([
                    'country' => $countryId,
                    'isDeleted' => false,
                ]);

                $form->add('city', EntityType::class, [
                    'class' => City::class,
                    'choices' => $cities,
                    'placeholder' => 'Select City',
                    'choice_label' => 'name',
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
