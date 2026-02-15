<?php


// src/Service/ResetPasswordService.php
namespace App\Service;

use App\Entity\ResetPasswordToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class ResetPasswordService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private  TokenGeneratorInterface $tokenGenerator,
        private SendMailService $sendMailService,
    ) {}

    public function sendResetPasswordEmail($user): void
    {

        $token = $this->tokenGenerator->generateToken();
        $resetRpassWOrd = new ResetPasswordToken($user);
        $resetRpassWOrd->setToken($token);
        $this->em->persist($resetRpassWOrd);
        $this->em->flush();

      
        $user->setResetToken($token);
        $this->em->persist($user);
        $this->em->flush();

        // URL du frontend Svelte pour la réinitialisation
        $url = "https://mydepps.net/site/connexion/nouveau_mot_de_passe/{$token}";

        $context = compact('url', 'user');

        $this->sendMailService->send(
            //'konatefvaly@gmail.com',
            'depps@myonmci.ci',
            $user->getEmail(),
            'reinitialisation',
            'password_reset',
            $context
        );

       
    }
    public function sendResetPasswordEmailAdmin($user): void
    {

        $token = $this->tokenGenerator->generateToken();
        $resetRpassWOrd = new ResetPasswordToken($user);
        $resetRpassWOrd->setToken($token);
        $this->em->persist($resetRpassWOrd);
        $this->em->flush();

      
        $user->setResetToken($token);
        $this->em->persist($user);
        $this->em->flush();

        // URL du frontend Svelte pour la réinitialisation
        $url = "https://mydepps.net/login/nouveau_mot_de_passe/{$token}";

        $context = compact('url', 'user');

        $this->sendMailService->send(
            //'konatefvaly@gmail.com',
            'depps@myonmci.ci',
            $user->getEmail(),
            'reinitialisation',
            'password_reset',
            $context
        );

       
    }
}
