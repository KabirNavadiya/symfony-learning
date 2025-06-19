<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Country;
use App\Repository\CityRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CityType extends AbstractType
{

    private $cityRepository;
    public function __construct(CityRepository $cityRepository)
    {
        $this->cityRepository = $cityRepository;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'City Name',
            ])
            ->add('country', EntityType::class, [
                'class' => Country::class,
                'choice_label' => 'name',
                'placeholder' => 'Select Country',
                'required' => true,
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'data' => true,
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            // Validate that the city name is not empty
            if (empty($data->getName())) {
                $form->get('name')->addError(new FormError('City name cannot be empty.'));
            }
            if (null === $data->getCountry()) {
                $form->get('country')->addError(new FormError('Please select a country.'));
            }

            if ($data->getName() && $data->getCountry()) {
                $existingCity = $this->cityRepository->findOneBy([
                    'name' => $data->getName(),
                    'country' => $data->getCountry(),
                ]);

                // Skip current when checking for existing city
                if ($existingCity && $existingCity->getId() !== $data->getId()) {
                    $form->get('name')->addError(new FormError('This city already exists in the selected country.'));
                }
            }
        });
    }



    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => City::class,
        ]);
    }


}
