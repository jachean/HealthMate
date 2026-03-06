<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use App\Service\ClinicAdminContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/appointments')]
#[IsGranted('ROLE_CLINIC_ADMIN')]
class AdminAppointmentController extends AdminController
{
    public function __construct(
        private AppointmentRepository $appointmentRepository,
        private EntityManagerInterface $em,
        private ClinicAdminContext $ctx,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $limit = max(1, min(100, (int) $request->query->get('limit', '20')));

        $filters = [
            'dateFrom' => $request->query->get('dateFrom'),
            'dateTo'   => $request->query->get('dateTo'),
            'doctorId' => $request->query->get('doctorId'),
            'clinicId' => $request->query->get('clinicId'),
            'status'   => $request->query->get('status'),
            'patient'  => $request->query->get('patient'),
        ];

        // Clinic admins are restricted to their own clinic
        $scopedClinic = $this->ctx->getClinic();
        if ($scopedClinic !== null) {
            $filters['clinicId'] = $scopedClinic->getId();
        }

        $result = $this->appointmentRepository->findAllPaginatedForAdmin($page, $limit, $filters);

        return $this->json([
            'data'  => array_map($this->toAdminArray(...), $result['data']),
            'total' => $result['total'],
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    #[Route('/{id}/cancel', methods: ['PATCH'])]
    public function cancel(int $id): Response
    {
        $appointment = $this->appointmentRepository->find($id);
        if (!$appointment) {
            return $this->json(['error' => 'Appointment not found'], Response::HTTP_NOT_FOUND);
        }

        // Clinic admins can only cancel appointments in their own clinic
        $scopedClinic = $this->ctx->getClinic();
        if ($scopedClinic !== null) {
            $appointmentClinicId = $appointment->getTimeSlot()->getDoctor()->getClinic()->getId();
            if ($appointmentClinicId !== $scopedClinic->getId()) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
        }

        if ($appointment->getStatus() === Appointment::STATUS_CANCELLED) {
            return $this->json(['error' => 'Appointment is already cancelled'], Response::HTTP_CONFLICT);
        }

        $appointment->setStatus(Appointment::STATUS_CANCELLED);
        $appointment->getTimeSlot()->setIsBooked(false);
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function toAdminArray(Appointment $appointment): array
    {
        $slot         = $appointment->getTimeSlot();
        $doctor       = $slot->getDoctor();
        $user         = $appointment->getUser();
        $doctorService = $appointment->getDoctorService();

        return [
            'id'        => $appointment->getId(),
            'status'    => $appointment->getStatus(),
            'createdAt' => $appointment->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'startAt'   => $slot->getStartAt()->format(\DateTimeInterface::ATOM),
            'endAt'     => $slot->getEndAt()->format(\DateTimeInterface::ATOM),
            'doctor'    => [
                'id'     => $doctor->getId(),
                'name'   => $doctor->getFirstName() . ' ' . $doctor->getLastName(),
                'clinic' => $doctor->getClinic()->getName(),
            ],
            'patient'   => [
                'id'    => $user->getId(),
                'name'  => $user->getFirstName() . ' ' . $user->getLastName(),
                'email' => $user->getEmail(),
            ],
            'service'   => [
                'name'  => $doctorService->getMedicalService()->getName(),
                'price' => $doctorService->getPrice(),
            ],
        ];
    }
}
