<?php

namespace Keyboardman\FilemanagerBundle\Controller;

use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use Keyboardman\FilemanagerBundle\DTO\QueryFilterFactory;
use Keyboardman\FilemanagerBundle\Security\IframeTokenValidator;
use Keyboardman\FilemanagerBundle\Upload\UploadLimitResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur principal affichant l'interface du filemanager en mode page ou iframe.
 */
class FilemanagerController extends AbstractController
{
    public function __construct(
        private readonly DiskManager $diskManager,
        private readonly QueryFilterFactory $queryFactory,
        private readonly UploadLimitResolver $uploadLimitResolver,
        private readonly int $chunkSize,
        private readonly int $chunkThreshold,
    ) {
    }

    #[Route('/kbd/filemanager', name: 'keyboardman_filemanager')]
    /** Affiche l'interface du filemanager avec listage et filtres. */
    public function __invoke(Request $request, IframeTokenValidator $validator): Response
    {
        if (!$validator->isValid($request)) {
            throw $this->createAccessDeniedException('Invalid token');
        }

        $query = $this->queryFactory->create($request);

        $contents = $this->diskManager->list($query->filesystem, $query->path, $query->type, $query->sort);

        $directories = array_reduce($contents, function ($carry, $item) {
            if ('dir' === $item['type']) {
                $carry[] = $item;
            }

            return $carry;
        });

        $files = array_reduce($contents, function ($carry, $item) {
            if ('file' === $item['type']) {
                $carry[] = $item;
            }

            return $carry;
        });

        $paths = array_values(array_filter(explode('/', $query->path)));

        $uploadLimits = $this->uploadLimitResolver->resolve($this->chunkSize, $this->chunkThreshold);

        return $this->render('@KeyboardmanFilemanager/filemanager/layout.html.twig',
            [
                'filesystems' => $this->diskManager->all(),
                'filter' => $query,
                'directories' => $directories,
                'files' => $files,
                'paths' => $paths,
                'chunk_size' => $uploadLimits['chunk_size'],
                'chunk_threshold' => $uploadLimits['chunk_threshold'],
            ]
        );
    }
}
