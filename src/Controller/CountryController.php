<?php

namespace App\Controller;

use App\Entity\Country;
use App\Form\CountryType;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CountryController extends AbstractController
{
    /**
     * @Route("/", name="app_homepage")
     */
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    /**
     * @Route("/country", name="app_country_page")
     */
    public function country(Request $request, CountryRepository $countryRepository)
    {

        $search = $request->query->get('search');

        if ($search) {
            $countries = $countryRepository->findByNameLike($search);

            if (!$countries) {
                $this->addFlash('error', 'No countries found for the search term.');
            }
        } else {
            $countries = $countryRepository->findAll();
        }

        return $this->render('country/country.html.twig', [
            'countries' => $countries ?? [],
            'search' => $search,
        ]);
    }

    /**
     * @Route("/country/add", name="app_add_country")
     */
    public function addCountryPage(Request $request, EntityManagerInterface $entityManager):Response
    {
        $country = new Country();
        $form = $this->createForm(CountryType::class, $country);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $processedCountry = $this->preprocessingData($country->getName());
            $country->setName($processedCountry);
            $entityManager->persist($country);
            $entityManager->flush();

            $this->addFlash('success', 'Country added successfully!');
            return $this->redirectToRoute('app_country_page');
        }
        return $this->render('country/add_country_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/country/edit/{id}", name="app_edit_country", requirements={"id"="\d+"})
     */
    public function editCountryPage(int $id, Request $request, EntityManagerInterface $entityManager, CountryRepository $countryRepository): Response{
        $country = $countryRepository->find($id);
        if(!$country) {
            throw $this->createNotFoundException('Country not found');
        }
        $form = $this->createForm(CountryType::class, $country);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $processedCountry = $this->preprocessingData($country->getName());
            $country->setName($processedCountry);
            $entityManager->persist($country);
            $entityManager->flush();

            $this->addFlash('success', 'Country updated successfully!');
            return $this->redirectToRoute('app_country_page');
        }
        return $this->render('country/edit_country_form.html.twig', [
            'form' => $form->createView(),
            'country' => $country,
        ]);
    }

    /**
     * @Route("/country/delete/{id}", name="app_delete_country", requirements={"id"="\d+"})
     */

    public function deleteCountry(int $id, EntityManagerInterface $entityManager, CountryRepository $countryRepository): Response
    {
        $country = $countryRepository->find($id);
        if (!$country) {
            throw $this->createNotFoundException('Country not found');
        }
        $entityManager->remove($country);
        $entityManager->flush();

        $this->addFlash('success', 'Country deleted successfully!');
        return $this->redirectToRoute('app_country_page');
    }

    public function preprocessingData(string $input): string
    {
        return strtolower($input);
    }
}
