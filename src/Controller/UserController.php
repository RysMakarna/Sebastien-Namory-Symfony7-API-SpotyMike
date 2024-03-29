<?php

namespace App\Controller;


use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Length;

class UserController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
    }

    #[Route('/add/user', name: 'app_add_user', methods: ['POST'])]
    public function AddUser(Request $request): JsonResponse
    {
        $id_user = $this->repository->count();
        $email = $request->get('email');
        $existingUser = $this->repository->findOneBy(['email' => $email]);

        if ($existingUser) {
            return $this->json([
                "message" => 'L utilisateur existe',
            ], 409);
        }

        //add regex tel 
        if (strlen($request->get('tel')) <= 14) {
            //dd("acccepter");
            //[0][1-9][0-9]{8}$
            if (preg_match('/[(0|\\+33|0033)][1-9][0-9]{8}$/', $request->get('tel'))) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 60) {
                    if (strlen($request->get('name')) <= 10) {
                        $user = new User();
                        $user->setEmail($request->get('email'));
                        $user->setFirstname($request->get('firstname'));
                        $user->setLastname($request->get('lastname'));
                        //$encrypte = password_hash($request->get('encrypte'), PASSWORD_DEFAULT);        
                        $user->setEncrypte($request->get('encrypte'));
                        $user->setTel($request->get('tel'));
                        $user->setIdUser($id_user + 1);
                        $user->setCreateAt(new \DateTimeImmutable());
                        $user->setUpdateAt(new \DateTime());

                        $this->entityManager->persist($user);
                        $this->entityManager->flush();
                        return $this->json([
                            'user' => $user->UserSerializer(),
                            'message' => 'Ajouter  avec  succès',
                        ], 200);
                    } else {
                        return $this->json([
                            "message" => "votre email nom est trop long."
                        ], 400); 
                    }

                } else {
                    return $this->json([
                        "message" => "votre adresse e-mail est invalide."
                    ], 400); 
                }
            } else {
                return $this->json([
                    'error' => 'votre numero est dans un format invalide.',
                ], 400);
            }
        } else {
            return $this->json([
                'error' => 'La taille du numéro est invalide.',
            ], 400);
        }
    }

    #[Route('/read/user', name: 'app_read_user')]
    public function readUser(): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findAll();
        if(count($user) > 0) {
            $usersArray = array_map(function ($user) {
                return $user->UserSerializer(); // Ensure you have a toArray() method in your User entity
            }, $user);
    
            return $this->json([
                $usersArray,
            ],200);
        }
        return $this->json([
            'message'=> 'aucun utilisateur pour le moment',
        ],204);
        
    }

    #[Route('/update/user/{id}', name: 'app_update_user', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json([
                'message' => 'Aucune compte avec ce id à modifier !',
            ],444);
        }
        $user->setName($request->get('name'));
        $user->setEmail($request->get('email'));
        $user->setTel($request->get('tel'));
        $this->entityManager->flush();
        return $this->json([
            'message' => 'modifier avec succès',
        ],200);
    }
    #[Route('/delete/user/{id}', name: 'app_delete_user', methods: ['delete'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json([
                'message' => 'Aucune compte avec ce id à modifier !',
            ],444);
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Utisateur supprimer avec succès!',
        ],200);
    }

}
