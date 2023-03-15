<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Vérification du numéro de téléphone
            $user_phone = $form->get('phone')->getData();

            for ($i = 0; $i < 9; $i++) {
                if (!in_array($user_phone[$i], range(0, 9))) {
                    $this->addFlash(
                        'notice',
                        'Veuillez entrer un numéro de téléphone composé de chiffres uniquement'
                    );
                    return $this->render('registration/register.html.twig', [
                        'registrationForm' => $form->createView()
                    ]);
                    break;
                }
            }

            if ($user_phone[0] == 0) {
                if (in_array($user_phone[1], range(6, 7))) {

                    $entityManager->persist($user);
                    $entityManager->flush();
                    // do anything else you need here, like send an email
                    return $this->redirectToRoute('app_login');
                } else {
                    $this->addFlash(
                        'notice',
                        'Veuillez entrer un numéro de téléphone mobile valide, commençant par 06 ou 07'
                    );
                }
            } else {
                $this->addFlash(
                    'notice',
                    'Veuillez entrer un numéro commençant par 0'
                );
            }
        }
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            ''
        ]);
    }
}
