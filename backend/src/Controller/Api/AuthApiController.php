<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth', name: 'api_auth_')]
class AuthApiController extends AbstractController
{
    // -------------------------------------------------------
    // GET /api/auth/me  →  usuario autenticado actual
    // -------------------------------------------------------
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'No autenticado'], 401);
        }

        /** @var User $user */
        return $this->json([
            'id'          => $user->getId(),
            'email'       => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
            'avatar'      => $user->getAvatar(),
            'roles'       => $user->getRoles(),
        ]);
    }

    // -------------------------------------------------------
    // POST /api/auth/register  →  registro de nuevo usuario
    // Body JSON: { "email": "...", "password": "..." }
    // -------------------------------------------------------
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data        = json_decode($request->getContent(), true) ?? [];
        $email       = trim((string) ($data['email'] ?? ''));
        $password    = (string) ($data['password'] ?? '');
        $displayName = trim((string) ($data['displayName'] ?? ''));

        if ($email === '' || $password === '') {
            return $this->json(['error' => 'email y password son obligatorios'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'El email no es válido'], 400);
        }

        if (strlen($password) < 6) {
            return $this->json(['error' => 'La contraseña debe tener al menos 6 caracteres'], 400);
        }

        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return $this->json(['error' => 'Ya existe una cuenta con ese email'], 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setIsVerified(true);

        // Usar displayName del cliente o generar uno único a partir del email
        $base = $displayName !== ''
            ? preg_replace('/[^a-zA-Z0-9_]/', '', $displayName) ?: strstr($email, '@', true)
            : strstr($email, '@', true);
        $base = preg_replace('/[^a-zA-Z0-9_]/', '', $base) ?: 'usuario';
        $candidate = $base;
        $suffix = 1;
        while ($em->getRepository(User::class)->findOneBy(['displayName' => $candidate])) {
            $candidate = $base . $suffix;
            $suffix++;
        }
        $user->setDisplayName($candidate);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
        ], 201);
    }

    // -------------------------------------------------------
    // POST /api/auth/logout  →  cerrar sesión
    // -------------------------------------------------------
    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $request->getSession()->invalidate();

        return $this->json(['status' => 'logged_out']);
    }
}
