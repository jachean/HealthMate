<?php

namespace App\Controller;

use App\DTO\ReviewCreateDTO;
use App\DTO\ReviewReadDTO;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
final class ReviewController extends AbstractController
{
    #[Route('/appointments/{id}/review', methods: ['POST'])]
    public function create(
        int $id,
        Request $request,
        AppointmentRepository $appointmentRepository,
        ReviewRepository $reviewRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        Security $security
    ): JsonResponse {
        /** @var User|null $user */
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['error' => ['code' => 'AUTH_REQUIRED', 'message' => 'Authentication required.']], 401);
        }

        $appointment = $appointmentRepository->find($id);

        if (!$appointment) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Appointment not found.']], 404);
        }

        if ($appointment->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied.']], 403);
        }

        if ($appointment->getStatus() !== 'booked') {
            return $this->json([
                'error' => [
                    'code' => 'INVALID_STATUS',
                    'message' => 'Only booked appointments can be reviewed.',
                ],
            ], 422);
        }

        if ($appointment->getTimeSlot()->getStartAt() >= new \DateTimeImmutable()) {
            return $this->json([
                'error' => [
                    'code' => 'APPOINTMENT_NOT_PAST',
                    'message' => 'Appointment has not taken place yet.',
                ],
            ], 422);
        }

        if ($reviewRepository->findByAppointment($id) !== null) {
            return $this->json([
                'error' => [
                    'code' => 'REVIEW_EXISTS',
                    'message' => 'A review already exists for this appointment.',
                ],
            ], 409);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new ReviewCreateDTO();
        $dto->rating = isset($data['rating']) ? (float) $data['rating'] : 0;
        $dto->comment = $data['comment'] ?? null;

        $errors = $validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json(['error' => ['code' => 'INVALID_REQUEST', 'message' => 'Invalid rating value.']], 400);
        }

        $doctor = $appointment->getTimeSlot()->getDoctor();
        $authorName = $user->getFirstName() . ' ' . mb_substr($user->getLastName(), 0, 1) . '.';

        $review = new Review();
        $review->setRating($dto->rating);
        $review->setComment($dto->comment);
        $review->setAuthor($user);
        $review->setDoctor($doctor);
        $review->setAppointment($appointment);
        $review->setAuthorName($authorName);

        $em->persist($review);
        $em->flush();

        return $this->json($this->toReadDTO($review), 201);
    }

    #[Route('/appointments/{id}/review', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        AppointmentRepository $appointmentRepository,
        ReviewRepository $reviewRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        Security $security
    ): JsonResponse {
        /** @var User|null $user */
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['error' => ['code' => 'AUTH_REQUIRED', 'message' => 'Authentication required.']], 401);
        }

        $appointment = $appointmentRepository->find($id);

        if (!$appointment) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Appointment not found.']], 404);
        }

        if ($appointment->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied.']], 403);
        }

        $review = $reviewRepository->findByAppointment($id);

        if (!$review) {
            return $this->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'No review found for this appointment.',
                ],
            ], 404);
        }

        if ($review->getAuthor()->getId() !== $user->getId()) {
            return $this->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied.']], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new ReviewCreateDTO();
        $dto->rating = isset($data['rating']) ? (float) $data['rating'] : $review->getRating();
        $dto->comment = array_key_exists('comment', $data) ? $data['comment'] : $review->getComment();

        $errors = $validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json(['error' => ['code' => 'INVALID_REQUEST', 'message' => 'Invalid rating value.']], 400);
        }

        $review->setRating($dto->rating);
        $review->setComment($dto->comment);

        $em->flush();

        return $this->json($this->toReadDTO($review));
    }

    #[Route('/appointments/{id}/review', methods: ['DELETE'])]
    public function delete(
        int $id,
        AppointmentRepository $appointmentRepository,
        ReviewRepository $reviewRepository,
        EntityManagerInterface $em,
        Security $security
    ): JsonResponse {
        /** @var User|null $user */
        $user = $security->getUser();

        if (!$user) {
            return $this->json(['error' => ['code' => 'AUTH_REQUIRED', 'message' => 'Authentication required.']], 401);
        }

        $appointment = $appointmentRepository->find($id);

        if (!$appointment) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Appointment not found.']], 404);
        }

        if ($appointment->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied.']], 403);
        }

        $review = $reviewRepository->findByAppointment($id);

        if (!$review) {
            return $this->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'No review found for this appointment.',
                ],
            ], 404);
        }

        if ($review->getAuthor()->getId() !== $user->getId()) {
            return $this->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied.']], 403);
        }

        $em->remove($review);
        $em->flush();

        return $this->json(null, 204);
    }

    #[Route('/doctors/{id}/reviews', methods: ['GET'])]
    public function listForDoctor(
        int $id,
        DoctorRepository $doctorRepository,
        ReviewRepository $reviewRepository
    ): JsonResponse {
        $doctor = $doctorRepository->find($id);

        if (!$doctor) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Doctor not found.']], 404);
        }

        $reviews = $reviewRepository->findByDoctor($id);

        return $this->json(array_map(fn(Review $r) => $this->toReadDTO($r), $reviews));
    }

    private function toReadDTO(Review $review): ReviewReadDTO
    {
        return new ReviewReadDTO(
            $review->getId(),
            $review->getRating(),
            $review->getComment(),
            $review->getCreatedAt()->format(\DateTimeInterface::ATOM),
            $review->getAuthorName() ?? 'Anonymous',
        );
    }
}
