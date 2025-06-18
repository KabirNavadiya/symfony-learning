<?php

namespace App\Controller;

use App\Form\AddressType;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AddressController extends AbstractController
{
    /**
     * @Route("/address", name="app_address_page")
     */
    public function index(AddressRepository $addressRepository): Response
    {
        $addresses = $addressRepository->findAll();
        return $this->render('address/address.html.twig',[
            'addresses' => $addresses,
        ]);
    }

    /**
     * @Route("/address/add", name="app_add_address")
     */
    public function addAddress(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AddressType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $address = $form->getData();
            $entityManager->persist($address);
            $entityManager->flush();

            $this->addFlash('success', 'Address added successfully!');
            return $this->redirectToRoute('app_address_page');
        }
        return $this->render('address/add_address_form.html.twig',[
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/address/edit/{id}", name="app_edit_address", requirements={"id"="\d+"})
     */
    public function editAddress(int $id, Request $request, EntityManagerInterface $entityManager, AddressRepository $addressRepository): Response
    {
        $address = $addressRepository->find($id);
        if (!$address) {
            throw $this->createNotFoundException('Address not found');
        }

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Address updated successfully!');
            return $this->redirectToRoute('app_address_page');
        }

        return $this->render('address/edit_address_form.html.twig', [
            'form' => $form->createView(),
            'address' => $address,
        ]);
    }

    /**
     * @Route("/address/delete/{id}", name="app_delete_address", requirements={"id"="\d+"})
     */
    public function deleteAddress(int $id, EntityManagerInterface $entityManager, AddressRepository $addressRepository): Response
    {
        $address = $addressRepository->find($id);
        if (!$address) {
            throw $this->createNotFoundException('Address not found');
        }

        $entityManager->remove($address);
        $entityManager->flush();
        $this->addFlash('success', 'Address deleted successfully!');

        return $this->redirectToRoute('app_address_page');
    }
}
