<?php

namespace Keyboardman\FilemanagerBundle\Controller;

use Keyboardman\FilemanagerBundle\Disk\DiskManager;
use Keyboardman\FilemanagerBundle\Media\Streaming\MediaRangeReaderResolver;
use Keyboardman\FilemanagerBundle\Media\Streaming\MediaStreamException;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class MediaController extends AbstractController
{
    public function __construct(
        private readonly DiskManager $diskManager,
        private readonly MediaRangeReaderResolver $rangeReaderResolver,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(
        '/kbd/filemanager/media/{filesystem}/{path}',
        name: 'keyboardman_filemanager_media',
        requirements: ['path' => '.+'],
        methods: ['GET', 'HEAD'],
    )]
    public function serve(string $filesystem, string $path, Request $request): Response
    {
        if (!$this->diskManager->has($filesystem)) {
            throw $this->createNotFoundException();
        }

        $normalizedPath = ltrim($path, '/');

        if ('' === $normalizedPath || str_contains($normalizedPath, '..')) {
            throw $this->createNotFoundException();
        }

        $fs = $this->diskManager->disk($filesystem)->filesystem();

        try {
            if (!$fs->fileExists($normalizedPath)) {
                throw $this->createNotFoundException();
            }

            $fileSize = $fs->fileSize($normalizedPath);
            $mimeType = $this->diskManager->resolveMimeType($filesystem, $normalizedPath);
        } catch (FilesystemException $exception) {
            $this->logger->warning('Media metadata lookup failed.', [
                'filesystem' => $filesystem,
                'path' => $normalizedPath,
                'exception' => $exception,
            ]);

            throw $this->createNotFoundException();
        }

        if ($fileSize < 0) {
            throw $this->createNotFoundException();
        }

        $range = $this->parseRange($request->headers->get('Range'), $fileSize);
        if ($range instanceof Response) {
            return $range;
        }

        [$start, $end, $status] = $range;
        $length = $end - $start + 1;

        $headers = [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Content-Length' => (string) $length,
        ];

        if (Response::HTTP_PARTIAL_CONTENT === $status) {
            $headers['Content-Range'] = sprintf('bytes %d-%d/%d', $start, $end, $fileSize);
        }

        if (Request::METHOD_HEAD === $request->getMethod()) {
            return new Response(null, $status, $headers);
        }

        try {
            $output = $this->rangeReaderResolver->readRange($fs, $normalizedPath, $start, $length);
        } catch (MediaStreamException $exception) {
            $this->logger->error('Media range read failed before streaming.', [
                'filesystem' => $filesystem,
                'path' => $normalizedPath,
                'start' => $start,
                'length' => $length,
                'exception' => $exception,
            ]);

            return new Response(null, Response::HTTP_BAD_GATEWAY);
        }

        return new StreamedResponse($output, $status, $headers);
    }

    /**
     * @return array{0: int, 1: int, 2: int}|Response
     */
    private function parseRange(?string $rangeHeader, int $fileSize): array|Response
    {
        $start = 0;
        $end = $fileSize - 1;
        $status = Response::HTTP_OK;

        if (is_string($rangeHeader) && preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $matches)) {
            $start = (int) $matches[1];
            $end = '' !== $matches[2] ? (int) $matches[2] : $fileSize - 1;

            if ($start > $end || $start >= $fileSize) {
                return new Response(null, Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, [
                    'Content-Range' => sprintf('bytes */%d', $fileSize),
                ]);
            }

            $end = min($end, $fileSize - 1);
            $status = Response::HTTP_PARTIAL_CONTENT;
        }

        return [$start, $end, $status];
    }
}
