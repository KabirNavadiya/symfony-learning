<?php

namespace App\Form;

use App\Entity\Country;
use App\Repository\CountryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


class CountryType extends AbstractType
{
    private $countryRepository;
    public function __construct(CountryRepository $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {


        $builder
            ->add('name', TextType::class, [
                'label' => 'Country Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter country name',
                    'required' => true,
                ],

            ]);
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            // Validate that the country name is not empty
            if (empty($data->getName())) {
                $form->get('name')->addError(new FormError('Country name cannot be empty.'));
            }

            // Check if the country name already exists
            $existingCountry = $this->countryRepository->findOneBy(['name' => $data->getName()]);
            if ($existingCountry) {
                $form->get('name')->addError(new FormError('This country already exists.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Country::class,
        ]);
    }
}
