<?php

namespace Keyboardman\FilemanagerBundle\Controller;

use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use Keyboardman\FilemanagerBundle\DTO\QueryFilterFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/filemanager')]
class ApiController extends AbstractController
{
    public function __construct(private readonly DiskManager $diskManager)
    {
    }

    #[Route('/list', name: 'keyboardman_filemanager_api_list')]
    public function list(QueryFilterFactory $queryFactory, Request $request): JsonResponse
    {
        try {
            $query = $queryFactory->create($request);

            return $this->json(
                $this->diskManager->list($query->filesystem, $query->path)
            );
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ]);
        }
    }

    #[Route('/upload', name: 'keyboardman_filemanager_api_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('file');
        $filesystem = $request->request->get('filesystem', 'default');
        $path = $request->request->get('path', '/');

        if (!$uploadedFile instanceof UploadedFile) {
            return new JsonResponse(['error' => 'No file uploaded'], 400);
        }

        try {
            $targetPath = $this->diskManager->upload(
                $filesystem,
                $path,
                $uploadedFile->getPathname(),
                $uploadedFile->getClientOriginalName()
            );

            return new JsonResponse([
                'success' => true,
                'path' => $targetPath,
                'name' => $uploadedFile->getClientOriginalName(),
                'mimeType' => $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
                'url' => $this->diskManager->publicUrl($filesystem, $targetPath),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/rename', name: 'keyboardman_filemanager_api_rename', methods: ['POST'])]
    public function rename(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $filesystem = $data['filesystem'] ?? null;
        $oldPath = $data['path'] ?? null;
        $newName = $data['newName'] ?? null;

        if (!is_string($filesystem) || !is_string($oldPath) || !is_string($newName)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Paramètres invalides.',
            ], 400);
        }

        try {
            $this->diskManager->rename($filesystem, $oldPath, $newName);

            return new JsonResponse([
                'success' => true,
                'message' => 'Fichier renommé avec succès',
                'path' => $oldPath,
                'newName' => $newName,
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/create-directory', name: 'keyboardman_filemanager_api_create_directory', methods: ['POST'])]
    public function createDirectory(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $filesystem = $data['filesystem'] ?? null;
        $parentPath = $data['path'] ?? '';
        $directoryName = $data['name'] ?? null;

        if (!is_string($filesystem) || !is_string($parentPath) || !is_string($directoryName)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Paramètres invalides.',
            ], 400);
        }

        try {
            $newPath = $this->diskManager->createDirectory($filesystem, $parentPath, $directoryName);

            return new JsonResponse([
                'success' => true,
                'path' => $newPath,
                'name' => trim($directoryName),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
