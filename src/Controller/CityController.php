<?php

namespace App\Controller;

use App\Entity\City;
use App\Form\CityType;
use App\Repository\CityRepository;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CityController extends AbstractController
{
    /**
     * @Route("/city", name="app_city_page")
     */
    public function index(Request $request, CityRepository $cityRepository,CountryRepository $countryRepository): Response
    {
        $search = $request->query->get('search');
        $countryId = $request->query->get('country');
        $status = $request->query->get('status');

        $cities = $cityRepository->filterCities($search, $countryId, $status);
        if ($search && count($cities) === 0) {
            $this->addFlash('error', 'No cities found for the given filters.');
        }

        $countries = $countryRepository->findAll();
        return $this->render('city/city.html.twig',[
            'cities' => $cities ?? [],
            'search' => $search,
            'countryId' => $countryId,
            'status' => $status,
            'countries' => $countries ?? [],
        ]);
    }

    /**
     * @Route("/city/add", name="app_add_city")
     */
    public function addCityPage(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CityType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $country = $form->get('country')->getData();
            $active = $form->get('active')->getData();
            $cityNames = $request->request->all('cities');
            $firstCity = $form->get('name')->getData();
            array_unshift($cityNames, $firstCity);
            foreach ($cityNames as $name) {

                $city = new City();
                $city->setName($name);
                $city->setCountry($country);
                $city->setActive($active);
                $entityManager->persist($city);
            }
            $entityManager->flush();
            $this->addFlash('success', 'City added successfully!');
            return $this->redirectToRoute('app_city_page');
        }
        return $this->render('city/add_city_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/city/edit/{id}", name="app_edit_city", requirements={"id"="\d+"})
     */
    public function editCity(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $city = $entityManager->getRepository(City::class)->find($id);

        if (!$city) {
            throw $this->createNotFoundException('City not found.');
        }

        $form = $this->createForm(CityType::class, $city);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'City updated successfully!');
            return $this->redirectToRoute('app_city_page');
        }

        return $this->render('city/edit_city_form.html.twig', [
            'form' => $form->createView(),
            'city' => $city,
        ]);
    }
    /**
     * @Route("/city/delete/{id}", name="app_delete_city", requirements={"id"="\d+"})
     */
    public function deleteCity(int $id, EntityManagerInterface $entityManager, CityRepository $cityRepository): Response
    {
        $city = $cityRepository->find($id);
        if (!$city) {
            throw $this->createNotFoundException('City not found');
        }
        $city->setIsDeleted(true);
        $entityManager->flush();

        $this->addFlash('success', 'City deleted successfully!');
        return $this->redirectToRoute('app_city_page');
    }
}
