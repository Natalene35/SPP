<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\DateTime;
// Pour récupérer les données d'un utilisateur connecté et réinjecter ces données dans le formulaire de réservation
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use App\Entity\User;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ReservationRepository $reservationRepository, MailerInterface $mailer, TexterInterface $texter, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
        // $phone = $form->getData()->getPhone();
        // $ToPhoneApb = "+33695353500";
        $ToMail = $form->getData()->getEmail();
        $ToMailApb = "poupouille20@gmail.com";
        $firstname = $form->getData()->getFirstname();
        $lastname = $form->getData()->getLastname();
        $date = $form->getData()->getDate();
        $type = $form->getData()->getType();
        date_default_timezone_set('Europe/Zurich');
        $now = date('y-m-d G:i:s');

        if ($form->isSubmitted() && $form->isValid()) {
              // Vérification du numéro de téléphone
              $user_phone = $form->get('phone')->getData();

              for ($i = 0; $i < 9; $i++) {
                  if (!in_array($user_phone[$i], range(0, 9))) {
                      $this->addFlash(
                          'notice',
                          'Veuillez entrer un numéro de téléphone composé de chiffres uniquement'
                      );
                      return $this->renderForm('reservation/new.html.twig', [
                        'reservation' => $reservation,
                        'form' => $form,
                        'user' => $user,
                    ]);
                      break;
                  }
              }
  
              if ($user_phone[0] == 0) {
                  if (in_array($user_phone[1], range(6, 7))) {
                    $reservationRepository->save($reservation, true);
                
                      // do anything else you need here, like send an email
                      return $this->redirectToRoute('app_accueil');
                  } else {
                      $this->addFlash(
                          'notice',
                          'Veuillez entrer un numéro de téléphone mobile valide, commençant par 06 ou 07'
                      );
                      return $this->renderForm('reservation/new.html.twig', [
                        'reservation' => $reservation,
                        'form' => $form,
                        'user' => $user,
                    ]);
                  }
              } else {
                  $this->addFlash(
                      'notice',
                      'Veuillez entrer un numéro commençant par 0'
                  );
                  return $this->renderForm('reservation/new.html.twig', [
                    'reservation' => $reservation,
                    'form' => $form,
                    'user' => $user,
                ]);
              }

            //   $toPhone = "+33" . $phone;

              $date = $form->getData()->getDate()->format('y-m-d G:i:s');
            if($date < $now){
                $this->addFlash(
                    'noticeDate',
                    'Veuillez sélectionner une date postérieure à la date actuelle'
                );
                return $this->renderForm('reservation/new.html.twig', [
                    'reservation' => $reservation,
                    'form' => $form,
                    'user' => $user,
                ]);
            }else{
                $entityManager->persist($reservation);
                $entityManager->flush();
            }

              // envoie de mail au client
              $email = (new TemplatedEmail())
                  ->from($ToMailApb)
                  ->to($ToMail)
                  ->subject('Confirmation de votre Réservation')
                  ->htmlTemplate('mail/sendMail.html.twig')
                  ->context([
                      'e-mail' => $ToMail,
                      'firstname' => $firstname,
                      'lastname' => $lastname,
                      'date_res' => $date,
                      'type' => $type
                  ]);
                  $mailer->send($email);  
              // envoie de mail a apb
              $email2 = (new TemplatedEmail())
                  ->from($ToMailApb)
                  ->to($ToMailApb)
                  ->subject('Confirmation de Réservation')
                  ->htmlTemplate('mail/sendMailSPP.html.twig')
                  ->context([
                      'e-mail' => $ToMail,
                      'firstname' => $firstname,
                      'lastname' => $lastname,
                      'date_res' => $date,
                      'type' => $type
                  ]);
              $mailer->send($email2);

              // envoie d'un sms au client
              /* $sms = new SmsMessage(
                  // the phone number to send the SMS message to
                  $toPhone,
                  // the message
                  'Votre réservation pour un remplacement de ' + $type + 'le' + $date + 'avec le garage SPP a bien été pris en compte.'
              );
              // envoie d'un sms a apb
              $sms = new SmsMessage(
                  // the phone number to send the SMS message to
                  $ToPhoneApb,
                  // the message
                  'Une réservation pour un remplacement de ' + 'type' + 'a été confirmée. Je vous invite à regarder votre planning sur le site www.garagespp.fr'
              );
              $texter->send($sms);  
 */
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, ReservationRepository $reservationRepository): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservationRepository->save($reservation, true);

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, ReservationRepository $reservationRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $reservationRepository->remove($reservation, true);
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    // Pour récupérer les données d'un utilisateur connecté et réinjecter ces données dans le formulaire de réservation
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function newReservation(Request $request, Reservation $reservation)
    {
        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            if ($user instanceof User) {
                $form = $this->createForm(ReservationType::class, $reservation);

                // Set the value of the email field to the email of the logged-in user
                $lastnameCollect = $form->get('lastname')->setData($user->getLastname());
                $form->get('firstname')->setData($user->getFirstname());
                $form->get('phone')->setData($user->getPhone());
                $form->get('email')->setData($user->getEmail());
                

                // Handle the form submission
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    // Save the reservation
                }

                return $this->render('reservation/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }
    }
}
