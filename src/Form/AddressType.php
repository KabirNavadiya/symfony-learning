<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\City;
use App\Entity\Country;
use App\Repository\AddressRepository;
use App\Repository\CityRepository;
use App\Repository\CountryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    private $cityRepository;
    private $addressRepository;
    private $countryRepository;

    public function __construct(CityRepository $cityRepository,AddressRepository $addressRepository,CountryRepository $countryRepository)
    {
        $this->addressRepository = $addressRepository;
        $this->cityRepository = $cityRepository;
        $this->countryRepository = $countryRepository;
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
                'attr' => ['pattern' => '\d*', 'title'=>'Must be numeric' ],
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

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            if (empty($data->getStreetName())) {
                $form->get('streetName')->addError(new FormError('Street name cannot be empty.'));
            }
            if (empty($data->getPostcode())) {
                $form->get('postcode')->addError(new FormError('Postcode cannot be empty.'));
            }
            if (!$data->getCity()) {
                $form->get('city')->addError(new FormError('City must be selected.'));
            }
            if (!$data->getCountry()) {
                $form->get('country')->addError(new FormError('Country must be selected.'));
            }

            $existingAddress = $this->addressRepository->findOneBy([
                'StreetName' => $data->getStreetName(),
                'postcode' => $data->getPostcode(),
                'city' => $data->getCity(),
                'country' => $data->getCountry(),
            ]);
            if($existingAddress) {
                $form->get('streetName')->addError(new FormError('Address already exists.'));
            }

            if($data->getPostcode() && !preg_match('/^\d+$/', $data->getPostcode())) {
                $form->get('postcode')->addError(new FormError('Postcode must be numeric.'));
            }
            if($data->getPostcode() && strlen($data->getPostcode()) != 6) {
                $form->get('postcode')->addError(new FormError('Postcode must be 6 digits long.'));
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
